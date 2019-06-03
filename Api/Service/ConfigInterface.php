<?php declare(strict_types=1);

namespace Bitbull\Mimmo\Api\Service;

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
     * Check if debug mode is enabled
     *
     * @return boolean
     */
    public function isDebugModeEnabled();

    /**
     * Check if event is enabled
     *
     * @param  string $eventName
     * @return boolean
     */
    public function isEventEnabled($eventName);
}
