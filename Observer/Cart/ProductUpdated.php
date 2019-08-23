<?php
namespace Bitbull\AWSEventBridge\Observer\Cart;

use Bitbull\AWSEventBridge\Observer\BaseObserver;
use Magento\Framework\Event\Observer;

class ProductUpdated extends BaseObserver
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $items = $observer->getCart()->getQuote()->getItems();
        $info = $observer->getInfo()->getData();

        foreach ($items as $item) {
            $qtyFrom = $item->getQty();
            if (!isset($info[$item->getId()]) || !isset($info[$item->getId()]['qty'])) {
                $this->logger->warn('Invalid observer data: '.$item->getId().' not found in infos, tracking skipped', [
                    'info' => $info,
                    'item' => $item->getId()
                ]);
                continue;
            }
            $qtyTo = $info[$item->getId()]['qty'];
            $qtyDiff = round($qtyTo) - round($qtyFrom);

            if ($qtyDiff > 0) {
                $event = 'add';
            }else if($qtyDiff < 0){
                $event = 'remove';
            }else{
                $this->logger->warn('Product "'. $item->getName() .'" has no changes, qty delta is 0, tracking skipped');
                continue;
            }

            $this->eventEmitter->send($this->getFullEventName(), [
                'sku' => $item->getSku(),
                'operation' => $event,
                'value' => abs($qtyDiff),
                'qty' => [
                    'from' => $qtyFrom,
                    'to' => $qtyTo,
                ]
            ]);
        }
    }
}
