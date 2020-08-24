<?php

namespace Bitbull\AWSEventBridge\Observer\Creditmemo;

use Bitbull\AWSEventBridge\Api\Service\ConfigInterface;
use Bitbull\AWSEventBridge\Api\Service\LoggerInterface;
use Bitbull\AWSEventBridge\Model\Service\EventEmitter;
use Bitbull\AWSEventBridge\Observer\BaseObserver as ParentBaseObserver;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order\Creditmemo;

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
        switch ($creditMemo->getCreditmemoStatus()) {
            case Creditmemo::STATE_OPEN:
                $status = 'OPEN';
                break;
            case Creditmemo::STATE_REFUNDED:
                $status = 'REFUNDED';
                break;
            case Creditmemo::STATE_CANCELED:
                $status = 'CANCELED';
                break;
            default:
                $status = (string) $creditMemo->getStatus();
        }

        return [
            'id' => $creditMemo->getIncrementId(),
            'shippingAmount' => $creditMemo->getShippingAmount(),
            'taxAmount' => $creditMemo->getTaxAmount(),
            'total' => $creditMemo->getGrandTotal(),
            'status' => $status,
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
