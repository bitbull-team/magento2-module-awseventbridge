<?php declare(strict_types=1);

namespace Bitbull\AWSEventBridge\Model\Service;

use Bitbull\AWSEventBridge\Api\Service\LoggerInterface;
use Bitbull\AWSEventBridge\Api\Service\TrackingInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Header;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Request\Http as RequestHttp;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Backend\Model\Auth\Session as BackendUserSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\State as AppState;

class Tracking implements TrackingInterface
{
    const MODULE_NAME = 'bitbull/magento2-module-awseventbridge';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var BackendUserSession
     */
    protected $backendUserSession;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var RequestHttp
     */
    protected $request;

    /**
     * @var Header
     */
    protected $httpHeader;

    /**
     * @var UrlInterface
     */
    protected $url;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var AppState
     */
    protected $appState;

    /**
     * @var boolean
     */
    protected $isInConsole = false;

    /**
     * @var mixed
     */
    protected $cachedParameter = null;

    /**
     * Tracking constructor.
     *
     * @param LoggerInterface $logger
     * @param ProductMetadataInterface $productMetadata
     * @param RequestHttp $request
     * @param Header $httpHeader
     * @param UrlInterface $url
     * @param StoreManagerInterface $storeManager
     * @param AppState $appState
     * @param BackendUserSession $backendUserSession
     * @param CustomerSession $customerSession
     */
    public function __construct(
        LoggerInterface $logger,
        ProductMetadataInterface $productMetadata,
        RequestHttp $request,
        Header $httpHeader,
        UrlInterface $url,
        StoreManagerInterface $storeManager,
        AppState $appState,
        BackendUserSession $backendUserSession,
        CustomerSession $customerSession
    ) {
        $this->logger = $logger;
        $this->productMetadata = $productMetadata;
        $this->request = $request;
        $this->httpHeader = $httpHeader;
        $this->url = $url;
        $this->storeManager = $storeManager;
        $this->backendUserSession = $backendUserSession;
        $this->customerSession = $customerSession;

        try {
            $appState->getAreaCode();
        } catch (LocalizedException $e) {
            $this->isInConsole = true;
        }

    }

    /**
     * @inheritdoc
     */
    public function getModuleVersion()
    {
        $version = 'undefined';
        try {
            $reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);
            $vendorDir = dirname($reflection->getFileName(), 2);
            $packages = json_decode(file_get_contents($vendorDir . '/composer/installed.json'), true);
            foreach ($packages as $package) {
                if ($package['name'] === self::MODULE_NAME) {
                    $version = $package['version'];
                    break;
                }
            }
        } catch (\Exception $e) {
            $version = 'error: ' . $e->getMessage();
        }
        return $version;
    }

    /**
     * @inheritdoc
     */
    public function getPHPVersion()
    {
        return PHP_VERSION;
    }

    /**
     * @inheritdoc
     */
    public function getMagentoVersion()
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * @inheritdoc
     */
    public function getRemoteAddr()
    {
        $remoteAddr = $this->request->getClientIp(true);
        if ($remoteAddr !== null && strpos($remoteAddr, ',') !== false){
            $remoteAddrParts = explode(',', $remoteAddr);
            $remoteAddr = $remoteAddrParts[0];
        }
        return $remoteAddr;
    }

    /**
     * @inheritdoc
     */
    public function getUserAgent()
    {
        $userAgent = $this->httpHeader->getHttpUserAgent();
        if ($userAgent === null) {
            $userAgent = 'unknown';
        }
        return $userAgent;
    }

    /**
     * @inheritDoc
     */
    public function getCurrentUserName() {
        if ($this->isInConsole === true) {
            return posix_getpwuid(posix_geteuid());
        }

        $currentCustomerUser = $this->customerSession->getCustomer();
        if ($currentCustomerUser->getId() !== null) {
            return [
                'id' => $currentCustomerUser->getId(),
                'email' => $currentCustomerUser->getEmail()
            ];
        }

        $currentAdminUser = $this->backendUserSession->getUser();
        if ($currentAdminUser !== null) {
            return [
                'id' => $currentAdminUser->getId(),
                'username' => $currentAdminUser->getUserName(),
                'email' => $currentAdminUser->getEmail()
            ];
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getCurrentStoreId() {
        try {
            $currentStore = $this->storeManager->getStore();
        } catch (NoSuchEntityException $e) {
            return null;
        }
        if ($currentStore === null) {
            return null;
        }

        return $currentStore->getId();
    }

    /**
     * @inheritdoc
     */
    public function getTrackingParams()
    {
        // Can be cached in memory during the request, the client is the same
        if ($this->cachedParameter !== null) {
            return $this->cachedParameter;
        }

        // Elaborate tracking parameters
        $this->cachedParameter = [
            'transport' => $this->isInConsole === false ? 'HTTP' : 'SHELL',
            'hostname' => gethostname(),
            'time' => round(microtime(true) * 1000),
            'storeId' => $this->getCurrentStoreId(),
            'version' => [
                'module' => $this->getModuleVersion(),
                'php' => $this->getPHPVersion(),
                'magento' => $this->getMagentoVersion()
            ],
            'user' => $this->getCurrentUserName()
        ];

        // If the request is HTTP add IP and user agent
        if ($this->isInConsole === false) {
            $this->cachedParameter['ip'] = $this->getRemoteAddr();
            $this->cachedParameter['userAgent'] = $this->getUserAgent();
        }

        return $this->cachedParameter;
    }
}
