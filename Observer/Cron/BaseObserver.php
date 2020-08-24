<?php

namespace Bitbull\AWSEventBridge\Observer\Cron;

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
        $job = $observer->getData();
        $this->eventEmitter->send($this->getFullEventName(), $this->getJobData($job));
    }

    /**
     * Get job data
     *
     * @return array
     * @var array $job
     */
    public function getJobData($job)
    {
        return [
            'code' => $job['code'],
            'groupId' => $job['groupId'],
            'jobConfig' => $job['jobConfig'],
            'duration' => $job['duration'] ?? null,
            'error' => $job['error'] ?? null
        ];
    }
}
