<?php
namespace Bitbull\Mimmo\Observer\Cart;

use Bitbull\Mimmo\Observer\BaseObserver;
use Magento\Framework\Event\Observer;

class ProductRemoved extends BaseObserver
{
    const EVENT_NAME = 'CartProductRemoved';

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

        $this->eventEmitter->send($this->getEventName(), [
            'sku' => $product->getSku(),
            'qty' => round($quoteItem->getQty())
        ]);
    }
}
