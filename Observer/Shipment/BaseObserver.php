<?php

namespace Bitbull\AWSEventBridge\Observer\Shipment;

use Bitbull\AWSEventBridge\Observer\BaseObserver as ParentBaseObserver;
use Magento\Framework\Event\Observer;
use Magento\Sales\Api\Data\CommentInterface;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Magento\Sales\Api\Data\TrackInterface;

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

        if ($shipment === null) {
            /** @var \Magento\Sales\Api\Data\ShipmentTrackInterface $track */
            $track = $observer->getEvent()->getTrack();
            if ($track === null) {
                $this->logger->warn('Cannot find shipment or track for event ' . $this->getFullEventName());
                return;
            }
            $shipment = $track->getShipment();
        }

        $this->eventEmitter->send($this->getFullEventName(), $this->getShipmentData($shipment));
    }

    /**
     * Get order data
     *
     * @return array
     * @var \Magento\Sales\Api\Data\ShipmentInterface $shipment
     */
    public function getShipmentData($shipment)
    {
        return [
            'id' => $shipment->getIncrementId(),
            'tracks' => array_map(static function ($track) {

                /** @var TrackInterface $track */
                return [
                    'title' => $track->getTitle(),
                    'carrier' => $track->getCarrierCode(),
                    'number' => $track->getTrackNumber(),
                ];
            }, $shipment->getTracks()),
            'comments' => array_map(static function ($comment) {

                /** @var CommentInterface $comment */
                return $comment->getComment();
            }, $shipment->getComments()),
            'qty' => $shipment->getTotalQty(),
            'weight' => $shipment->getTotalWeight(),
            'items' => array_map(static function ($item) {

                /** @var ShipmentItemInterface $item */
                return [
                    'sku' => $item->getSku(),
                    'name' => $item->getName(),
                    'price' => $item->getPrice(),
                    'qty' => $item->getQty()
                ];
            }, $shipment->getItems())
        ];
    }
}
