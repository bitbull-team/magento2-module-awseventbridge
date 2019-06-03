<?php declare(strict_types=1);

namespace Bitbull\Mimmo\Api;

interface ObserverInterface extends \Magento\Framework\Event\ObserverInterface
{
    /**
     * Get event name
     *
     * @return string
     */
    public function getEventName();
}
