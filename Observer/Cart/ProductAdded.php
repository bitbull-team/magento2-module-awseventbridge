<?php

namespace Bitbull\AWSEventBridge\Observer\Cart;

use Magento\Framework\Event\Observer;

class ProductAdded extends BaseObserver
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
        $product = $observer->getProduct();

        $this->eventEmitter->send($this->getFullEventName(), [
            'sku' => $product->getSku(),
            'qty' => round($product->getCartQty())
        ]);
    }
}
