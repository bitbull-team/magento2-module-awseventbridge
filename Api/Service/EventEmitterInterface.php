<?php declare(strict_types=1);

namespace Bitbull\AWSEventBridge\Api\Service;

interface EventEmitterInterface
{
    /**
     * Add event metadata
     *
     * @param array $eventData
     * @return array
     */
    public function addEventMetadata($eventData);

    /**
     * Send event
     *
     * @param string $eventName
     * @param array $eventData
     */
    public function send($eventName, $eventData);
}
