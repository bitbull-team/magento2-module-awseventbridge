<?php declare(strict_types=1);

namespace Bitbull\AWSEventBridge\Model\Service;

use Bitbull\AWSEventBridge\Api\Service\ConfigInterface;
use Magento\Config\App\Config\Type\System;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\CacheInterface;

class Config implements ConfigInterface
{
    const XML_PATH_ACCESS_KEY = 'aws_eventbridge/credentials/access_key';
    const XML_PATH_SECRET_ACCESS_KEY = 'aws_eventbridge/credentials/secret_access_key';
    const XML_PATH_REGION = 'aws_eventbridge/options/region';
    const XML_PATH_SOURCE = 'aws_eventbridge/options/source';
    const XML_PATH_EVENT_BUS = 'aws_eventbridge/options/event_bus';
    const XML_PATH_DEBUG_MODE = 'aws_eventbridge/options/debug_mode';
    const XML_PATH_CLOUDWATCH_EVENT = 'aws_eventbridge/options/cloudwatch_event_fallback';
    const XML_PATH_DRY_RUN_MODE = 'aws_eventbridge/options/dry_run_mode';

    const XML_PATH_EVENT_PREFIX = 'aws_eventbridge/events_';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param CacheInterface $cache
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        CacheInterface $cache)
    {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->cache = $cache;
    }

    /**
     * @inheritdoc
     */
    public function getRegion()
    {
        $region = $this->scopeConfig->getValue(self::XML_PATH_REGION, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($region === null) {
            $region = 'us-east-1';
        }
        return $region;
    }

    /**
     * @inheritdoc
     */
    public function getCredentials()
    {
        return [
            'key' => (string) $this->scopeConfig->getValue(self::XML_PATH_ACCESS_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'secret' => (string) $this->scopeConfig->getValue(self::XML_PATH_SECRET_ACCESS_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getSource()
    {
        $customSource = $this->scopeConfig->getValue(self::XML_PATH_SOURCE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($customSource !== null) {
            return $customSource;
        }

        $storeUrl = $this->storeManager->getStore()->getBaseUrl();
        if ($storeUrl !== null) {
            return parse_url($storeUrl, PHP_URL_HOST);
        }

        return $_SERVER['HTTP_HOST'] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getEventBusName()
    {
        return  $this->scopeConfig->getValue(self::XML_PATH_EVENT_BUS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @inheritdoc
     */
    public function isCloudWatchEventFallbackEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CLOUDWATCH_EVENT);
    }

    /**
     * @inheritdoc
     */
    public function isDebugModeEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_DEBUG_MODE);
    }

    /**
     * @inheritdoc
     */
    public function isDryRunModeEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_DRY_RUN_MODE);
    }

    /**
     * @inheritdoc
     */
    public function isEventEnabled($eventName, $scopeName = null)
    {
        // Search for pre-cached value to avoid config name transformation
        $cacheKey = self::XML_PATH_EVENT_PREFIX  . $scopeName . $eventName;
        $cachedFlag = $this->cache->load($cacheKey);
        if ($cachedFlag !== false && is_string($cachedFlag) === true) {
            return $cachedFlag === 'enabled';
        }

        // Check if event config is enabled
        $eventName = strtolower(preg_replace('/(?<!^|\\\)[A-Z]/', '_$0', $eventName)); // convert from CamelCase to snake_case
        $scopeName = $scopeName !== null ? strtolower(preg_replace('/(?<!^|\\\)[A-Z]/', '_$0', $scopeName)) : null; // convert from CamelCase to snake_case if not null
        $configName = $scopeName === null ? $eventName : "$scopeName/$eventName"; // concatenate scope and event name
        $isEnable = $this->scopeConfig->isSetFlag(self::XML_PATH_EVENT_PREFIX  . $configName);

        // Store in cache
        $this->cache->save($isEnable === true ? 'enabled' : 'disabled', $cacheKey, [
            \Magento\Framework\App\Config::CACHE_TAG
        ]);

        // Return value
        return $isEnable;
    }
}
