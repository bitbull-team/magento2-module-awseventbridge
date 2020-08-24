<?php
namespace Bitbull\AWSEventBridge\Observer\Cart;

use Magento\Framework\Event\Observer;

class ProductRemoved extends BaseObserver
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $observer->getQuoteItem();

        /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
        $product = $quoteItem->getProduct();

        $this->eventEmitter->send($this->getFullEventName(), [
            'sku' => $product->getSku(),
            'qty' => round($quoteItem->getQty())
        ]);
    }
}
