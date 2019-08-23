<?php
namespace Bitbull\AWSEventBridge\Observer\Customer;

use Bitbull\AWSEventBridge\Observer\BaseObserver;
use Magento\Framework\Event\Observer;

class SignedUp extends BaseObserver
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        if ($customer === null) {
            return;
        }
        $this->eventEmitter->send($this->getFullEventName(), [
            'defaultBilling' => $customer->getDefaultBilling(),
            'defaultShipping' => $customer->getDefaultShipping(),
            'createdAt' => $customer->getCreatedAt(),
            'email' => $customer->getEmail(),
            'firstname' => $customer->getFirstname(),
            'gender' => $customer->getGender(),
            'id' => $customer->getId(),
            'lastname' => $customer->getLastname(),
            'middlename' => $customer->getMiddlename(),
            'prefix' => $customer->getPrefix(),
            'suffix' => $customer->getSuffix(),
            'addresses' => $customer->getAddresses(),
        ]);
    }
}
