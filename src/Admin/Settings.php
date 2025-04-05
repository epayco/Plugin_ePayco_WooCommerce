<?php

namespace Epayco\Woocommerce\Admin;

use Epayco\Woocommerce\Hooks\Order;
use Exception;
use Epayco\Woocommerce\Configs\Seller;
use Epayco\Woocommerce\Configs\Store;
use Epayco\Woocommerce\Hooks\Admin;
use Epayco\Woocommerce\Hooks\Endpoints;
use Epayco\Woocommerce\Hooks\Plugin;
use Epayco\Woocommerce\Hooks\Scripts;
use Epayco\Woocommerce\Helpers\CurrentUser;
use Epayco\Woocommerce\Helpers\Form;
use Epayco\Woocommerce\Helpers\Url;
use Epayco\Woocommerce\Translations\AdminTranslations;
use Epayco\Woocommerce\Funnel\Funnel;


if (!defined('ABSPATH')) {
    exit;
}

class Settings
{
    private const PRIORITY_ON_MENU = 90;
    private const NONCE_ID = 'ep_settings_nonce';
    private Admin $admin;
    private Endpoints $endpoints;
    private Order $order;
    private Plugin $plugin;
    private Scripts $scripts;
    private Seller $seller;
    private Store $store;
    private AdminTranslations $translations;
    private Url $url;
    private CurrentUser $currentUser;
    private Funnel $funnel;

    /**
     * Settings constructor
     *
     * @param Admin $admin
     * @param Endpoints $endpoints
     * @param Plugin $plugin
     * @param Scripts $scripts
     * @param Seller $seller
     * @param Store $store
     * @param AdminTranslations $translations
     * @param Url $url
     * @param CurrentUser $currentUser
     * @param Funnel $funnel
     */
    public function __construct(
        Admin $admin,
        Endpoints $endpoints,
        Order $order,
        Plugin $plugin,
        Scripts $scripts,
        Seller $seller,
        Store $store,
        AdminTranslations $translations,
        Url $url,
        CurrentUser $currentUser,
        Funnel $funnel
    )
    {
        $this->admin        = $admin;
        $this->endpoints    = $endpoints;
        $this->order        = $order;
        $this->plugin       = $plugin;
        $this->scripts      = $scripts;
        $this->seller       = $seller;
        $this->store        = $store;
        $this->translations = $translations;
        $this->url          = $url;
        $this->currentUser  = $currentUser;
        $this->funnel       = $funnel;

        $this->loadMenu();
        $this->loadScriptsAndStyles();
        $this->registerAjaxEndpoints();

        $this->plugin->registerOnPluginCredentialsUpdate(function () {
            $this->funnel->updateStepCredentials();
        });

        $this->plugin->registerOnPluginTestModeUpdate(function () {
            $this->funnel->updateStepPluginMode();
        });

        //$this->plugin->registerOnPluginStoreInfoUpdate(function () {
            $this->order->toggleSyncPendingStatusOrdersCron($this->store->getCronSyncMode());
        //});
    }

    /**
     * Load admin menu
     *
     * @return void
     */
    public function loadMenu(): void
    {
        $this->admin->registerOnMenu(self::PRIORITY_ON_MENU, [$this, 'registerEpaycoInWoocommerceMenu']);
    }

    /**
     * Load scripts and styles
     *
     * @return void
     */
    public function loadScriptsAndStyles(): void
    {
        if ($this->canLoadScriptsAndStyles()) {
            $this->scripts->registerAdminStyle(
                'epayco_settings_admin_css',
                $this->url->getCssAsset('admin/ep-admin-settings')
            );

            $this->scripts->registerAdminStyle(
                'epayco_admin_configs_css',
                $this->url->getCssAsset('admin/ep-admin-configs')
            );

            $this->scripts->registerAdminScript(
                'epayco_settings_admin_js',
                $this->url->getJsAsset('admin/ep-admin-settings'),
                [
                    'nonce'              => $this->generateNonce(self::NONCE_ID),
                    'show_advanced_text' => 'Show advanced options',
                    'hide_advanced_text' => 'Hide advanced options',
                ]
            );

        }

        if ($this->canLoadScriptsNoticesAdmin()) {
            $this->scripts->registerNoticesAdminScript();
        }
    }

    /**
     * Check if scripts notices can be loaded
     *
     * @return bool
     */
    public function canLoadScriptsNoticesAdmin(): bool
    {
        return $this->admin->isAdmin() && (
                $this->url->validateUrl('index') ||
                $this->url->validateUrl('plugins') ||
                $this->url->validatePage('wc-admin') ||
                $this->url->validatePage('wc-settings') ||
                $this->url->validatePage('epayco-settings')
            );
    }

    /**
     * Generate wp_nonce
     *
     * @param string $id
     *
     * @return string
     */
    public function generateNonce(string $id): string
    {
        $nonce = wp_create_nonce($id);
        if (!$nonce) {
            return '';
        }
        return $nonce;
    }

    /**
     * Register ajax endpoints
     *
     * @return void
     */
    public function registerAjaxEndpoints(): void
    {
        $this->endpoints->registerAjaxEndpoint('ep_update_test_mode', [$this, 'EpaycoUpdateTestMode']);
        $this->endpoints->registerAjaxEndpoint('ep_update_option_credentials', [$this, 'EpaycoUpdateOptionCredentials']);
        $this->endpoints->registerAjaxEndpoint('ep_get_payment_methods', [$this, 'EpaycoPaymentMethods']);
        $this->endpoints->registerAjaxEndpoint('ep_validate_credentials_tips', [$this, 'EpaycoValidateCredentialsTips']);
        $this->endpoints->registerAjaxEndpoint('ep_validate_payment_tips', [$this, 'EpaycoValidatePaymentTips']);
    }

    /**
     * Check if scripts and styles can be loaded
     *
     * @return bool
     */
    public function canLoadScriptsAndStyles(): bool
    {
        return $this->admin->isAdmin() && (
                $this->url->validatePage('epayco-settings') ||
                $this->url->validateSection('woo-epayco')
            );
    }

    /**
     * Add Epayco submenu to Woocommerce menu
     *
     * @return void
     */
    public function registerEpaycoInWoocommerceMenu(): void
    {
        $this->admin->registerSubmenuPage(
            'woocommerce',
            'Epayco  Settings',
            'Epayco',
            'manage_options',
            'epayco-settings',
            [$this, 'EpaycoSubmenuPageCallback']
        );
    }

    /**
     * Show plugin configuration page
     *
     * @return void
     */
    public function EpaycoSubmenuPageCallback(): void
    {
        $headerTranslations      = $this->translations->headerSettings;
        $credentialsTranslations = $this->translations->credentialsSettings;
        $gatewaysTranslations    = $this->translations->gatewaysSettings;
        $testModeTranslations    = $this->translations->testModeSettings;

        $pcustid   = $this->seller->getCredentialsPCustId();
        $publicKey   = $this->seller->getCredentialsPublicKeyPayment();
        $privateKey   = $this->seller->getCredentialsPrivateKeyPayment();
        $pKey   = $this->seller->getCredentialsPkey();
        $checkboxCheckoutTestMode  = $this->store->getCheckboxCheckoutTestMode();

        $testMode   = ($checkboxCheckoutTestMode === 'yes');

        $links      = [
            'epayco_credentials' =>'https://dashboard.epayco.io/configuration'
        ];
        $allowedHtmlTags = array(
            'br' => array(),
            'b'  => array(),
            'a'  => array(
                'href'   => array(),
                'target' => array(),
                'class'  => array(),
                'id'     => array()
            ),
            'span' => array(
                'id'      => array(),
                'class'   => array(),
                'onclick' => array()
            ),
            'div' => array(
                'id'      => array(),
                'class'   => array(),
            ),
            'p' => array(
                'id'      => array(),
                'class'   => array(),
                'style' => array()
            ),
        );

        include dirname(__FILE__) . '/../../templates/admin/settings/settings.php';
    }

    /**
     * Save test mode options
     *
     * @return void
     */
    public function EpaycoUpdateTestMode(): void
    {
        $this->validateAjaxNonce();

        $checkoutTestMode    = Form::sanitizedPostData('input_mode_value');

        $validateCheckoutTestMode = ($checkoutTestMode === 'yes');

        $withoutTestCredentials = (
            $this->seller->getCredentialsPublicKeyPayment() === '' ||
            $this->seller->getCredentialsPrivateKeyPayment() === ''
        );

        if ( $withoutTestCredentials ) {
            wp_send_json_error($this->translations->updateCredentials['invalid_credentials_title'] .
                $this->translations->updateCredentials['for_test_mode']);
        }

        $this->store->setCheckboxCheckoutTestMode($checkoutTestMode);

        $this->plugin->executeUpdateTestModeAction();

        if ($validateCheckoutTestMode) {
            wp_send_json_success($this->translations->testModeSettings['title_message_test']);
        }

        wp_send_json_success($this->translations->testModeSettings['title_message_prod']);
    }


    /**
     * Save credentials, seller and store options
     *
     * @return void
     */
    public function EpaycoUpdateOptionCredentials(): void
    {
        try {
            $this->validateAjaxNonce();

            $p_cust_id   = Form::sanitizedPostData('p_cust_id')??$_POST['p_cust_id'];
            $p_key   = Form::sanitizedPostData('p_key')??$_POST['p_key'];
            $publicKey   = Form::sanitizedPostData('publicKey')??$_POST['publicKey'];
            $private_key   = Form::sanitizedPostData('private_key')??$_POST['private_key'];

            $this->seller->validatePublicKeyPayment('p_cust_id', $p_cust_id);
            $this->seller->validatePublicKeyPayment('p_key', $p_key);
            $this->seller->validatePublicKeyPayment('publicKey', $publicKey);
            $this->seller->validatePublicKeyPayment('private_key', $private_key);

            $validateEpaycoCredentials =  $this->seller->validateEpaycoCredentials($publicKey, $private_key);

            //$this->store->setCronSyncMode('no');

            if ($validateEpaycoCredentials['status']) {
                $this->seller->setHomologValidate($validateEpaycoCredentials['status']);
                $this->seller->setCredentialsPCustId($p_cust_id);
                $this->seller->setCredentialsPkey($p_key);
                $this->seller->setCredentialsPublicKeyPayment($publicKey);
                $this->seller->setCredentialsPrivateKeyPayment($private_key);
                $this->plugin->executeUpdateCredentialAction();
                wp_send_json_success($this->translations->updateCredentials['credentials_updated']);
            }else{
                $response = [
                    'type'      => 'error',
                    'message'   => $this->translations->updateCredentials['invalid_credentials_title'],
                    'subtitle'  => $this->translations->updateCredentials['invalid_credentials_subtitle'] . ' ',
                    'linkMsg'   => $this->translations->updateCredentials['invalid_credentials_link_message'],
                    'link'      => 'https://dashboard.epayco.io/login',
                    'test_mode' => $this->store->getCheckboxCheckoutTestMode()
                ];
                wp_send_json_error($response);
            }


        } catch (Exception $e) {
            $response = [
                'type'      => 'error',
                'message'   => $e->getMessage(),
                'subtitle'  => $e->getMessage() . ' ',
                'linkMsg'   => '',
                'link'      => '',
                'test_mode' => $this->store->getCheckboxCheckoutTestMode()
            ];
            wp_send_json_error($response);
        }
    }

    /**
     * Get available payment methods
     *
     * @return void
     */
    public function EpaycoPaymentMethods(): void
    {
        try {
            $this->validateAjaxNonce();

            $paymentGateways            = $this->store->getAvailablePaymentGateways();
            $payment_gateway_properties = [];

            foreach ($paymentGateways as $paymentGateway) {
                $gateway = new $paymentGateway();

                $payment_gateway_properties[] = [
                    'id'               => $gateway->id,
                    'title_gateway'    => $gateway->title,
                    'description'      => $gateway->description,
                    'title'            => $gateway->title,
                    'enabled'          => !isset($gateway->settings['enabled']) ? false : $gateway->settings['enabled'],
                    'icon'             => $gateway->iconAdmin,
                    'link'             => admin_url('admin.php?page=wc-settings&tab=checkout&section=') . $gateway->id,
                    'badge_translator' => [
                        'yes' => $this->translations->gatewaysSettings['enabled'],
                        'no'  => $this->translations->gatewaysSettings['disabled'],
                    ],
                ];
            }

            wp_send_json_success($payment_gateway_properties);
        } catch (Exception $e) {
            $response = [
                'message' => $e->getMessage()
            ];

            wp_send_json_error($response);
        }
    }

    /**
     * Validate credentials tips
     *
     * @return void
     */
    public function EpaycoValidateCredentialsTips(): void
    {
        $this->validateAjaxNonce();

        $publicKeyProd   = $this->seller->getCredentialsPublicKeyPayment();
        $accessTokenProd = $this->seller->getCredentialsPublicKeyPayment();

        if ($publicKeyProd && $accessTokenProd) {
            wp_send_json_success($this->translations->configurationTips['valid_credentials_tips']);
        }

        wp_send_json_error($this->translations->configurationTips['invalid_credentials_tips']);
    }

    /**
     * Validate store tips
     *
     * @return void
     */
    public function EpaycoValidatePaymentTips(): void
    {
        $this->validateAjaxNonce();

        $paymentGateways = $this->store->getAvailablePaymentGateways();

        foreach ($paymentGateways as $gateway) {
            $gateway = new $gateway();

            if (isset($gateway->settings['enabled']) && 'yes' === $gateway->settings['enabled']) {
                wp_send_json_success($this->translations->configurationTips['valid_payment_tips']);
            }
        }

        wp_send_json_error($this->translations->configurationTips['invalid_payment_tips']);
    }

    /**
     * Validate ajax nonce
     *
     * @return void
     */
    private function validateAjaxNonce(): void
    {
        $this->validateNonce(self::NONCE_ID, Form::sanitizedPostData('nonce'));
        $this->currentUser->validateUserNeededPermissions();
    }

    /**
     * Validate wp_nonce
     *
     * @param string $id
     * @param string $nonce
     *
     * @return void
     */
    public function validateNonce(string $id, string $nonce): void
    {
        if (!wp_verify_nonce($nonce, $id)) {
            wp_send_json_error('Forbidden', 403);
        }
    }

}