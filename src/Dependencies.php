<?php

namespace Epayco\Woocommerce;

use Epayco\Woocommerce\Admin\Settings;
use Epayco\Woocommerce\Configs\Seller;
use Epayco\Woocommerce\Configs\Store;
use Epayco\Woocommerce\Helpers\PaymentMethods;
use Epayco\Woocommerce\Hooks\Order;
use Epayco\Woocommerce\Hooks\OrderMeta;
use Epayco\Woocommerce\Hooks\Plugin;
use Epayco\Woocommerce\Hooks\Admin;
use Epayco\Woocommerce\Hooks\Blocks;
use Epayco\Woocommerce\Hooks\Checkout;
use Epayco\Woocommerce\Hooks\Endpoints;
use Epayco\Woocommerce\Hooks\Scripts;
use Epayco\Woocommerce\Hooks\Gateway;
use Epayco\Woocommerce\Hooks\Options;
use Epayco\Woocommerce\Helpers\Session;
use Epayco\Woocommerce\Helpers\Strings;
use Epayco\Woocommerce\Hooks\Template;
use Epayco\Woocommerce\Helpers\Cache;
use Epayco\Woocommerce\Helpers\Cron;
use Epayco\Woocommerce\Helpers\CurrentUser;
use Epayco\Woocommerce\Helpers\Gateways;
use Epayco\Woocommerce\Helpers\Url;
use Epayco\Woocommerce\Funnel\Funnel;
use Epayco\Woocommerce\Order\OrderMetadata;
use Epayco\Woocommerce\Translations\AdminTranslations;
use Epayco\Woocommerce\Translations\StoreTranslations;
use WooCommerce;
class Dependencies
{
    public WooCommerce $woocommerce;

    public Settings $settings;

    public Admin $adminHook;
    public Blocks $blocksHook;
    public Checkout $checkoutHook;
    public Endpoints $endpointsHook;

    public Seller $sellerConfig;

    public Store $storeConfig;
    public Session $sessionHelper;
    public PaymentMethods $paymentMethodsHelper;
    public Strings $stringsHelper;

    public Scripts $scriptsHook;
    public Template $templateHook;
    public Cache $cacheHelper;

    public Cron $cronHelper;
    public Gateway $gatewayHook;

    public Order $orderHook;
    public OrderMeta $orderMetaHook;
    public Plugin $pluginHook;

    public Url $urlHelper;

    public Hooks $hooks;

    public Helpers $helpers;
    public CurrentUser $currentUserHelper;
    public Gateways $gatewaysHelper;
    public Options $optionsHook;

    public OrderMetadata $orderMetadata;

    public AdminTranslations $adminTranslations;

    public StoreTranslations $storeTranslations;

    public Funnel $funnel;

    /**
     * Dependencies constructor
     */
    public function __construct()
    {
        global $woocommerce;

        $this->woocommerce             = $woocommerce;
        $this->adminHook               = new Admin();
        $this->blocksHook              = new Blocks();
        $this->endpointsHook           = new Endpoints();
        $this->optionsHook             = new Options();
        $this->orderMetaHook           = new OrderMeta();
        $this->templateHook            = new Template();
        $this->pluginHook              = new Plugin();
        $this->checkoutHook            = new Checkout();
        $this->cacheHelper             = new Cache();
        $this->sessionHelper           = new Session();
        $this->stringsHelper           = new Strings();
        $this->orderMetadata           = $this->setOrderMetadata();
        $this->storeConfig             = $this->setStore();
        $this->sellerConfig            = $this->setSeller();
        $this->urlHelper               = $this->setUrl();
        $this->paymentMethodsHelper    = $this->setPaymentMethods();
        $this->scriptsHook             = $this->setScripts();
        $this->adminTranslations       = new AdminTranslations();
        $this->storeTranslations       = new StoreTranslations();
        $this->gatewaysHelper          = $this->setGatewaysHelper();
        $this->funnel                  = $this->setFunnel();
        $this->gatewayHook             = $this->setGateway();
        $this->cronHelper              = new Cron();
        $this->currentUserHelper       = new CurrentUser();
        $this->orderHook               = $this->setOrder();
        $this->settings                = $this->setSettings();
        $this->hooks                   = $this->setHooks();
        $this->helpers                 = $this->setHelpers();
    }

    /**
     * @return Order
     */
    private function setOrder(): Order
    {
        return new Order(
            $this->templateHook,
            $this->orderMetadata,
            $this->adminTranslations,
            $this->storeTranslations,
            $this->storeConfig,
            $this->sellerConfig,
            $this->scriptsHook,
            $this->urlHelper,
            $this->endpointsHook,
            $this->cronHelper,
            $this->currentUserHelper,
        );
    }

    /**
     * @return Settings
     */
    private function setSettings(): Settings
    {
        return new Settings(
            $this->adminHook,
            $this->endpointsHook,
            $this->orderHook,
            $this->pluginHook,
            $this->scriptsHook,
            $this->sellerConfig,
            $this->storeConfig,
            $this->adminTranslations,
            $this->urlHelper,
            $this->currentUserHelper,
            $this->funnel,
        );
    }

    /**
     * @return Hooks
     */
    private function setHooks(): Hooks
    {
        return new Hooks(
            $this->adminHook,
            $this->blocksHook,
            $this->checkoutHook,
            $this->endpointsHook,
            $this->gatewayHook,
            $this->optionsHook,
            $this->orderHook,
            $this->pluginHook,
            $this->scriptsHook,
            $this->templateHook
        );
    }

    private function setHelpers(): Helpers
    {
        return new Helpers(
            $this->paymentMethodsHelper,
            $this->sessionHelper,
            $this->urlHelper
        );
    }

    /**
     * @return Scripts
     */
    private function setScripts(): Scripts
    {
        return new Scripts($this->urlHelper);
    }

    /**
     * @return Url
     */
    private function setUrl(): Url
    {
        return new Url($this->stringsHelper);
    }

    /**
     * @return Gateway
     */
    private function setGateway(): Gateway
    {
        return new Gateway(
            $this->storeConfig,
            $this->urlHelper,
            $this->funnel
        );
    }

    /**
     * @return Funnel
     */
    private function setFunnel(): Funnel
    {
        return new Funnel(
            $this->gatewaysHelper
        );
    }

    /**
     * @return Gateways
     */
    private function setGatewaysHelper(): Gateways
    {
        return new Gateways(
            $this->storeConfig
        );
    }

    /**
     * @return OrderMetadata
     */
    private function setOrderMetadata(): OrderMetadata
    {
        return new OrderMetadata($this->orderMetaHook);
    }

    /**
     * @return Store
     */
    private function setStore(): Store
    {
        return new Store($this->optionsHook);
    }

    /**
     * @return Seller
     */
    private function setSeller(): Seller
    {
        return new Seller(
            $this->cacheHelper,
            $this->optionsHook
        );
    }

    /**
     * @return PaymentMethods
     */
    private function setPaymentMethods(): PaymentMethods
    {
        return new PaymentMethods($this->urlHelper);
    }


}