<?php

namespace Bitbull\AWSEventBridge\Observer\Invoice;

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
        /** @var \Magento\Sales\Api\Data\InvoiceInterface $invoice */
        $invoice = $observer->getEvent()->getInvoice();

        $this->eventEmitter->send($this->getFullEventName(), $this->getInvoiceData($invoice));
    }

    /**
     * Get invoice data
     *
     * @return array
     * @var \Magento\Sales\Api\Data\InvoiceInterface $invoice
     */
    public function getInvoiceData($invoice)
    {
        /** @var Address $billingAddress */
        $billingAddress = $invoice->getBillingAddress();

        $items = $invoice->getItems();
        if (is_object($items)) {
            $items = $items->getItems();
        }

        /** @var OrderInterface $order */
        $order = $invoice->getOrder();

        return [
            'orderId' => $order->getIncrementId(),
            'billingAddress' => $this->getAddressData($billingAddress),
            'shippingAmount' => $invoice->getShippingAmount(),
            'taxAmount' => $invoice->getTaxAmount(),
            'total' => $invoice->getGrandTotal(),
            'items' => array_map(static function ($item) {

                /** @var \Magento\Sales\Api\Data\InvoiceItemInterface $item */
                return [
                    'sku' => $item->getSku(),
                    'name' => $item->getName(),
                    'price' => $item->getPrice(),
                    'qty' => $item->getQty(),
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
