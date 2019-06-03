<?php declare(strict_types=1);

namespace Bitbull\Mimmo\Api\Service;

interface EventEmitterInterface
{
    /**
     * Send event
     *
     * @param string $eventName
     * @param array $data
     */
    public function send($eventName, $data);
}
