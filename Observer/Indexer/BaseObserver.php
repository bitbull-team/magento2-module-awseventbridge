<?php

namespace Bitbull\AWSEventBridge\Observer\Indexer;

use Bitbull\AWSEventBridge\Observer\BaseObserver as ParentBaseObserver;
use Magento\Framework\Event\Observer;
use Magento\Indexer\Model\Indexer\State as IndexerState;

abstract class BaseObserver extends ParentBaseObserver
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var IndexerState $state */
        $state = $observer->getEvent()->getIndexerState();

        $this->eventEmitter->send($this->getFullEventName(), $this->getIndexerStateData($state));
    }

    /**
     * Get indexer state data
     *
     * @return array
     * @var IndexerState $state
     */
    public function getIndexerStateData($state)
    {
        return [
            'index' => $state->getIndexerId(),
            'status' => $state->getStatus()
        ];
    }
}
