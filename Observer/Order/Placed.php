<?php
namespace Bitbull\AWSEventBridge\Observer\Order;

use Bitbull\AWSEventBridge\Observer\BaseObserver;
use Magento\Framework\Event\Observer;

class Placed extends BaseObserver
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
        $order = $observer->getEvent()->getOrder();

        $this->eventEmitter->send($this->getFullEventName(), [
            'id' => $order->getIncrementId(),
            'status' => $order->getStatus(),
            'shipping' => $order->getShippingAmount(),
            'coupon' => $order->getCouponCode(),
            'tax' => $order->getTaxAmount(),
            'total' => $order->getGrandTotal(),
            'items' => array_map(function ($item) {

                /** @var \Magento\Sales\Api\Data\OrderItemInterface $item */
                return [
                    'sku' => $item->getSku(),
                    'name' => $item->getName(),
                    'price' => $item->getPrice(),
                    'quantity' => $item->getQtyOrdered(),
                ];
            }, $order->getItems())
        ]);
    }
}
