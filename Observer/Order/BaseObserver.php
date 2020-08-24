<?php

namespace Bitbull\AWSEventBridge\Observer\Order;

use Bitbull\AWSEventBridge\Observer\BaseObserver as ParentBaseObserver;
use Magento\Framework\Event\Observer;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Address;

abstract class BaseObserver extends ParentBaseObserver
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getEvent()->getOrder();

        $this->eventEmitter->send($this->getFullEventName(), $this->getOrderData($order));
    }

    /**
     * Get order data
     *
     * @return array
     * @var OrderInterface $order
     */
    public function getOrderData($order)
    {
        /** @var Address $shippingAddress */
        $shippingAddress = $order->getShippingAddress();
        /** @var Address $billingAddress */
        $billingAddress = $order->getBillingAddress();

        $items = $order->getItems();
        if (is_object($items)) {
            $items = $items->getItems();
        }

        return [
            'id' => $order->getIncrementId(),
            'status' => $order->getStatus(),
            'coupon' => $order->getCouponCode(),
            'billingAddress' => $this->getAddressData($billingAddress),
            'shippingAddress' => $this->getAddressData($shippingAddress),
            'shippingAmount' => $order->getShippingAmount(),
            'taxAmount' => $order->getTaxAmount(),
            'total' => $order->getGrandTotal(),
            'items' => array_map(static function ($item) {

                /** @var \Magento\Sales\Api\Data\OrderItemInterface $item */
                return [
                    'sku' => $item->getSku(),
                    'name' => $item->getName(),
                    'price' => $item->getPrice(),
                    'qty' => $item->getQtyOrdered(),
                ];
            }, $items)
        ];
    }

    /**
     * Get address data
     *
     * @return array
     * @var Address $address
     */
    public function getAddressData($address)
    {
        return [
          'countryId' => $address->getCountryId(),
          'region' => $address->getRegion(),
          'street' => $address->getStreet(),
          'city' => $address->getCity(),
          'postCode' => $address->getPostcode(),
        ];
    }
}
