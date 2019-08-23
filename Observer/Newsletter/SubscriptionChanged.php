<?php
namespace Bitbull\AWSEventBridge\Observer\Newsletter;

use Bitbull\AWSEventBridge\Observer\BaseObserver;
use Magento\Framework\Event\Observer;

class SubscriptionChanged extends BaseObserver
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $subscriber = $observer->getEvent()->getSubscriber();
        if ($subscriber->isStatusChanged() === false) {
            return;
        }
        $this->eventEmitter->send($this->getFullEventName(), [
            'status' => $subscriber->isSubscribed()
        ]);
    }
}
