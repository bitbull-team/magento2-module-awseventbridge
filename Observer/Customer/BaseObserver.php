<?php

namespace Bitbull\AWSEventBridge\Observer\Customer;

use Bitbull\AWSEventBridge\Api\Service\ConfigInterface;
use Bitbull\AWSEventBridge\Api\Service\LoggerInterface;
use Bitbull\AWSEventBridge\Model\Service\EventEmitter;
use Bitbull\AWSEventBridge\Observer\BaseObserver as ParentBaseObserver;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\Observer;

abstract class BaseObserver extends ParentBaseObserver
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
        /** @var \Magento\Customer\Model\Data\Customer $customer */
        $customer = $observer->getEvent()->getCustomer();
        if ($customer === null) {
            $customer = $this->customerSession->getCustomer();
            if ($customer->getId() === null) {
                $this->logger->warn('No customer data in session, skipping ' . $this->getEventName());
                return;
            }
        }

        $this->eventEmitter->send($this->getFullEventName(), $this->getCustomerData($customer));
    }

    /**
     * Get customer data
     *
     * @return array
     * @var \Magento\Customer\Model\Data\Customer $customer
     */
    public function getCustomerData($customer)
    {
        return [
            'id' => $customer->getId(),
            'email' => $customer->getEmail(),
            'createdAt' => $customer->getCreatedAt(),
            'firstName' => $customer->getFirstname(),
            'gender' => $customer->getGender(),
            'lastName' => $customer->getLastname(),
            'middleName' => $customer->getMiddlename(),
            'prefix' => $customer->getPrefix(),
            'suffix' => $customer->getSuffix()
        ];
    }
}
