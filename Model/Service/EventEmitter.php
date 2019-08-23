<?php

namespace Bitbull\AWSEventBridge\Model\Service;

use Bitbull\AWSEventBridge\Api\Service\ConfigInterface;
use Bitbull\AWSEventBridge\Api\Service\EventEmitterInterface;
use Bitbull\AWSEventBridge\Api\Service\LoggerInterface;
use Aws\CloudWatchEvents\CloudWatchEventsClient;
use Bitbull\AWSEventBridge\Api\Service\TrackingInterface;
use Magento\Framework\Serialize\Serializer\Json as SerializerJson;

class EventEmitter implements EventEmitterInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var SerializerJson
     */
    protected $serializerJson;

    /**
     * @var CloudWatchEventsClient
     */
    private $client;

    /**
     * @var TrackingInterface
     */
    protected $tracking;
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
        $this->config = $config;
        $this->serializerJson = $serializerJson;
        $this->tracking = $tracking;

        if ($this->config->isDryRunModeEnabled()) {
            try {
                $this->client = new CloudWatchEventsClient([
                    'version' => '2015-10-07',
                    'region' => $this->config->getRegion(),
                    'credentials' => $this->config->getCredentials(),
                ]);
            } catch (\Exception $exception) {
                $this->logger->logException($exception);

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

        if ($this->config->isDryRunModeEnabled()) {
            $this->logger->debug("[DryRun] Sending event '$eventName' with data: ".print_r($data, true));
            $this->logger->debug("[DryRun] Event '$eventName' sent with id 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'");
            return;
        }

        $this->logger->debug("Sending event '$eventName' with data: ".print_r($data, true));
        try {
            $result = $this->client->putEvents([
                'Entries' => [
                    [
                        'Source' => $this->config->getSource(),
                        'Detail' => $this->serializerJson->serialize($data),
                        'DetailType' => $eventName,
                        'Resources' => []
                    ]
                ]
            ]);
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
