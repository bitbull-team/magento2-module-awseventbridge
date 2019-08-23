<?php declare(strict_types=1);

namespace Bitbull\AWSEventBridge\Api\Service;

interface ConfigInterface
{
    /**
     * Get region
     *
     * @return array
     */
    public function getRegion();

    /**
     * Get credentials
     *
     * @return array
     */
    public function getCredentials();

    /**
     * Get event source
     *
     * @return array
     */
    public function getSource();

    /**
     * Get event bus name
     *
     * @return array
     */
    public function getEventBusName();

    /**
     * Check if CloudWatchEvent fallback is enabled
     *
     * @return boolean
     */
    public function isCloudWatchEventFallbackEnabled();

    /**
     * Check if debug mode is enabled
     *
     * @return boolean
     */
    public function isDebugModeEnabled();

    /**
     * Check if dry run mode is enabled
     *
     * @return boolean
     */
    public function isDryRunModeEnabled();

    /**
     * Check if event is enabled
     *
     * @param  string $eventName
     * @param  string $scope
     * @return boolean
     */
    public function isEventEnabled($eventName, $scope = 'global');
}
