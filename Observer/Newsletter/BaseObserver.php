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
     * @return array
     * @var \Magento\Newsletter\Model\Subscriber $subscriber
     */
    public function getSubscriberData($subscriber)
    {
        switch ($subscriber->getStatus()) {
            case \Magento\Newsletter\Model\Subscriber::STATUS_NOT_ACTIVE:
                $status = 'NOT_ACTIVE';
                break;
            case \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED:
                $status = 'SUBSCRIBED';
                break;
            case \Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED:
                $status = 'UNSUBSCRIBED';
                break;
            case \Magento\Newsletter\Model\Subscriber::STATUS_UNCONFIRMED:
                $status = 'UNCONFIRMED';
                break;
            default:
                $status = (string)$subscriber->getStatus();
        }

        return [
            'email' => $subscriber->getEmail(),
            'unsubscriptionLink' => $subscriber->getUnsubscriptionLink(),
            'code' => $subscriber->getCode(),
            'isSubscribed' => (boolean)$subscriber->isSubscribed(),
            'status' => $status
        ];
    }
}
