<?php declare(strict_types=1);

namespace Bitbull\AWSEventBridge\Api;

interface ObserverInterface extends \Magento\Framework\Event\ObserverInterface
{
    /**
     * Get event name
     *
     * @return string
     */
    public function getEventName();

    /**
     * Get scope name
     *
     * @return string|null
     */
    public function getScopeName();

    /**
     * Get full event name
     *
     * @return string
     */
    public function getFullEventName();
}
