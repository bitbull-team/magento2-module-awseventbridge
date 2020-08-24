<?php

namespace Bitbull\AWSEventBridge\Observer\User;

use Bitbull\AWSEventBridge\Api\Service\ConfigInterface;
use Bitbull\AWSEventBridge\Api\Service\LoggerInterface;
use Bitbull\AWSEventBridge\Model\Service\EventEmitter;
use Bitbull\AWSEventBridge\Observer\BaseObserver as ParentBaseObserver;
use Magento\Backend\Model\Auth\Session as BackendUserSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\Observer;

abstract class BaseObserver extends ParentBaseObserver
{
    /**
     * @var BackendUserSession
     */
    protected $backendUserSession;

    /**
     * LoggedIn constructor.
     * @param LoggerInterface $logger
     * @param ConfigInterface $config
     * @param EventEmitter $eventEmitter
     * @param BackendUserSession $backendUserSession
     */
    public function __construct(LoggerInterface $logger, ConfigInterface $config, EventEmitter $eventEmitter, BackendUserSession $backendUserSession)
    {
        parent::__construct($logger, $config, $eventEmitter);
        $this->backendUserSession = $backendUserSession;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Customer\Model\Data\Customer $user */
        $user = $observer->getEvent()->getUser();
        if ($user === null) {
            $user = $this->backendUserSession->getUser();
            if ($user->getId() === null) {
                $this->logger->warn('No admin user data in session, skipping ' . $this->getEventName());
                return;
            }
        }

        $this->eventEmitter->send($this->getFullEventName(), $this->getUserData($user));
    }

    /**
     * Get user data
     *
     * @return array
     * @var \Magento\User\Model\User $user
     */
    public function getUserData($user)
    {
        return [
            'id' => $user->getId(),
            'username' => $user->getUserName(),
            'email' => $user->getEmail()
        ];
    }
}
