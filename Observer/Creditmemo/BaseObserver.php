<?php

namespace Bitbull\AWSEventBridge\Observer\Creditmemo;

use Bitbull\AWSEventBridge\Api\Service\ConfigInterface;
use Bitbull\AWSEventBridge\Api\Service\LoggerInterface;
use Bitbull\AWSEventBridge\Model\Service\EventEmitter;
use Bitbull\AWSEventBridge\Observer\BaseObserver as ParentBaseObserver;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\Observer;

abstract class BaseObserver extends ParentBaseObserver
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Api\Data\CreditmemoInterface $creditMemo */
        $creditMemo = $observer->getEvent()->getCreditmemo();

        $this->eventEmitter->send($this->getFullEventName(), $this->getCreditmemoData($creditMemo));
    }

    /**
     * Get credit memo data
     *
     * @return array
     * @var \Magento\Sales\Api\Data\CreditmemoInterface $creditMemo
     */
    public function getCreditmemoData($creditMemo)
    {
        return [
            'id' => $creditMemo->getIncrementId(),
            'shipping' => $creditMemo->getShippingAmount(),
            'tax' => $creditMemo->getTaxAmount(),
            'total' => $creditMemo->getGrandTotal(),
            'status' => $creditMemo->getCreditmemoStatus(),
            'items' => array_map(function ($item) {
                /** @var \Magento\Sales\Api\Data\CreditmemoItemInterface $item */
                return [
                    'sku' => $item->getSku(),
                    'name' => $item->getName(),
                    'price' => $item->getPrice(),
                    'quantity' => $item->getQty(),
                ];
            }, $creditMemo->getItems())
        ];
    }
}
