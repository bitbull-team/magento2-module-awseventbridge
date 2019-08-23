<?php
namespace Bitbull\AWSEventBridge\Observer\User;

use Bitbull\AWSEventBridge\Api\Service\ConfigInterface;
use Bitbull\AWSEventBridge\Api\Service\LoggerInterface;
use Bitbull\AWSEventBridge\Model\Service\EventEmitter;
use Bitbull\AWSEventBridge\Observer\BaseObserver;
use Magento\Framework\Event\Observer;
use \Magento\Backend\Model\Auth\Session as AuthSession;

class LoggedOut extends BaseObserver
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->eventEmitter->send($this->getFullEventName(), []);
    }
}
