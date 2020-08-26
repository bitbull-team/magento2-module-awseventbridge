<?php declare(strict_types=1);

namespace Bitbull\AWSEventBridge\Api\Service;

interface TrackingInterface
{
    /**
     * Get profiling params to identify caller
     *
     * @return array
     */
    public function getTrackingParams();

    /**
     * Get current PHP version
     *
     * @return string
     */
    function getPHPVersion();

    /**
     * Get current Magento version
     *
     * @return string
     */
    public function getMagentoVersion();

    /**
     * Get current Magento edition
     *
     * @return string "Community" or "Enterprise"
     */
    public function getMagentoEdition();

    /**
     * Get current installed module version
     *
     * @return string
     */
    public function getModuleVersion();

    /**
     * Get client IP
     *
     * @return string
     */
    public function getRemoteAddr();

    /**
     * Get client user agent
     *
     * @return string
     */
    public function getUserAgent();

    /**
     * Get current user name
     *
     * @return string|null
     */
    public function getCurrentUserName();

    /**
     * Get current store id
     *
     * @return string|null
     */
    public function getCurrentStoreId();
}
