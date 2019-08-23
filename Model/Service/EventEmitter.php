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
                    $this->cloudWatchEventClient =  new CloudWatchEventsClient([
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
        $data = [
            'data' => $eventData,
            'tracking' => $this->tracking->getTrackingParams()
        ];

        if ($this->dryRun === true) {
            $this->logger->debug("[DryRun] Sending event '$eventName' with data: ".print_r($data, true));
            $this->logger->debug("[DryRun] Event '$eventName' sent with id 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'");
            return;
        }

        $data = [
            'Source' => $this->source,
            'Detail' => $this->serializerJson->serialize($data),
            'DetailType' => $eventName,
            'Resources' => [],
            'Time' => time()
        ];
        try {
            if ($this->cloudWatchEventFallback) {
                $this->logger->debug("Sending event '$eventName' to bus '" . ( $this->eventBus ?? '') . "' with data: ".print_r($data, true));
                $data['EventBusName'] = $this->eventBus;
                $result = $this->eventBridgeClient->putEvents([
                    'Entries' => [ $data ]
                ]);
            } else {
                $this->logger->debug("Sending event '$eventName' with data: ".print_r($data, true));
                $result = $this->cloudWatchEventClient->putEvents([
                    'Entries' => [ $data ]
                ]);
            }
        }catch (\Exception $exception) {
            $this->logger->logException($exception);
            return;
        }

        foreach ($result['Entries'] as $entry) {
            if (isset($entry['EventId']) && $entry['EventId'] !== null){
                $this->logger->debug("Event '$eventName' sent with id '".$entry['EventId']."'");
            } else {
                $this->logger->error('Error '.$entry['ErrorCode']." sending event '$eventName': ".$entry['ErrorMessage']);
            }
        }
    }
}
