<?php

namespace Epayco\Woocommerce\Translations;

if (!defined('ABSPATH')) {
    exit;
}

class AdminTranslations
{

    public array $notices = [];

    public array $plugin = [];
    public array $headerSettings = [];
    public array $credentialsSettings = [];
    public array $gatewaysSettings = [];
    public array $testModeSettings = [];
    public array $updateCredentials = [];
    public array $configurationTips = [];
    public array $ticketGatewaySettings = [];
    public array $daviplataGatewaySettings = [];
    public array $creditcardGatewaySettings = [];
    public array $pseGatewaySettings = [];
    public array $checkoutGatewaySettings = [];
    public array $subscriptionGatewaySettings = [];
    public array $statusSync = [];

    /**
     * Translations constructor
     */
    public function __construct()
    {
        $this->setNoticesTranslations();
        $this->setPluginSettingsTranslations();
        $this->setHeaderSettingsTranslations();
        $this->setCredentialsSettingsTranslations();
        $this->setGatewaysSettingsTranslations();
        $this->setTestModeSettingsTranslations();
        $this->setUpdateCredentialsTranslations();
        $this->setConfigurationTipsTranslations();
        $this->setTicketGatewaySettingsTranslations();
        $this->setDaviplataGatewaySettingsTranslations();
        $this->setcreditCardGatewaySettingsTranslations();
        $this->setPseGatewaySettingsTranslations();
        $this->setCheckoutGatewaySettingsTranslations();
        $this->setSubscriptonGatewaySettingsTranslations();
        $this->setStatusSyncTranslations();
    }


    /**
     * Set notices translations
     *
     * @return void
     */
    private function setNoticesTranslations(): void
    {
        $missWoocommerce = sprintf(
            __('The ePayco module needs an active version of %s in order to work!', 'woo-epayco-api'),
            '<a target="_blank" href="https://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a>'
        );

        $this->notices = [
            'miss_woocommerce'          => $missWoocommerce,
            'php_wrong_version'         => __('ePayco payments for WooCommerce requires PHP version 7.4 or later. Please update your PHP version.', 'woo-epayco-api'),
            'missing_curl'              => __('ePayco Error: PHP Extension CURL is not installed.', 'woo-epayco-api'),
            'missing_gd_extensions'     => __('ePayco Error: PHP Extension GD is not installed. Installation of GD extension is required to send QR Code Pix by email.', 'woo-epayco-api'),
            'activate_woocommerce'      => __('Activate WooCommerce', 'woo-epayco-api'),
            'install_woocommerce'       => __('Install WooCommerce', 'woo-epayco-api'),
            'see_woocommerce'           => __('See WooCommerce', 'woo-epayco-api'),
            'dismissed_review_title'    => sprintf(__('%s, help us improve the experience we offer', 'woo-epayco-api'), wp_get_current_user()->display_name),
            'dismissed_review_subtitle' => __('Share your opinion with us so that we improve our product and offer the best payment solution.', 'woo-epayco-api'),
            'dismissed_review_button'   => __('Rate the plugin', 'woo-epayco-api'),
            'saved_cards_title'         => __('Enable payments via Sdk account', 'woo-epayco-api'),
            'saved_cards_subtitle'      => __('When you enable this function, your customers pay faster using their Sdk accounts.</br>The approval rate of these payments in your store can be 25% higher compared to other payment methods.', 'woo-epayco-api'),
            'saved_cards_button'        => __('Activate', 'woo-epayco-api'),
            'missing_translation'       => __("Our plugin does not support the language you've chosen, so we've switched it to the English default. If you prefer, you can also select Spanish or Portuguese (Brazilian).", 'woo-epayco-api'),
            'action_feedback_title'     => __('You activated Sdk’s plug-in', 'woo-epayco-api'),
            'action_feedback_subtitle'  => __('Follow the instructions below to integrate your store with Sdk and start to sell.', 'woo-epayco-api'),
        ];
    }

    /**
     * Set plugin settings translations
     *
     * @return void
     */
    private function setPluginSettingsTranslations(): void
    {
        $this->plugin = [
            'set_plugin'     => __('Set plugin', 'woo-epayco-api'),
            'payment_method' => __('Payment methods', 'woo-epayco-api'),
        ];
    }

    /**
     * Set headers settings translations
     *
     * @return void
     */
    private function setHeaderSettingsTranslations(): void
    {
        $titleHeader = sprintf(
            '<div class="ep-settings-header-title"><p style="font-weight: 900;color: #16161D ;margin:0px 50px;font-size: 19px;line-height: 20px;">%s</p><p style="font-weight: 900;color: #DF5C1F;margin:0px 50px;font-size: 29px;">%s</p><p class="ep-settings-context">%s</p></div>',
            __('OPTIMIZE YOUR STORE WITH THE', 'woo-epayco-api'),
            __('ePayco PLUGIN', 'woo-epayco-api'),
            __('Facilitate payments in your online store with the ePayco plugin. With this integration, you will be able to offer your customers a fast, secure and frictionless payment experience.', 'woo-epayco-api'),
        );

        $this->headerSettings = [
            'title_header'             => $titleHeader,
            'configuration'   => __('Configuration', 'woo-epayco-api'),
        ];
    }
    /**
     * Set credentials settings translations
     *
     * @return void
     */
    private function setCredentialsSettingsTranslations(): void
    {
        $this->credentialsSettings = [
            'title_credentials'                 => __('1. Enter your credentials to integrate your store with ePayco', 'woo-epayco-api'),
            'title_credential'                  => __('Credentials', 'woo-epayco-api'),
            'first_text_subtitle_credentials'   => __('To start selling, ', 'woo-epayco-api'),
            'text_link_credentials'             => __('copy and paste your credentials ', 'woo-epayco-api'),
            'second_text_subtitle_credentials'  => __('in the fields below. If you don’t have credentials yet, you’ll have to create them from this link.', 'woo-epayco-api'),
            'p_cust_id'                         => __('P_CUST_ID_CLIENTE', 'woo-epayco-api'),
            'publicKey'                         => __('PUBLIC_KEY', 'woo-epayco-api'),
            'private_key'                       => __('PRIVATE_KEY', 'woo-epayco-api'),
            'p_key'                             => __('P_KEY', 'woo-epayco-api'),
            'placeholder_p_cust_id'             => __('Paste your P_CUST_ID here', 'woo-epayco-api'),
            'placeholder_publicKey'             => __('Paste your PUBLIC_KEY here', 'woo-epayco-api'),
            'placeholder_private_key'           => __('Paste your PRIVATE_KEY here', 'woo-epayco-api'),
            'placeholder_p_key'                 => __('Paste your P_KEY here', 'woo-epayco-api'),
            'button_credentials'                => __('Save and continue', 'woo-epayco-api'),
            'card_info_subtitle'                => __('You have to enter your production credentials to start selling with ePayco.', 'woo-epayco-api'),
            'card_info_button_text'             => __('Enter credentials', 'woo-epayco-api'),
            'card_homolog_title'                => __('Activate your credentials to be able to sell', 'woo-epayco-api'),
            'card_homolog_subtitle'             => __('Credentials are codes that you must enter to enable sales. Go below on Activate Credentials. On the next screen, use again the Activate Credentials button and fill in the fields with the requested information.', 'woo-epayco-api'),
            'card_homolog_button_text'          => __('Activate credentials', 'woo-epayco-api'),
        ];
    }

    /**
     * Set credits settings translations
     *
     * @return void
     */
    private function setSubscriptonGatewaySettingsTranslations(): void
    {
        $enabledDescriptionsEnabled = sprintf(
            '%s <b>%s</b>.',
            __('Credit cards is', 'woo-epayco-api'),
            __('enabled', 'woo-epayco-api')
        );

        $enabledDescriptionsDisabled = sprintf(
            '%s <b>%s</b>.',
            __('Credit cards is', 'woo-epayco-api'),
            __('disabled', 'woo-epayco-api')
        );

        $this->subscriptionGatewaySettings = [
            'card_settings_title'                       => __('ePayco plugin general settings', 'woo-epayco-api'),
            'card_settings_subtitle'                    => __('Set the deadlines and fees, test your store or access the Plugin manual.', 'woo-epayco-api'),
            'card_settings_button_text'                 => __('Go to Settings', 'woo-epayco-api'),
            'gateway_title'                             => __('Subscription', 'woo-epayco-api'),
            'gateway_description'                       => __('Allow your customers to subscribe to recurring payment plans quickly and easily. Automatically charge every set period without requiring additional actions from the customer.', 'woo-epayco-api'),
            'gateway_method_title'                      => __('ePayco - Checkout Subscription', 'woo-epayco-api'),
            'gateway_method_description'                => __('Payments without leaving your store with our customizable checkout', 'woo-epayco-api'),
            'header_title'                              => __('Subscription', 'woo-epayco-api'),
            'header_description'                        => __('With the Subscription payment, you can sell inside your store environment, without redirection and with the security from ePayco.', 'woo-epayco-api'),
            'enabled_title'                             => __('Enable', 'woo-epayco-api'),
            'enabled_subtitle'                          => __('By disabling it, you will disable subscriptions payments from ePayco.', 'woo-epayco-api'),
            'enabled_descriptions_enabled'              => $enabledDescriptionsEnabled,
            'enabled_descriptions_disabled'             => $enabledDescriptionsDisabled,
            'title_title'                               => __('Title in the store Checkout', 'woo-epayco-api'),
            'title_description'                         => __('Change the display text in Checkout, maximum characters: 85', 'woo-epayco-api'),
            'title_default'                             => __('Subscription', 'woo-epayco-api'),
            'title_desc_tip'                            => __('The text inserted here will not be translated to other languages', 'woo-epayco-api')
        ];
        $this->subscriptionGatewaySettings  = array_merge($this->subscriptionGatewaySettings, $this->setSupportLinkTranslations());
    }

    /**
     * Set gateway settings translations
     *
     * @return void
     */
    private function setGatewaysSettingsTranslations(): void
    {
        $this->gatewaysSettings = [
            'title_payments'    => __('2. Activate and set up payment methods', 'woo-epayco-api'),
            'subtitle_payments' => __('Select the payment method you want to appear in your store to activate and set it up.', 'woo-epayco-api'),
            'settings_payment'  => __('Settings', 'woo-epayco-api'),
            'button_payment'    => __('Continue', 'woo-epayco-api'),
            'enabled'           => __('Enabled', 'woo-epayco-api'),
            'disabled'          => __('Disabled', 'woo-epayco-api'),
            'empty_credentials' => __('Configure your credentials to enable ePayco payment methods.', 'woo-epayco-api'),
        ];
    }

    /**
     * Set test mode settings translations
     *
     * @return void
     */
    private function setTestModeSettingsTranslations(): void
    {
        $this->testModeSettings = [
            'title_test_mode'         => __('3. Test your store before you start to sell', 'woo-epayco-api'),
            'badge_test'              => __('test', 'woo-epayco-api'),
            'badge_mode'              => __('Production', 'woo-epayco-api'),
            'subtitle_test_mode'      => __('Select “Test Mode” if you want to try the payment experience before you start to sell or “Sales Mode” (Production) to start now.', 'woo-epayco-api'),
            'title_mode'              => __('Choose how you want to operate your store:', 'woo-epayco-api'),
            'title_test'              => __('Test Mode', 'woo-epayco-api'),
            'subtitle_test'           => __('ePayco Checkouts Test.', 'woo-epayco-api'),
            'subtitle_test_link'      => __('Test Mode rules.', 'woo-epayco-api'),
            'title_prod'              => __('Production Mode', 'woo-epayco-api'),
            'subtitle_prod'           => __('ePayco Checkouts Production.', 'woo-epayco-api'),
            'title_message_prod'      => __('ePayco payment methods in Production Mode', 'woo-epayco-api'),
            'title_message_test'      => __('ePayco payment methods in Test Mode', 'woo-epayco-api'),
            'subtitle_message_prod'   => __('The clients can make real purchases in your store.', 'woo-epayco-api'),
            'button_test_mode'        => __('Save changes', 'woo-epayco-api'),
        ];
    }

    /**
     * Set update credentials translations
     *
     * @return void
     */
    private function setUpdateCredentialsTranslations(): void
    {
        $this->updateCredentials = [
            'credentials_updated'              => __('Credentials were updated', 'woo-epayco-api'),
            'no_test_mode_title'               => __('Your store has exited Test Mode and is making real sales in Production Mode.', 'woo-epayco-api'),
            'no_test_mode_subtitle'            => __('To test the store, re-enter both test credentials.', 'woo-epayco-api'),
            'invalid_credentials_title'        => __('Invalid credentials', 'woo-epayco-api'),
            'invalid_credentials_subtitle'     => __('See our manual to learn', 'woo-epayco-api'),
            'invalid_credentials_link_message' => __('how to enter the credentials the right way.', 'woo-epayco-api'),
            'for_test_mode'                    => __(' for test mode', 'woo-epayco-api'),
        ];
    }

    /**
     * Set configuration tips translations
     *
     * @return void
     */
    private function setConfigurationTipsTranslations(): void
    {
        $this->configurationTips = [
            'valid_store_tips'         => __('Store business fields are valid', 'woo-epayco-api'),
            'invalid_store_tips'       => __('Store business fields could not be validated', 'woo-epayco-api'),
            'valid_payment_tips'       => __('At least one payment method is enabled', 'woo-epayco-api'),
            'invalid_payment_tips'     => __('No payment method enabled', 'woo-epayco-api'),
            'valid_credentials_tips'   => __('Credentials fields are valid', 'woo-epayco-api'),
            'invalid_credentials_tips' => __('Credentials fields could not be validated', 'woo-epayco-api'),
        ];
    }

    /**
     * Set ticket settings translations
     *
     * @return void
     */
    private function setTicketGatewaySettingsTranslations(): void
    {
        $this->ticketGatewaySettings = [
            'gateway_title'                => __('Cash', 'woo-epayco-api'),
            'gateway_description'          => __('Add the cash payment option directly in your store. Perfect for customers who prefer paying at physical locations, with no hassles or redirects.', 'woo-epayco-api'),
            'method_title'                 => __('ePayco - Checkout Cash', 'woo-epayco-api'),
            'header_title'                 => __('Cash Checkout', 'woo-epayco-api'),
            'header_description'           => __('With the Transparent Checkout, you can sell inside your store environment, without redirection and all the safety from ePayco.', 'woo-epayco-api'),
            'card_settings_title'                       => __('ePayco plugin general settings', 'woo-epayco-api'),
            'card_settings_subtitle'                    => __('Set the deadlines and fees, test your store or access the Plugin manual.', 'woo-epayco-api'),
            'card_settings_button_text'                 => __('Go to Settings', 'woo-epayco-api'),
            'enabled_title'                => __('Enable the Checkout', 'woo-epayco-api'),
            'enabled_subtitle'             => __('By disabling it, you will disable all cash payments from ePayco Transparent Checkout.', 'woo-epayco-api'),
            'enabled_enabled'              => __('Cash is <b>enabled</b>.', 'woo-epayco-api'),
            'enabled_disabled'             => __('Cash is <b>disabled</b>.', 'woo-epayco-api'),
            'title_title'                  => __('Title in the store Checkout', 'woo-epayco-api'),
            'title_description'            => __('Change the display text in Checkout, maximum characters: 85', 'woo-epayco-api'),
            'title_default'                => __('Invoice', 'woo-epayco-api'),
            'title_desc_tip'               => __('The text inserted here will not be translated to other languages', 'woo-epayco-api'),
            'date_expiration_title'        => __('Payment Due', 'woo-epayco-api'),
            'date_expiration_description'  => __('In how many days will cash payments expire.', 'woo-epayco-api'),
            'type_payments_title'          => __('Payment methods', 'woo-epayco-api'),
            'type_payments_description'    => __('Enable the available payment methods', 'woo-epayco-api'),
            'type_payments_desctip'        => __('Choose the available payment methods in your store.', 'woo-epayco-api'),
            'type_payments_label'          => __('All payment methods', 'woo-epayco-api'),
        ];
        $this->ticketGatewaySettings  = array_merge($this->ticketGatewaySettings, $this->setSupportLinkTranslations());
    }

    /**
     * Set ticket settings translations
     *
     * @return void
     */
    private function setDaviplataGatewaySettingsTranslations(): void
    {
        $this->daviplataGatewaySettings = [
            'gateway_title'                => __('Daviplata', 'woo-epayco-api'),
            'gateway_description'          => __('Add the Daviplata payment option directly in your store. Perfect for customers who prefer paying at physical locations, with no hassles or redirects.', 'woo-epayco-api'),
            'method_title'                 => __('ePayco - Checkout Daviplata', 'woo-epayco-api'),
            'header_title'                 => __('Daviplata Checkout', 'woo-epayco-api'),
            'header_description'           => __('With the Transparent Checkout, you can sell inside your store environment, without redirection and all the safety from ePayco.', 'woo-epayco-api'),
            'card_settings_title'                       => __('ePayco plugin general settings', 'woo-epayco-api'),
            'card_settings_subtitle'                    => __('Set the deadlines and fees, test your store or access the Plugin manual.', 'woo-epayco-api'),
            'card_settings_button_text'                 => __('Go to Settings', 'woo-epayco-api'),
            'enabled_title'                => __('Enable the Checkout', 'woo-epayco-api'),
            'enabled_subtitle'             => __('By disabling it, you will disable daviplata payment from ePayco Transparent Checkout.', 'woo-epayco-api'),
            'enabled_enabled'              => __('Daviplata is <b>enabled</b>.', 'woo-epayco-api'),
            'enabled_disabled'             => __('Daviplata is <b>disabled</b>.', 'woo-epayco-api'),
            'title_title'                  => __('Title in the store Checkout', 'woo-epayco-api'),
            'title_description'            => __('Change the display text in Checkout, maximum characters: 85', 'woo-epayco-api'),
            'title_default'                => __('Invoice', 'woo-epayco-api'),
            'title_desc_tip'               => __('The text inserted here will not be translated to other languages', 'woo-epayco-api'),
        ];
        $this->ticketGatewaySettings  = array_merge($this->ticketGatewaySettings, $this->setSupportLinkTranslations());
    }

    /**
     * Set credits settings translations
     *
     * @return void
     */
    private function setcreditCardGatewaySettingsTranslations (): void
    {
        $enabledDescriptionsEnabled = sprintf(
            '%s <b>%s</b>.',
            __('Credit cards is', 'woo-epayco-api'),
            __('enabled', 'woo-epayco-api')
        );

        $enabledDescriptionsDisabled = sprintf(
            '%s <b>%s</b>.',
            __('Credit cards is', 'woo-epayco-api'),
            __('disabled', 'woo-epayco-api')
        );

        $this->creditcardGatewaySettings = [
            'card_settings_title'                       => __('ePayco plugin general settings', 'woo-epayco-api'),
            'card_settings_subtitle'                    => __('Set the deadlines and fees, test your store or access the Plugin manual.', 'woo-epayco-api'),
            'card_settings_button_text'                 => __('Go to Settings', 'woo-epayco-api'),
            'gateway_title'                             => __('Credit and Debit Cards by ePayco', 'woo-epayco-api'),
            'gateway_description'                       => __('Accept fast and secure payments directly from your store using credit and debit cards from any bank. No redirects, ensuring a seamless shopping experience.  (Visa, Mastercard, Amex & Dinners)', 'woo-epayco-api'),
            'gateway_method_title'                      => __('ePayco - Checkout Credit card', 'woo-epayco-api'),
            'gateway_method_description'                => __('Payments without leaving your store with our customizable checkout', 'woo-epayco-api'),
            'header_title'                              => __('Credit and Debit Cards by ePayco', 'woo-epayco-api'),
            'header_description'                        => __('With the Credit card payment, you can sell inside your store environment, without redirection and with the security from ePayco.', 'woo-epayco-api'),
            'enabled_title'                             => __('Enable', 'woo-epayco-api'),
            'enabled_subtitle'                          => __('By disabling it, you will disable all credit cards payments from ePayco.', 'woo-epayco-api'),
            'enabled_descriptions_enabled'              => $enabledDescriptionsEnabled,
            'enabled_descriptions_disabled'             => $enabledDescriptionsDisabled,
            'title_title'                               => __('Title in the store Checkout', 'woo-epayco-api'),
            'title_description'                         => __('Change the display text in Checkout, maximum characters: 85', 'woo-epayco-api'),
            'title_default'                             => __('Credit and debit cards', 'woo-epayco-api'),
            'title_desc_tip'                            => __('The text inserted here will not be translated to other languages', 'woo-epayco-api')
        ];
        $this->creditcardGatewaySettings  = array_merge($this->creditcardGatewaySettings, $this->setSupportLinkTranslations());
    }

    /**
     * Set PSE settings translations
     *
     * @return void
     */
    private function setPseGatewaySettingsTranslations(): void
    {
        $this->pseGatewaySettings = [
            'card_settings_title'                       => __('ePayco plugin general settings', 'woo-epayco-api'),
            'card_settings_subtitle'                    => __('Set the deadlines and fees, test your store or access the Plugin manual.', 'woo-epayco-api'),
            'card_settings_button_text'                 => __('Go to Settings', 'woo-epayco-api'),
            'gateway_title'                => __('PSE by ePayco', 'woo-epayco-api'),
            'gateway_description'          => __('Let your customers pay with direct bank transfers from any Colombian bank, all without leaving your online store. Secure, fast, and interruption-free.', 'woo-epayco-api'),
            'method_title'                 => __('ePayco - Checkout Pse', 'woo-epayco-api'),
            'header_title'                 => __('PSE by ePayco', 'woo-epayco-api'),
            'header_description'           => __('you can sell inside your store environment, without redirection and all the safety from ePayco.', 'woo-epayco-api'),
            'enabled_title'                => __('Enable PSE', 'woo-epayco-api'),
            'enabled_subtitle'             => __('By deactivating it, you will disable PSE payments from ePayco', 'woo-epayco-api'),
            'enabled_enabled'              => __('PSE is <b>enabled</b>.', 'woo-epayco-api'),
            'enabled_disabled'             => __('PSE is <b>disabled</b>.', 'woo-epayco-api'),
            'title_title'                  => __('Title in the store Checkout', 'woo-epayco-api'),
            'title_description'            => __('Change the display text in Checkout, maximum characters: 85', 'woo-epayco-api'),
            'title_default'                => __('PSE', 'woo-epayco-api'),
            'title_desc_tip'               => __('The text inserted here will not be translated to other languages', 'woo-epayco-api'),
        ];
        $this->pseGatewaySettings  = array_merge($this->pseGatewaySettings, $this->setSupportLinkTranslations());
    }

    /**
     * Set PSE settings translations
     *
     * @return void
     */
    private function setCheckoutGatewaySettingsTranslations(): void
    {
        $ePaycoCheckoutDescriptionsEnabled = sprintf(
            '%s <b>%s</b>.',
            __('One Page Checkout is', 'woo-epayco-api'),
            __('enabled', 'woo-epayco-api')
        );

        $ePaycoCheckoutDescriptionsDisabled = sprintf(
            '%s <b>%s</b>.',
            __('One Page Checkout is', 'woo-epayco-api'),
            __('disabled', 'woo-epayco-api')
        );
        $this->checkoutGatewaySettings = [
            'card_settings_title'                       => __('ePayco plugin general settings', 'woo-epayco-api'),
            'card_settings_subtitle'                    => __('Set the deadlines and fees, test your store or access the Plugin manual.', 'woo-epayco-api'),
            'card_settings_button_text'                 => __('Go to Settings', 'woo-epayco-api'),
            'gateway_title'                => __('Web CheckOut', 'woo-epayco-api'),
            'gateway_description'          => __('Offer your customers a complete payment experience with multiple options: cards, bank transfers, digital wallets, and cash. All in one secure and easy-to-use platform!', 'woo-epayco-api'),
            'method_title'                 => __('ePayco', 'woo-epayco-api'),
            'header_title'                 => __('Web CheckOut', 'woo-epayco-api'),
            'header_description'           => __('you can sell inside your store environment, without redirection and all the safety from ePayco.', 'woo-epayco-api'),
            'enabled_title'                => __('Enable ePayco', 'woo-epayco-api'),
            'enabled_subtitle'             => __('By deactivating it, you will disable Checkout payment from ePayco', 'woo-epayco-api'),
            'enabled_enabled'              => __('Checkout is <b>enabled</b>.', 'woo-epayco-api'),
            'enabled_disabled'             => __('Checkout is <b>disabled</b>.', 'woo-epayco-api'),
            'title_title'                  => __('Title in the store Checkout', 'woo-epayco-api'),
            'title_description'            => __('Change the display text in Checkout, maximum characters: 85', 'woo-epayco-api'),
            'title_default'                => __('Checkout', 'woo-epayco-api'),
            'title_desc_tip'               => __('The text inserted here will not be translated to other languages', 'woo-epayco-api'),
            'epayco_type_checkout_title'                 => __('Checkout mode', 'woo-epayco-api'),
            'epayco_type_checkout_subtitle'              => __('Activate this option so that the payment experience is within your store environment, without redirection.', 'woo-epayco-api'),
            'epayco_type_checkout_descriptions_enabled'  => $ePaycoCheckoutDescriptionsEnabled,
            'epayco_type_checkout_descriptions_disabled' => $ePaycoCheckoutDescriptionsDisabled,
        ];
        $this->checkoutGatewaySettings  = array_merge($this->checkoutGatewaySettings, $this->setSupportLinkTranslations());
    }

    /**
     * Set status sync metabox translations
     *
     * @return void
     */
    private function setStatusSyncTranslations(): void
    {
        $this->statusSync = [
            'metabox_title'                                    => __('Payment status on ePayco', 'woo-epayco-api'),
            'card_title'                                       => __('This is the payment status of your ePayco Activities. To check the order status, please refer to Order details.', 'woo-epayco-api'),
            'link_description_success'                         => __('View purchase details at ePayco', 'woo-epayco-api'),
            'sync_button_success'                              => __('Sync order status', 'woo-epayco-api'),
            'link_description_pending'                         => __('View purchase details at ePayco', 'woo-epayco-api'),
            'sync_button_pending'                              => __('Sync order status', 'woo-epayco-api'),
            'link_description_failure'                         => __('Consult the reasons for refusal', 'woo-epayco-api'),
            'sync_button_failure'                              => __('Sync order status', 'woo-epayco-api'),
            'alert_title_generic'                              => __('Something went wrong and the payment was declined', 'woo-epayco-api'),
            'description_generic'                              => __('Please recommend you customer to try again or to pay with another payment method.', 'woo-epayco-api'),
        ];
    }

    /**
     * Set support link translations
     *
     * @return array with new translations
     */
    private function setSupportLinkTranslations(): array
    {
        return [
            'support_link_bold_text'        => __('Any questions?', 'woo-epayco-api'),
            'support_link_text_before_link' => __('Please check the', 'woo-epayco-api'),
            'support_link_text_with_link'   => __('FAQs', 'woo-epayco-api'),
            'support_link_text_after_link'  => __('on the dev website.', 'woo-epayco-api'),
        ];
    }
    
}