<?php

namespace Bitbull\AWSEventBridge\Model\Service;

use Aws\CloudWatchEvents\CloudWatchEventsClient;
use Aws\EventBridge\EventBridgeClient;
use Bitbull\AWSEventBridge\Api\Service\ConfigInterface;
use Bitbull\AWSEventBridge\Api\Service\EventEmitterInterface;
use Bitbull\AWSEventBridge\Api\Service\LoggerInterface;
use Bitbull\AWSEventBridge\Api\Service\TrackingInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json as SerializerJson;
use Magento\Framework\Stdlib\DateTime\DateTime;

class EventEmitter implements EventEmitterInterface
{
    const TOPIC_NAME = 'aws.eventbridge.events.send';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SerializerJson
     */
    protected $serializerJson;

    /**
     * @var EventBridgeClient
     */
    private $eventBridgeClient;

    /**
     * @var CloudWatchEventsClient
     */
    private $cloudWatchEventClient;

    /**
     * @var TrackingInterface
     */
    private $tracking;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    private $publisher;

    /**
     * @var string|null
     */
    private $source;

    /**
     * @var string|null
     */
    private $eventBus;

    /**
     * @var boolean
     */
    private $dryRun;

    /**
     * @var boolean
     */
    private $cloudWatchEventFallback;

    /**
     * @var boolean
     */
    private $trackingEnabled;

    /**
     * @var boolean
     */
    private $queueMode;
    /**
     * @var DateTime
     */
    private $date;

    /**
     * Event emitter.
     *
     * @param LoggerInterface $logger
     * @param ConfigInterface $config
     * @param SerializerJson $serializerJson
     * @param TrackingInterface $tracking
     * @param DateTime $date
     */
    public function __construct(
        LoggerInterface $logger,
        ConfigInterface $config,
        SerializerJson $serializerJson,
        TrackingInterface $tracking,
        DateTime $date
    ) {
        $this->logger = $logger;
        $this->serializerJson = $serializerJson;
        $this->tracking = $tracking;
        $this->date = $date;

        $this->source = $config->getSource();
        $this->dryRun = $config->isDryRunModeEnabled();
        $this->cloudWatchEventFallback = $config->isCloudWatchEventFallbackEnabled();
        $this->trackingEnabled = $config->isTrackingEnabled();

        if (\class_exists('\Magento\Framework\MessageQueue\Publisher') && $config->isQueueModeEnabled()) {
            // Dynamically load queue publisher based on Magento edition
            $objManager = ObjectManager::getInstance();
            $this->publisher = $objManager->create('\Magento\Framework\MessageQueue\PublisherInterface');
            $this->queueMode = true;
        } else {
            $this->queueMode = false;
        }

        if ($this->dryRun === false) {
            if ($this->cloudWatchEventFallback === false) {
                $this->eventBus = $config->getEventBusName();
                try {
                    $this->eventBridgeClient = new EventBridgeClient([
                        'version' => '2015-10-07',
                        'region' => $config->getRegion(),
                        'credentials' => $config->getCredentials(),
                    ]);
                } catch (\Exception $exception) {
                    $this->logger->logException($exception);
                }
            } else {
                try {
                    $this->cloudWatchEventClient = new CloudWatchEventsClient([
                        'version' => '2015-10-07',
                        'region' => $config->getRegion(),
                        'credentials' => $config->getCredentials(),
                    ]);
                } catch (\Exception $exception) {
                    $this->logger->logException($exception);
                }
            }
        }
    }

    /** @inheritDoc */
    public function send($eventName, $eventData)
    {
        // set date and timestamp data
        $eventData['date'] = $this->date->gmtDate();
        $eventData['timestamp'] = $this->date->gmtTimestamp();

        // conditionally send event synchronously or asynchronously (with queue)
        if ($this->queueMode === true) {
            $this->addEventToQueue($eventName, $eventData);
        } else {
            $this->sendImmediately($eventName, $eventData);
        }
    }

    /**
     * Add event to queue
     *
     * @param $eventName
     * @param $eventData
     */
    protected function addEventToQueue($eventName, $eventData)
    {
        if ($this->publisher === null) {
            $this->logger->error("No queue publisher set, 'addEventToQueue' method cannot work");
            return;
        }
        try {
            $this->publisher->publish(self::TOPIC_NAME, [
                $this->serializerJson->serialize([
                    'name' => $eventName,
                    'data' => $eventData
                ])
            ]);
        } catch (\Exception $exception) {
            $this->logger->logException($exception);
            return;
        }
        $this->logger->debug("Event '$eventName' published to topic '" . self::TOPIC_NAME . "' with data: " . print_r($eventData, true));
    }

    /**
     * Send event
     *
     * @param $eventName
     * @param $eventData
     */
    public function sendImmediately($eventName, $eventData)
    {
        // Check if tracking is enabled, in case add client infos
        if ($this->trackingEnabled === true) {
            $eventData['tracking'] = $this->tracking->getTrackingParams();
        }

        // If dry run is enabled do a fake operation
        if ($this->dryRun === true) {
            $this->logger->debug("[DryRun] Sending event '$eventName' with data: " . print_r($eventData, true));
            $this->logger->debug("[DryRun] Event '$eventName' sent with id 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'");
            return;
        }

        // Elaborate event parameters
        $eventEntry = [
            'Source' => $this->source,
            'Detail' => $this->serializerJson->serialize($eventData),
            'DetailType' => $eventName,
            'Resources' => [],
            'Time' => time()
        ];
        try {
            if ($this->cloudWatchEventFallback === false) {
                // Send event using EventBridge
                $this->logger->debug("Sending event '$eventName' to bus '" . ($this->eventBus ?? '') . "' with data: " . print_r($eventData, true));
                $eventEntry['EventBusName'] = $this->eventBus;
                $result = $this->eventBridgeClient->putEvents([
                    'Entries' => [$eventEntry]
                ]);
            } else {
                // Send event using CloudWatch Event
                $this->logger->debug("Sending event '$eventName' with data: " . print_r($eventData, true));
                $result = $this->cloudWatchEventClient->putEvents([
                    'Entries' => [$eventEntry]
                ]);
            }
        } catch (\Exception $exception) {
            $this->logger->logException($exception);
            return;
        }

        foreach ($result['Entries'] as $entry) {
            if (isset($entry['EventId']) && $entry['EventId'] !== null) {
                $this->logger->debug("Event '$eventName' sent with id '" . $entry['EventId'] . "'");
            } else {
                $this->logger->error('Error ' . $entry['ErrorCode'] . " sending event '$eventName': " . $entry['ErrorMessage']);
            }
        }
    }
}
