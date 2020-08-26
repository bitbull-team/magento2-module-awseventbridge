<?php

namespace Bitbull\AWSEventBridge\Observer\Invoice;

use Magento\Framework\Event\Observer;

class Saved extends BaseObserver
{
    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Api\Data\InvoiceInterface $invoice */
        $invoice = $observer->getEvent()->getInvoice();

        if ($invoice->isObjectNew()) {
            $this->eventEmitter->send($this->getScopeName() . 'Created', $this->getInvoiceData($invoice));
        } else {
            $this->eventEmitter->send($this->getScopeName() . 'Updated', $this->getInvoiceData($invoice));
        }
    }
}
