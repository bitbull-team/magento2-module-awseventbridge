<?php declare(strict_types=1);

namespace Bitbull\AWSEventBridge\Model\Handler\Events;

use Bitbull\AWSEventBridge\Model\Service\EventEmitter;
use Magento\Framework\Serialize\Serializer\Json as SerializerJson;

class SendEventAsync
{
    /**
     * @var EventEmitter
     */
    private $eventEmitter;

    /**
     * @var SerializerJson
     */
    protected $serializerJson;

    /**
     * Event emitter.
     *
     * @param EventEmitter $eventEmitter
     * @param SerializerJson $serializerJson
     */
    public function __construct(
        EventEmitter $eventEmitter,
        SerializerJson $serializerJson
    ) {
        $this->eventEmitter = $eventEmitter;
        $this->serializerJson = $serializerJson;
    }

        /**
     * Process queue message
     *
     * @param $payload
     */
    public function processQueueMessage($payload) {
        $payload = $this->serializerJson->unserialize($payload);
        if (!isset($payload['name'], $payload['data'])) {
            throw new \InvalidArgumentException("Invalid queue message: 'name' and 'data' are required properties.");
        }
        $this->eventEmitter->sendImmediately($payload['name'], $payload['data']);
    }
}
