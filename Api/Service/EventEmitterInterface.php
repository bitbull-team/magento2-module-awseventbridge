<?php declare(strict_types=1);

namespace Bitbull\AWSEventBridge\Api\Service;

interface EventEmitterInterface
{
    /**
     * Send event
     *
     * @param string $eventName
     * @param array $eventData
     */
    public function send($eventName, $eventData);
}
