<?php
namespace Bitbull\Mimmo\Observer\Cart;

use Bitbull\Mimmo\Observer\BaseObserver;
use Magento\Framework\Event\Observer;

class ProductAdded extends BaseObserver
{
    const EVENT_NAME = 'CartProductAdded';

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
        $product = $observer->getProduct();

        $this->eventEmitter->send($this->getEventName(), [
            'sku' => $product->getSku(),
            'qty' => round($product->getCartQty())
        ]);
    }
}
