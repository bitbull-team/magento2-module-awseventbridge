<?php
namespace Bitbull\AWSEventBridge\Observer\Newsletter;

use Magento\Framework\Event\Observer;

class SubscriptionChanged extends BaseObserver
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Newsletter\Model\Subscriber $subscriber */
        $subscriber = $observer->getEvent()->getSubscriber();
        if ($subscriber->isStatusChanged() === false) {
            return;
        }
        $this->eventEmitter->send($this->getFullEventName(), $this->getSubscriberData($subscriber));
    }
}
