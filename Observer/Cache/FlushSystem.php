<?php
namespace Bitbull\AWSEventBridge\Observer\Cache;

use Bitbull\AWSEventBridge\Observer\BaseObserver;
use Magento\Framework\Event\Observer;

class FlushSystem extends BaseObserver
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
