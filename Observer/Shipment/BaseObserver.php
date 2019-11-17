<?php
namespace Bitbull\AWSEventBridge\Observer\Shipment;

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
        /** @var \Magento\Sales\Api\Data\ShipmentInterface $shipment */
        $shipment = $observer->getEvent()->getShipment();

        $this->eventEmitter->send($this->getFullEventName(), $this->getShipmentData($shipment));
    }

    /**
     * Get order data
     *
     * @var \Magento\Sales\Api\Data\ShipmentInterface $shipment
     * @return array
     */
    public function getShipmentData($shipment) {
        return [
            'id' => $shipment->getIncrementId(),
            'comments' => $shipment->getComments(),
            'qty' => $shipment->getTotalQty(),
            'weight' => $shipment->getTotalWeight(),
            'items' => array_map(function ($item) {

                /** @var \Magento\Sales\Api\Data\ShipmentItemInterface $item */
                return [
                    'sku' => $item->getSku(),
                    'name' => $item->getName(),
                    'price' => $item->getPrice(),
                    'qty' => $item->getQty()
                ];
            }, $shipment->getItems()->getItems()) // not a typo, first getItems return a collection, not an array
        ];
    }
}
