<?php
namespace Bitbull\AWSEventBridge\Observer\User;

use Bitbull\AWSEventBridge\Observer\BaseObserver;
use Magento\Framework\Event\Observer;

class LoginFailed extends BaseObserver
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->eventEmitter->send($this->getFullEventName(), [
            'username' => $observer->getUserName()
        ]);
    }
}
