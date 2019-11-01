<?php
namespace Bitbull\AWSEventBridge\Observer\User;

use Bitbull\AWSEventBridge\Api\Service\ConfigInterface;
use Bitbull\AWSEventBridge\Api\Service\LoggerInterface;
use Bitbull\AWSEventBridge\Model\Service\EventEmitter;
use Bitbull\AWSEventBridge\Observer\BaseObserver;
use Magento\Framework\Event\Observer;
use Magento\Backend\Model\Auth\Session as BackendUserSession;

class LoggedIn extends BaseObserver
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
        $currentAdminUser = $this->backendUserSession->getUser();
        if ($currentAdminUser !== null) {
            $this->eventEmitter->send($this->getFullEventName(), [
                'id' => $currentAdminUser->getId(),
                'username' => $currentAdminUser->getUserName(),
                'email' => $currentAdminUser->getEmail()
            ]);
        } else {
            $this->logger->warn('No admin user data in session, skipping ' . $this->getEventName());
        }
    }
}
