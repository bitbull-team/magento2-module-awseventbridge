<?php

namespace Bitbull\AWSEventBridge\Observer\Creditmemo;

use Magento\Framework\Event\Observer;

class Saved extends BaseObserver
{
    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Api\Data\CreditmemoInterface $creditMemo */
        $creditMemo = $observer->getEvent()->getCreditmemo();

        if ($creditMemo->isObjectNew()) {
            $this->eventEmitter->send($this->getScopeName() . 'Created', $this->getCreditmemoData($creditMemo));
        } else {
            $this->eventEmitter->send($this->getScopeName() . 'Updated', $this->getCreditmemoData($creditMemo));
        }
    }
}
