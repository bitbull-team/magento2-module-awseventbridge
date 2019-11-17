<?php
namespace Bitbull\AWSEventBridge\Observer\Invoice;

use Bitbull\AWSEventBridge\Observer\BaseObserver as ParentBaseObserver;
use Magento\Framework\Event\Observer;

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
     * @var \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @return array
     */
    public function getInvoiceData($invoice) {
        return [
            'id' => $invoice->getIncrementId(),
            'orderId' => $invoice->getOrderId(),
            'shipping' => $invoice->getShippingAmount(),
            'tax' => $invoice->getTaxAmount(),
            'total' => $invoice->getGrandTotal(),
            'items' => array_map(function ($item) {
                /** @var \Magento\Sales\Api\Data\InvoiceItemInterface $item */
                return [
                    'sku' => $item->getSku(),
                    'name' => $item->getName(),
                    'price' => $item->getPrice(),
                    'quantity' => $item->getQty(),
                ];
            }, $invoice->getItems())
        ];
    }
}
