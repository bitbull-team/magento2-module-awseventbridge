<?php

namespace Bitbull\AWSEventBridge\Model\Service;

use Bitbull\AWSEventBridge\Api\Service\ConfigInterface;
use Bitbull\AWSEventBridge\Api\Service\EventEmitterInterface;
use Bitbull\AWSEventBridge\Api\Service\LoggerInterface;
use Aws\CloudWatchEvents\CloudWatchEventsClient;
use Aws\EventBridge\EventBridgeClient;
use Bitbull\AWSEventBridge\Api\Service\TrackingInterface;
use Magento\Framework\Serialize\Serializer\Json as SerializerJson;

class EventEmitter implements EventEmitterInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SerializerJson
     */
    private $serializerJson;

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
     * Event emitter.
     *
     * @param LoggerInterface $logger
     * @param ConfigInterface $config
     * @param SerializerJson $serializerJson
     * @param TrackingInterface $tracking
     */
    public function __construct(
        LoggerInterface $logger,
        ConfigInterface $config,
        SerializerJson $serializerJson,
        TrackingInterface $tracking
    )
    {
        $this->logger = $logger;
        $this->serializerJson = $serializerJson;
        $this->tracking = $tracking;

        $this->source = $config->getSource();
        $this->dryRun = $config->isDryRunModeEnabled();
        $this->cloudWatchEventFallback = $config->isCloudWatchEventFallbackEnabled();
        $this->trackingEnabled = $config->isTrackingEnabled();

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
