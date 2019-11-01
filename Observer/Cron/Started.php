<?php
namespace Bitbull\AWSEventBridge\Observer\Cron;

use Bitbull\AWSEventBridge\Observer\BaseObserver;
use Magento\Framework\Event\Observer;

class Started extends BaseObserver
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $eventData = $observer->getData();
        $this->eventEmitter->send($this->getFullEventName(), [
            'code' => $eventData['code'],
            'groupId' => $eventData['groupId'],
            'jobConfig' => $eventData['jobConfig']
        ]);
    }
}
