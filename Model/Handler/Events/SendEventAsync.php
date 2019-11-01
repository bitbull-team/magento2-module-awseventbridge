<?php declare(strict_types=1);

namespace Bitbull\AWSEventBridge\Model\Handler\Events;

use Bitbull\AWSEventBridge\Model\Service\EventEmitter;

abstract class SendEventAsync extends EventEmitter
{
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
        $this->sendImmediately($payload['name'], $payload['data']);
    }
}
