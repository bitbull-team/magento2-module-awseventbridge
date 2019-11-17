<?php
namespace Bitbull\AWSEventBridge\Observer\Newsletter;

use Bitbull\AWSEventBridge\Observer\BaseObserver as ParentBaseObserver;
use Magento\Framework\Event\Observer;

abstract class BaseObserver extends ParentBaseObserver
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Newsletter\Model\Subscriber $subscriber */
        $subscriber = $observer->getEvent()->getSubscriber();

        $this->eventEmitter->send($this->getFullEventName(), $this->getSubscriberData($subscriber));
    }

    /**
     * Get subscriber data
     *
     * @var \Magento\Newsletter\Model\Subscriber $subscriber
     * @return array
     */
    public function getSubscriberData($subscriber) {
        return [
            'email' => $subscriber->getEmail(),
            'unsubscriptionLink' => $subscriber->getUnsubscriptionLink(),
            'code' => $subscriber->getCode(),
            'status' => $subscriber->getStatus()
        ];
    }
}
