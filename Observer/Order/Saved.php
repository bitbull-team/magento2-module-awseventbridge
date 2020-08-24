<?php

namespace Bitbull\AWSEventBridge\Observer\Order;

use Magento\Framework\Event\Observer;

class Saved extends BaseObserver
{
    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
        $order = $observer->getEvent()->getOrder();

        if ($order->isObjectNew()) {
            $this->eventEmitter->send($this->getScopeName() . 'Created', $this->getOrderData($order));
        } else {
            $this->eventEmitter->send($this->getScopeName() . 'Updated', $this->getOrderData($order));
        }
    }
}
