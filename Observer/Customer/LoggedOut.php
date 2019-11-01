<?php
namespace Bitbull\AWSEventBridge\Observer\Customer;

use Bitbull\AWSEventBridge\Api\Service\ConfigInterface;
use Bitbull\AWSEventBridge\Api\Service\LoggerInterface;
use Bitbull\AWSEventBridge\Model\Service\EventEmitter;
use Bitbull\AWSEventBridge\Observer\BaseObserver;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\Observer;
use \Magento\Backend\Model\Auth\Session as AuthSession;

class LoggedOut extends BaseObserver
{
    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * LoggedIn constructor.
     * @param LoggerInterface $logger
     * @param ConfigInterface $config
     * @param EventEmitter $eventEmitter
     * @param CustomerSession $customerSession
     */
    public function __construct(LoggerInterface $logger, ConfigInterface $config, EventEmitter $eventEmitter, CustomerSession $customerSession)
    {
        parent::__construct($logger, $config, $eventEmitter);
        $this->customerSession = $customerSession;
    }
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $currentCustomerUser = $this->customerSession->getCustomer();
        if ($currentCustomerUser->getId() !== null) {
            $this->eventEmitter->send($this->getFullEventName(), [
                'id' => $currentCustomerUser->getId(),
                'email' => $currentCustomerUser->getEmail()
            ]);
        } else {
            $this->logger->warn('No customer data in session, skipping ' . $this->getEventName());
        }
    }
}
