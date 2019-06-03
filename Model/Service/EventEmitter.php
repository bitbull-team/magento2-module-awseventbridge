<?php

namespace Bitbull\Mimmo\Model\Service;

use Bitbull\Mimmo\Api\Service\ConfigInterface;
use Bitbull\Mimmo\Api\Service\EventEmitterInterface;
use Bitbull\Mimmo\Api\Service\LoggerInterface;
use Aws\CloudWatchEvents\CloudWatchEventsClient;
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
     * Event emitter.
     *
     * @param LoggerInterface $logger
     * @param ConfigInterface $config
     * @param SerializerJson $serializerJson
     */
    public function __construct(
        LoggerInterface $logger,
        ConfigInterface $config,
        SerializerJson $serializerJson
    )
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->serializerJson = $serializerJson;
        $this->client = new CloudWatchEventsClient([
            'version' => '2015-10-07',
            'region' => $this->config->getRegion(),
            'credentials' => $this->config->getCredentials(),
        ]);
    }

    /** @inheritDoc */
    public function send($eventName, $data)
    {
        $this->logger->debug("Event '$eventName' sending with data: ".print_r($data, true));
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
