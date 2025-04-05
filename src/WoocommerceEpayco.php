<?php

namespace Epayco\Woocommerce;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Epayco\Woocommerce\Admin\Settings;
use Epayco\Woocommerce\Blocks\CreditCardBlock;
use Epayco\Woocommerce\Blocks\CheckoutBlock;
use Epayco\Woocommerce\Blocks\PseBlock;
use Epayco\Woocommerce\Blocks\TicketBlock;
use Epayco\Woocommerce\Blocks\DaviplataBlock;
use Epayco\Woocommerce\Configs\Store;
use Epayco\Woocommerce\Configs\Seller;
use Epayco\Woocommerce\Blocks\SubscriptionBlock;
use Epayco\Woocommerce\Helpers\Paths;
use Epayco\Woocommerce\Helpers\Strings;
use Epayco\Woocommerce\Funnel\Funnel;
use Epayco\Woocommerce\Order\OrderMetadata;
use Epayco\Woocommerce\Translations\AdminTranslations;
use Epayco\Woocommerce\Translations\StoreTranslations;
use WooCommerce;

if (!defined('ABSPATH')) {
    exit;
}

class WoocommerceEpayco
{
    private const PLUGIN_VERSION = '4.0.0';
    private const PLATFORM_NAME = 'woocommerce';
    private const TICKET_TIME_EXPIRATION = 3;
    private const PLUGIN_NAME = 'Plugin_ePayco_WooCommerce/woocommerce-epayco.php';

    public WooCommerce $woocommerce;

    public Hooks $hooks;

    public Settings $settings;

    public Helpers $helpers;

    public OrderMetadata $orderMetadata;

    public AdminTranslations $adminTranslations;

    public StoreTranslations $storeTranslations;

    public Store $storeConfig;

    public Seller $sellerConfig;

    public Funnel $funnel;

    /**
     * WoocommerceEpayco constructor
     */
    public function __construct()
    {
        $this->defineConstants();
        $this->loadPluginTextDomain();
        $this->registerHooks();
    }

    /**
     * Load plugin text domain
     *
     * @return void
     */
    public function loadPluginTextDomain(): void
    {
        $textDomain = $this->pluginMetadata('text-domain');
        unload_textdomain($textDomain);
        $locale = explode('_', apply_filters('plugin_locale', get_locale(), $textDomain))[0];
        $locale = apply_filters('plugin_locale', get_locale(), $textDomain);
        load_textdomain($textDomain, Paths::basePath(Paths::join($this->pluginMetadata('domain-path'), "woo-epayco-api-$locale.mo")));
    }

    /**
     * Register hooks
     *
     * @return void
     */
    public function registerHooks(): void
    {
        add_action('plugins_loaded', [$this, 'init']);
    }

    /**
     * Register gateways
     *
     * @return void
     */
    public function registerGateways(): void
    {

        $methods = [
                'Epayco\Woocommerce\Gateways\TicketGateway',
                'Epayco\Woocommerce\Gateways\DaviplataGateway',
                'Epayco\Woocommerce\Gateways\CreditCardGateway',
                'Epayco\Woocommerce\Gateways\PseGateway',
                'Epayco\Woocommerce\Gateways\CheckoutGateway',
        ];
        if (class_exists('WC_Subscriptions')){
            array_push($methods, 'Epayco\Woocommerce\Gateways\SubscriptionGateway');
        }
        foreach ($methods as $gateway) {
            $this->hooks->gateway->registerGateway($gateway);
        }
    }

    /**
     * Init plugin
     *
     * @return void
     */
    public function init(): void
    {
        if (!class_exists('WC_Payment_Gateway')) {
            $this->adminNoticeMissWoocoommerce();
            return;
        }

        if (!class_exists('WC_Subscriptions_Cart')) {
            //$this->adminNoticeMissWoocoommerceSubscription();
            //return;
        }

        $this->setProperties();
        $this->setPluginSettingsLink();
        $this->registerBlocks();
        $this->registerGateways();

        $this->hooks->gateway->registerAvailablePaymentGateway();

        $this->hooks->gateway->registerSaveCheckoutSettings();
        if ($this->storeConfig->getExecuteActivate()) {
            $this->hooks->plugin->executeActivatePluginAction();
        }
        if ($this->storeConfig->getExecuteAfterPluginUpdate()) {
            $this->afterPluginUpdate();
        }
    }


    /**
     * Register woocommerce blocks support
     *
     * @return void
     */
    public function registerBlocks(): void
    {
        if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function (PaymentMethodRegistry $payment_method_registry) {
                    $payment_method_registry->register(new CheckoutBlock());
                    $payment_method_registry->register(new CreditCardBlock());
                    $payment_method_registry->register(new SubscriptionBlock());
                    $payment_method_registry->register(new DaviplataBlock());
                    $payment_method_registry->register(new PseBlock());
                    $payment_method_registry->register(new TicketBlock());
                }
            );
        }
    }

    /**
     * Define plugin constants
     *
     * @return void
     */
    private function defineConstants(): void
    {
        $this->define('EP_VERSION', self::PLUGIN_VERSION);
        $this->define('EP_PLATFORM_NAME', self::PLATFORM_NAME);
        $this->define('EP_TICKET_DATE_EXPIRATION', self::TICKET_TIME_EXPIRATION);
        $this->define('EP_PLUGIN_URL',sprintf('%s%s', plugin_dir_url(__FILE__), '../assets/json/'));
    }

    /**
     * Function hook disabled plugin
     *
     * @return void
     */
    public function disablePlugin()
    {
        $this->funnel->updateStepDisable();
    }

    /**
     * Function hook active plugin
     */
    public function activatePlugin(): void
    {
        $after = fn() => $this->storeConfig->setExecuteActivate(false);

        $this->funnel->created() ? $this->funnel->updateStepActivate($after) : $this->funnel->create($after);
    }

    /**
     * Function hook after plugin update
     */
    public function afterPluginUpdate(): void
    {
        $this->funnel->updateStepPluginVersion(fn() => $this->storeConfig->setExecuteAfterPluginUpdate(false));
    }

    /**
     * Set plugin properties
     *
     * @return void
     */
    public function setProperties(): void
    {
        $dependencies = new Dependencies();

        // Globals
        $this->woocommerce = $dependencies->woocommerce;

        // Configs
        $this->storeConfig    = $dependencies->storeConfig;
        $this->sellerConfig   = $dependencies->sellerConfig;

        // Order
        $this->orderMetadata = $dependencies->orderMetadata;

        // Helpers
        $this->helpers = $dependencies->helpers;

        // Translations
        $this->adminTranslations = $dependencies->adminTranslations;
        $this->storeTranslations = $dependencies->storeTranslations;

        // Hooks
        $this->hooks = $dependencies->hooks;

        // Exclusive
        $this->settings = $dependencies->settings;

        $this->funnel = $dependencies->funnel;
    }

    /**
     * Set plugin configuration links
     *
     * @return void
     */
    public function setPluginSettingsLink()
    {
        $pluginLinks = [
            [
                'text'   => $this->adminTranslations->plugin['set_plugin'],
                'href'   => admin_url('admin.php?page=epayco-settings'),
                'target' => $this->hooks->admin::HREF_TARGET_DEFAULT,
            ],
            [
                'text'   => $this->adminTranslations->plugin['payment_method'],
                'href'   => admin_url('admin.php?page=wc-settings&tab=checkout'),
                'target' => $this->hooks->admin::HREF_TARGET_DEFAULT,
            ],
        ];

        $this->hooks->admin->registerPluginActionLinks(self::PLUGIN_NAME, $pluginLinks);
    }

    /**
     * Define constants
     *
     * @param $name
     * @param $value
     *
     * @return void
     */
    private function define($name, $value): void
    {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    /**
     * Show woocommerce missing notice
     * This function should use WordPress features only
     *
     * @return void
     */
    public function adminNoticeMissWoocoommerceSubscription(): void
    {
        $url_docs = 'https://github.com/wp-premium/woocommerce-subscriptions';
        $subs = __( 'Subscription ePayco: Woocommerce subscriptions must be installed and active, ') . sprintf(__('<a target="_blank" href="%s">'. __('check documentation for help') .'</a>'), $url_docs);
        add_action(
            'admin_notices',
            function() use($subs) {
                $this->subscription_epayco_se_notices($subs);
            }
        );
    }

    public function subscription_epayco_se_notices( $notice ): void
    {
        ?>
        <div class="error notice">
            <p><?php echo $notice; ?></p>
        </div>
        <?php
    }

    /**
     * Show woocommerce missing notice
     * This function should use WordPress features only
     *
     * @return void
     */
    public function adminNoticeMissWoocoommerce(): void
    {
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_style('woocommerce-epayco-admin-notice-css');
            wp_register_style(
                'woocommerce-epayco-admin-notice-css',
                sprintf('%s%s', plugin_dir_url(__FILE__), '../assets/css/admin/ep-admin-notices.css'),
                false,
                EP_VERSION
            );
        });

        add_action(
            'admin_notices',
            function () {
                $strings = new Strings();
                $allowedHtmlTags = $strings->getAllowedHtmlTags();
                $isInstalled = false;
                $currentUserCanInstallPlugins = current_user_can('install_plugins');

                $minilogo     = sprintf('%s%s', plugin_dir_url(__FILE__), '../assets/images/minilogo.png');
                $translations = [
                    'activate_woocommerce' => __('Activate WooCommerce', 'woo-epayco-api'),
                    'install_woocommerce'  => __('Install WooCommerce', 'woo-epayco-api'),
                    'see_woocommerce'      => __('See WooCommerce', 'woo-epayco-api'),
                    'miss_woocommerce'     => sprintf(
                        __('Epayco module needs an active version of %s in order to work!', 'woo-epayco-api'),
                        '<a target="_blank" href="https://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a>'
                    ),
                ];

                $activateLink = wp_nonce_url(
                    self_admin_url('plugins.php?action=activate&plugin=woocommerce/woocommerce.php&plugin_status=all'),
                    'activate-plugin_woocommerce/woocommerce.php'
                );

                $installLink = wp_nonce_url(
                    self_admin_url('update.php?action=install-plugin&plugin=woocommerce'),
                    'install-plugin_woocommerce'
                );

                if (function_exists('get_plugins')) {
                    $allPlugins  = get_plugins();
                    $isInstalled = !empty($allPlugins['woocommerce/woocommerce.php']);
                }

                if ($isInstalled && $currentUserCanInstallPlugins) {
                    $missWoocommerceAction = 'active';
                } else {
                    if ($currentUserCanInstallPlugins) {
                        $missWoocommerceAction = 'install';
                    } else {
                        $missWoocommerceAction = 'see';
                    }
                }

                include dirname(__FILE__) . '/../templates/admin/notices/miss-woocommerce-notice.php';
            }
        );
    }

    /**
     * Plugin file metadata
     *
     * Metadata map:
     * ```
     * [
     *     'name'             => 'Plugin Name',
     *     'uri'              => 'Plugin URI',
     *     'description'      => 'Description',
     *     'version'          => 'Version',
     *     'author'           => 'Author',
     *     'author-uri'       => 'Author URI',
     *     'text-domain'      => 'Text Domain',
     *     'domain-path'      => 'Domain Path',
     *     'network'          => 'Network',
     *     'min-wp'           => 'Requires at least',
     *     'min-wc'           => 'WC requires at least',
     *     'min-php'          => 'Requires PHP',
     *     'tested-wc'        => 'WC tested up to',
     *     'update-uri'       => 'Update URI',
     *     'required-plugins' => 'Requires Plugins',
     * ]
     * ```
     *
     * @param string $key metadata desired element key
     *
     * @return string|string[] all data or just $key element value
     */
    public function pluginMetadata(?string $key = null)
    {
        $data = get_file_data(EP_PLUGIN_FILE, [
            'name' => 'Plugin Name',
            'uri' => 'Plugin URI',
            'description' => 'Description',
            'version' => 'Version',
            'author' => 'Author',
            'author-uri' => 'Author URI',
            'text-domain' => 'Text Domain',
            'domain-path' => 'Domain Path',
            'network' => 'Network',
            'min-wp' => 'Requires at least',
            'min-wc' => 'WC requires at least',
            'min-php' => 'Requires PHP',
            'tested-wc' => 'WC tested up to',
            'update-uri' => 'Update URI',
            'required-plugins' => 'Requires Plugins',
        ]);

        return isset($key) ? $data[$key] : $data;
    }
}