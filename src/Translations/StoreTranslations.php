<?php

namespace Epayco\Woocommerce\Translations;

if (!defined('ABSPATH')) {
    exit;
}

class StoreTranslations
{
    public array $ticketCheckout = [];
    public array $daviplataCheckout = [];
    public array $creditcardCheckout = [];
    public array $pseCheckout = [];
    public array $epaycoCheckout = [];
    public array $subscriptionCheckout = [];
    public array $buyerRefusedMessages = [];
    public array $commonMessages = [];

    /**
     * Translations constructor
     */
    public function __construct()
    {
        $this->setTicketCheckoutTranslations();
        $this->setDaviplataCheckoutTranslations();
        $this->setCreditCardCheckoutTranslations ();
        $this->setPseCheckoutTranslations();
        $this->setSubscriptionCheckoutTranslations();
        $this->setbuyerRefusedMessagesTranslations();
        $this->setCommonMessagesTranslations();
        $this->setEpaycoCheckoutTranslations();
    }

    /**
     * Set checkout ticket translations
     *
     * @return void
     */
    private function setTicketCheckoutTranslations(): void
    {
        $this->ticketCheckout = [
            'message_error_amount'             => __('There was an error. Please try again in a few minutes.', 'woo-epayco-api'),
            'test_mode_title'                  => __('Test Mode', 'woo-epayco-api'),
            'test_mode_description'            => __('You can test the flow to generate an invoice, but you cannot finalize the payment.', 'woo-epayco-api'),
            'test_mode_link_text'              => __('See the rules for the test mode.', 'woo-epayco-api'),
            'input_name_label'                 => __('Holder name', 'woo-epayco-api'),
            'input_name_helper'                => __('Invalid name', 'woo-epayco-api'),
            'input_email_label'                => __('Holder email', 'woo-epayco-api'),
            'input_email_helper'               => __('Invalid email', 'woo-epayco-api'),
            'input_address_label'              => __('Holder address', 'woo-epayco-api'),
            'input_address_helper'             => __('Invalid adress', 'woo-epayco-api'),
            'input_ind_phone_label'            => __('Holder cellphone', 'woo-epayco-api'),
            'input_ind_phone_helper'           => __('Invalid cellphone', 'woo-epayco-api'),
            'person_type_label'                => __('Person type', 'woo-epayco-api'),
            'input_document_label'             => __('Holder document', 'woo-epayco-api'),
            'input_document_helper'            => __('Invalid document', 'woo-epayco-api'),
            'input_country_label'              => __('Holder Country', 'woo-epayco-api'),
            'input_country_helper'             => __('Invalid City', 'woo-epayco-api'),
            'ticket_text_label'                => __('Select where you want to pay', 'woo-epayco-api'),
            'input_table_button'               => __('more options', 'woo-epayco-api'),
            'input_helper_label'               => __('Select a payment method', 'woo-epayco-api'),
            'terms_and_conditions_label'       => __('I confirm and accept the', 'woo-epayco-api'),
            'terms_and_conditions_description' => __(' of ePayco.', 'woo-epayco-api'),
            'and_the' => __(' and the', 'woo-epayco-api'),
            'personal_data_processing_link_text' => __(' personal data processing policy', 'woo-epayco-api'),
            'terms_and_conditions_link_text'   => __('Terms and conditions', 'woo-epayco-api'),
            'success_message'                  => __('Approved transaction', 'woo-epayco-api'),
            'pending_message'                  => __('Pending transaction', 'woo-epayco-api'),
            'fail_message'                  => __('Transaction rejected', 'woo-epayco-api'),
            'error_message'                    => __('Payment has failed', 'woo-epayco-api'),
            'error_description'                => __('Please try again later.', 'woo-epayco-api'),
            'payment_method'                   => __('payment method', 'woo-epayco-api'),
            'dateandtime'                      => __('Date and time', 'woo-epayco-api'),
            'response'                         => __('Response', 'woo-epayco-api'),
            'totalValue'                       => __('Total value', 'woo-epayco-api'),
            'description'                       => __('Description', 'woo-epayco-api'),
            'reference'                       => __('Reference', 'woo-epayco-api'),
            'purchase'                        => __('Purchase details', 'woo-epayco-api'),
            'iPaddress'                       => __('IP address', 'woo-epayco-api'),
            'receipt'                         => __('Receipt', 'woo-epayco-api'),
            'authorization'                   => __('Authorization', 'woo-epayco-api'),
            'paymentMethod'                   => __('Payment method', 'woo-epayco-api'),
            'epayco_refecence'                => __('ePayco Reference', 'woo-epayco-api'),
            'customer_title'                   => __('Customer data', 'woo-epayco-api'),
            'code'                   => __('Convention code', 'woo-epayco-api'),
            'expirationDate'  => __('Expiration Date', 'woo-epayco-api'),
            'ticket_header'  => __('Taking into account the payment date, go to the nearest earning point and indicate the following information:', 'woo-epayco-api'),
            'ticket_footer'  => __('Collection in the name of EPAYCO.COM S.A.S.', 'woo-epayco-api'),
            'donwload_text'  => __('Download ePayco receipt', 'woo-epayco-api'),
        ];
    }

    /**
     * Set checkout ticket translations
     *
     * @return void
     */
    private function setDaviplataCheckoutTranslations(): void
    {
        $this->daviplataCheckout = [
            'message_error_amount'             => __('There was an error. Please try again in a few minutes.', 'woo-epayco-api'),
            'test_mode_title'                  => __('Test Mode', 'woo-epayco-api'),
            'test_mode_description'            => __('You can test the flow to generate an invoice, but you cannot finalize the payment.', 'woo-epayco-api'),
            'test_mode_link_text'              => __('See the rules for the test mode.', 'woo-epayco-api'),
            'input_name_label'                 => __('Holder name', 'woo-epayco-api'),
            'input_name_helper'                => __('Invalid name', 'woo-epayco-api'),
            'input_email_label'                => __('Holder email', 'woo-epayco-api'),
            'input_email_helper'               => __('Invalid email', 'woo-epayco-api'),
            'input_address_label'              => __('Holder address', 'woo-epayco-api'),
            'input_address_helper'             => __('Invalid adress', 'woo-epayco-api'),
            'input_ind_phone_label'            => __('Holder cellphone', 'woo-epayco-api'),
            'input_ind_phone_helper'           => __('Invalid cellphone', 'woo-epayco-api'),
            'person_type_label'                => __('Person type', 'woo-epayco-api'),
            'input_document_label'             => __('Holder document', 'woo-epayco-api'),
            'input_document_helper'            => __('Invalid document', 'woo-epayco-api'),
            'input_country_label'              => __('Holder Country', 'woo-epayco-api'),
            'input_country_helper'             => __('Invalid City', 'woo-epayco-api'),
            'daviplata_text_label'                => __('Select where you want to pay', 'woo-epayco-api'),
            'input_table_button'               => __('more options', 'woo-epayco-api'),
            'input_helper_label'               => __('Select a payment method', 'woo-epayco-api'),
            'terms_and_conditions_label'       => __('I confirm and accept the', 'woo-epayco-api'),
            'terms_and_conditions_description' => __(' of ePayco.', 'woo-epayco-api'),
            'terms_and_conditions_link_text'   => __('Terms and conditions', 'woo-epayco-api'),
            'and_the' => __(' and the', 'woo-epayco-api'),
            'personal_data_processing_link_text'                   => __(' personal data processing policy', 'woo-epayco-api'),
            'success_message'                  => __('Approved transaction', 'woo-epayco-api'),
            'pending_message'                  => __('Pending transaction', 'woo-epayco-api'),
            'fail_message'                     => __('Transaction rejected', 'woo-epayco-api'),
            'error_message'                    => __('Payment has failed', 'woo-epayco-api'),
            'error_description'                => __('Please try again later.', 'woo-epayco-api'),
            'payment_method'                   => __('Payment method', 'woo-epayco-api'),
            'dateandtime'                      => __('Date and time', 'woo-epayco-api'),
            'response'                         => __('Response', 'woo-epayco-api'),
            'totalValue'                       => __('Total value', 'woo-epayco-api'),
            'description'                       => __('Description', 'woo-epayco-api'),
            'reference'                       => __('Reference', 'woo-epayco-api'),
            'purchase'                        => __('Purchase details', 'woo-epayco-api'),
            'iPaddress'                       => __('IP address', 'woo-epayco-api'),
            'receipt'                         => __('Receipt', 'woo-epayco-api'),
            'authorization'                   => __('Authorization', 'woo-epayco-api'),
            'paymentMethod'                   => __('Payment method', 'woo-epayco-api'),
            'epayco_refecence'                => __('ePayco Reference', 'woo-epayco-api'),
            'customer_title'                   => __('Customer data', 'woo-epayco-api'),
            'donwload_text'  => __('Download ePayco receipt', 'woo-epayco-api'),

        ];
    }

    /**
     * Set credits checkout translations
     *
     * @return void
     */
    private function setCreditCardCheckoutTranslations (): void
    {
        $this->creditcardCheckout = [
            'message_error_amount'                                => __('There was an error. Please try again in a few minutes.', 'woo-epayco-api'),
            'test_mode_title'                                     => __('Test Mode', 'woo-epayco-api'),
            'test_mode_description'                               => __('Use ePayco\'s payment methods without real charges. ', 'woo-epayco-api'),
            'test_mode_link_text'                                 => __('See the rules for the test mode.', 'woo-epayco-api'),
            'card_detail'                                         => __('Card details', 'woo-epayco-api'),
            'card_form_title'                                     => __('Card details', 'woo-epayco-api'),
            'card_holder_name_input_label'                        => __('Holder name as it appears on the card', 'woo-epayco-api'),
            'card_holder_name_input_helper'                       => __('Holder name is required', 'woo-epayco-api'),
            'card_number_input_label'                             => __('Card number', 'woo-epayco-api'),
            'card_number_input_helper'                            => __('Required Card number', 'woo-epayco-api'),
            'card_expiration_input_label'                         => __('Expiration', 'woo-epayco-api'),
            'card_expiration_input_helper'                        => __('Required data', 'woo-epayco-api'),
            'customer_data'                                       => __('Customer data', 'woo-epayco-api'),
            'input_helper_message_expiration_date_invalid_type'   => __('Expiration date invalid', 'woo-epayco-api'),
            'input_helper_message_expiration_date_invalid_length' => __('Expiration date incomplete', 'woo-epayco-api'),
            'input_helper_message_expiration_date_invalid_value'  => __('Expiration date invalid', 'woo-epayco-api'),
            'card_security_code_input_label'                      => __('Security Code', 'woo-epayco-api'),
            'card_security_code_input_helper'                     => __('Required data', 'woo-epayco-api'),
            'input_helper_message_security_code_invalid_type'     => __('Security code is required', 'woo-epayco-api'),
            'input_helper_message_security_code_invalid_length'   => __('Security code incomplete', 'woo-epayco-api'),
            'card_fees_input_label'                               => __('Fees', 'woo-epayco-api'),
            'card_customer_title'                                 => __('Customer data', 'woo-epayco-api'),
            'card_document_input_label'                           => __('Holder document', 'woo-epayco-api'),
            'card_document_input_helper'                          => __('Invalid document', 'woo-epayco-api'),
            'card_holder_address_input_label'                     => __('Address ', 'woo-epayco-api'),
            'card_holder_address_input_helper'                    => __('Holder address is required', 'woo-epayco-api'),
            'card_holder_email_input_label'                       => __('Email', 'woo-epayco-api'),
            'card_holder_email_input_helper'                      => __('Holder email is required', 'woo-epayco-api'),
            'input_helper_message_card_holder_email'              => __('Holder email invalid', 'woo-epayco-api'),
            'input_ind_phone_label'                               => __('Holder Phone', 'woo-epayco-api'),
            'input_ind_phone_helper'                               => __('Invalid Phone', 'woo-epayco-api'),
            'input_country_label'                                 => __('Holder Country', 'woo-epayco-api'),
            'input_country_helper'                                => __('Invalid City', 'woo-epayco-api'),
            'terms_and_conditions_label'                          => __('I confirm and accept the', 'woo-epayco-api'),
            'terms_and_conditions_description'                    => __(' of ePayco.', 'woo-epayco-api'),
            'terms_and_conditions_link_text'                      => __('Terms and conditions', 'woo-epayco-api'),
            'and_the' => __(' and the', 'woo-epayco-api'),
            'personal_data_processing_link_text'                   => __(' personal data processing policy', 'woo-epayco-api'),
            'success_message'                  => __('Approved transaction', 'woo-epayco-api'),
            'pending_message'                  => __('Pending transaction', 'woo-epayco-api'),
            'fail_message'                  => __('Transaction rejected', 'woo-epayco-api'),
            'error_message'                    => __('Payment has failed', 'woo-epayco-api'),
            'error_description'                => __('Please try again later.', 'woo-epayco-api'),
            'payment_method'                   => __('Payment method', 'woo-epayco-api'),
            'dateandtime'                      => __('Date and time', 'woo-epayco-api'),
            'response'                         => __('Response', 'woo-epayco-api'),
            'totalValue'                       => __('Total value', 'woo-epayco-api'),
            'description'                       => __('Description', 'woo-epayco-api'),
            'reference'                       => __('Reference', 'woo-epayco-api'),
            'purchase'                        => __('Purchase details', 'woo-epayco-api'),
            'iPaddress'                       => __('IP address', 'woo-epayco-api'),
            'receipt'                         => __('Receipt', 'woo-epayco-api'),
            'authorization'                   => __('Authorization', 'woo-epayco-api'),
            'paymentMethod'                   => __('Payment method', 'woo-epayco-api'),
            'epayco_refecence'                => __('ePayco Reference', 'woo-epayco-api'),
            'donwload_text'  => __('Download ePayco receipt', 'woo-epayco-api'),
        ];
    }

    /**
     * Set checkout pse translations
     *
     * @return void
     */
    private function setPseCheckoutTranslations(): void
    {
        $this->pseCheckout = [
            'message_error_amount'             => __('There was an error. Please try again in a few minutes.', 'woo-epayco-api'),
            'test_mode_title'                  => __('Test Mode', 'woo-epayco-api'),
            'test_mode_description'            => __('You can test the flow to generate a payment with PSE', 'woo-epayco-api'),
            'test_mode_link_text'              => __('See the rules for the test mode.', 'woo-epayco-api'),
            'input_name_label'                 => __('Holder name', 'woo-epayco-api'),
            'input_name_helper'                => __('Invalid name', 'woo-epayco-api'),
            'input_email_label'                => __('Email', 'woo-epayco-api'),
            'input_email_helper'               => __('Invalid email', 'woo-epayco-api'),
            'input_address_label'              => __('Address', 'woo-epayco-api'),
            'input_address_helper'             => __('Invalid address', 'woo-epayco-api'),
            'input_document_label'             => __('Holder document', 'woo-epayco-api'),
            'input_document_helper'            => __('Invalid document', 'woo-epayco-api'),
            'input_ind_phone_label'            => __('Cellphone', 'woo-epayco-api'),
            'input_ind_phone_helper'           => __('Invalid cellphone', 'woo-epayco-api'),
            'input_country_label'              => __('Holder Country', 'woo-epayco-api'),
            'input_country_helper'             => __('Invalid City', 'woo-epayco-api'),
            'person_type_label'                => __('Person type ', 'woo-epayco-api'),
            'financial_institutions_label'     => __('Bank', 'woo-epayco-api'),
            'financial_institutions_helper'    => __('Select the Bank', 'woo-epayco-api'),
            'financial_placeholder'            => __('Select the Bank', 'woo-epayco-api'),
            'terms_and_conditions_label'                          => __('I confirm and accept the', 'woo-epayco-api'),
            'terms_and_conditions_description'                    => __(' of ePayco.', 'woo-epayco-api'),
            'terms_and_conditions_link_text'                      => __('Terms and conditions', 'woo-epayco-api'),
            'and_the' => __(' and the', 'woo-epayco-api'),
            'personal_data_processing_link_text'                   => __(' personal data processing policy', 'woo-epayco-api'),
            'success_message'                  => __('Approved transaction', 'woo-epayco-api'),
            'pending_message'                  => __('Pending transaction', 'woo-epayco-api'),
            'fail_message'                     => __('Transaction rejected', 'woo-epayco-api'),
            'error_message'                    => __('Payment has failed', 'woo-epayco-api'),
            'error_description'                => __('Please try again later.', 'woo-epayco-api'),
            'payment_method'                   => __('Payment method', 'woo-epayco-api'),
            'dateandtime'                      => __('Date and time', 'woo-epayco-api'),
            'response'                         => __('Response', 'woo-epayco-api'),
            'totalValue'                       => __('Total value', 'woo-epayco-api'),
            'description'                       => __('Description', 'woo-epayco-api'),
            'reference'                       => __('Reference', 'woo-epayco-api'),
            'purchase'                        => __('Purchase details', 'woo-epayco-api'),
            'iPaddress'                       => __('IP address', 'woo-epayco-api'),
            'receipt'                         => __('Receipt', 'woo-epayco-api'),
            'authorization'                   => __('Authorization', 'woo-epayco-api'),
            'paymentMethod'                   => __('Payment method', 'woo-epayco-api'),
            'epayco_refecence'                => __('ePayco Reference', 'woo-epayco-api'),
            'customer_title'                   => __('Customer data', 'woo-epayco-api'),
            'donwload_text'  => __('Download ePayco receipt', 'woo-epayco-api'),
        ];
    }

    /**
     * Set common messages translations
     *
     * @return void
     */
    private function setEpaycoCheckoutTranslations(): void
    {
        $this->epaycoCheckout = [
            'test_mode_title'                  => __('Test Mode', 'woo-epayco-api'),
            'test_mode_description'            => __('You can test the flow to generate a payment with ePayco', 'woo-epayco-api'),
            'test_mode_link_text'              => __('See the rules for the test mode.', 'woo-epayco-api'),
            'print_ticket_label'               => __('Great, we processed your purchase order. Complete the payment with ticket so that we finish approving it.', 'woo-epayco-api'),
            'message_error_amount'             => __('There was an error. Please try again in a few minutes.', 'woo-epayco-api'),
            'terms_and_conditions_label'                          => __('I confirm and accept the', 'woo-epayco-api'),
            'terms_and_conditions_description'                    => __(' of ePayco.', 'woo-epayco-api'),
            'terms_and_conditions_link_text'                      => __('Terms and conditions', 'woo-epayco-api'),
            'and_the' => __(' and the', 'woo-epayco-api'),
            'personal_data_processing_link_text'                   => __(' personal data processing policy', 'woo-epayco-api'),
            'success_message'                  => __('Approved transaction', 'woo-epayco-api'),
            'pending_message'                  => __('Pending transaction', 'woo-epayco-api'),
            'fail_message'                  => __('Transaction rejected', 'woo-epayco-api'),
            'error_message'                    => __('Payment has failed', 'woo-epayco-api'),
            'error_description'                => __('Please try again later.', 'woo-epayco-api'),
            'payment_method'                   => __('payment method', 'woo-epayco-api'),
            'dateandtime'                      => __('Date and time', 'woo-epayco-api'),
            'response'                         => __('Response', 'woo-epayco-api'),
            'totalValue'                       => __('Total value', 'woo-epayco-api'),
            'description'                       => __('Description', 'woo-epayco-api'),
            'reference'                       => __('Reference', 'woo-epayco-api'),
            'purchase'                        => __('Purchase details', 'woo-epayco-api'),
            'iPaddress'                       => __('IP address', 'woo-epayco-api'),
            'receipt'                         => __('Receipt', 'woo-epayco-api'),
            'authorization'                   => __('Authorization', 'woo-epayco-api'),
            'paymentMethod'                   => __('Payment method', 'woo-epayco-api'),
            'epayco_refecence'                => __('ePayco Reference', 'woo-epayco-api'),
            'customer_title'                   => __('Customer data', 'woo-epayco-api'),
            'donwload_text'  => __('Download ePayco receipt', 'woo-epayco-api'),
        ];
    }

    /**
     * Set credits checkout translations
     *
     * @return void
     */
    private function setSubscriptionCheckoutTranslations(): void
    {
        $this->subscriptionCheckout = [
            'message_error_amount'                                => __('There was an error. Please try again in a few minutes.', 'woo-epayco-api'),
            'test_mode_title'                                     => __('Test Mode', 'woo-epayco-api'),
            'test_mode_description'                               => __('Use ePayco\'s payment methods without real charges. ', 'woo-epayco-api'),
            'test_mode_link_text'                                 => __('See the rules for the test mode.', 'woo-epayco-api'),
            'card_detail'                                         => __('Card details', 'woo-epayco-api'),
            'card_form_title'                                     => __('Subscription', 'woo-epayco-api'),
            'card_holder_name_input_label'                        => __('Holder name as it appears on the card', 'woo-epayco-api'),
            'card_holder_name_input_helper'                       => __('Holder name is required', 'woo-epayco-api'),
            'card_number_input_label'                             => __('Card number', 'woo-epayco-api'),
            'card_number_input_helper'                            => __('Required Card number', 'woo-epayco-api'),
            'card_expiration_input_label'                         => __('Expiration', 'woo-epayco-api'),
            'card_expiration_input_helper'                        => __('Required data', 'woo-epayco-api'),
            'customer_data'                                       => __('Customer data', 'woo-epayco-api'),
            'input_helper_message_expiration_date_invalid_type'   => __('Expiration date invalid', 'woo-epayco-api'),
            'input_helper_message_expiration_date_invalid_length' => __('Expiration date incomplete', 'woo-epayco-api'),
            'input_helper_message_expiration_date_invalid_value'  => __('Expiration date invalid', 'woo-epayco-api'),
            'card_security_code_input_label'                      => __('Security Code', 'woo-epayco-api'),
            'card_security_code_input_helper'                     => __('Required data', 'woo-epayco-api'),
            'input_helper_message_security_code_invalid_type'     => __('Security code is required', 'woo-epayco-api'),
            'input_helper_message_security_code_invalid_length'   => __('Security code incomplete', 'woo-epayco-api'),
            'card_customer_title'                                 => __('Customer data', 'woo-epayco-api'),
            'card_document_input_label'                           => __('Holder document', 'woo-epayco-api'),
            'card_document_input_helper'                          => __('Invalid document', 'woo-epayco-api'),
            'card_holder_address_input_label'                     => __('Address ', 'woo-epayco-api'),
            'card_holder_address_input_helper'                    => __('Holder address is required', 'woo-epayco-api'),
            'card_holder_email_input_label'                       => __('Email', 'woo-epayco-api'),
            'card_holder_email_input_helper'                      => __('Holder email is required', 'woo-epayco-api'),
            'input_helper_message_card_holder_email'              => __('Holder email invalid', 'woo-epayco-api'),
            'input_ind_phone_label'                               => __('Holder Phone', 'woo-epayco-api'),
            'input_ind_phone_helper'                              => __('Invalid Phone', 'woo-epayco-api'),
            'input_country_label'                                 => __('Holder Country', 'woo-epayco-api'),
            'input_country_helper'                                => __('Invalid City', 'woo-epayco-api'),
            'terms_and_conditions_label'                          => __('I confirm and accept the', 'woo-epayco-api'),
            'terms_and_conditions_description'                    => __(' of ePayco.', 'woo-epayco-api'),
            'terms_and_conditions_link_text'                      => __('Terms and conditions', 'woo-epayco-api'),
            'and_the' => __(' and the', 'woo-epayco-api'),
            'personal_data_processing_link_text'                   => __(' personal data processing policy', 'woo-epayco-api'),
            'success_message'                                     => __('Approved transaction', 'woo-epayco-api'),
            'pending_message'                                     => __('Pending transaction', 'woo-epayco-api'),
            'fail_message'                                        => __('Transaction rejected', 'woo-epayco-api'),
            'error_message'                                       => __('Payment has failed', 'woo-epayco-api'),
            'error_description'                                   => __('Please try again later.', 'woo-epayco-api'),
            'payment_method'                                      => __('payment method', 'woo-epayco-api'),
            'dateandtime'                                         => __('Date and time', 'woo-epayco-api'),
            'response'                         => __('Response', 'woo-epayco-api'),
            'totalValue'                       => __('Total value', 'woo-epayco-api'),
            'description'                       => __('Description', 'woo-epayco-api'),
            'reference'                       => __('Reference', 'woo-epayco-api'),
            'purchase'                        => __('Purchase details', 'woo-epayco-api'),
            'iPaddress'                       => __('IP address', 'woo-epayco-api'),
            'receipt'                         => __('Receipt', 'woo-epayco-api'),
            'authorization'                   => __('Authorization', 'woo-epayco-api'),
            'paymentMethod'                   => __('Payment method', 'woo-epayco-api'),
            'epayco_refecence'                => __('ePayco Reference', 'woo-epayco-api'),
            'customer_title'                   => __('Customer data', 'woo-epayco-api'),
            'donwload_text'  => __('Download ePayco receipt', 'woo-epayco-api'),
        ];
    }

    /**
     * Set rejected payment messages translations for buyer
     *
     * @return void
     */
    private function setbuyerRefusedMessagesTranslations(): void
    {
        $this->buyerRefusedMessages = [
            'buyer_cc_rejected_call_for_authorize'          => __('<strong>Your bank needs you to authorize the payment</strong><br>Please call the telephone number on your card or pay with another method.', 'woo-epayco-api'),
            'buyer_cc_rejected_high_risk'                   => __('<strong>For safety reasons, your payment was declined</strong><br>We recommended paying with your usual payment method and device for online purchases.', 'woo-epayco-api'),
            'buyer_rejected_high_risk'                      => __('<strong>For safety reasons, your payment was declined</strong><br>We recommended paying with your usual payment method and device for online purchases.', 'woo-epayco-api'),
            'buyer_cc_rejected_bad_filled_other'            => __('<strong>One or more card details were entered incorrecctly</strong><br>Please enter them again as they appear on the card to complete the payment.', 'woo-epayco-api'),
            'buyer_cc_rejected_bad_filled_security_code'    => __('<strong>One or more card details were entered incorrecctly</strong><br>Please enter them again as they appear on the card to complete the payment.', 'woo-epayco-api'),
            'buyer_cc_rejected_bad_filled_date'             => __('<strong>One or more card details were entered incorrecctly</strong><br>Please enter them again as they appear on the card to complete the payment.', 'woo-epayco-api'),
            'buyer_cc_rejected_bad_filled_card_number'      => __('<strong>One or more card details were entered incorrecctly</strong><br>Please enter them again as they appear on the card to complete the payment.', 'woo-epayco-api'),
            'buyer_cc_rejected_insufficient_amount'         => __('<strong>Your credit card has no available limit</strong><br>Please pay using another card or choose another payment method.', 'woo-epayco-api'),
            'buyer_insufficient_amount'                     => __('<strong>Your debit card has insufficient founds</strong><br>Please pay using another card or choose another payment method.', 'woo-epayco-api'),
            'buyer_cc_rejected_invalid_installments'        => __('<strong>Your card does not accept the number of installments selected</strong><br>Please choose a different number of installments or use a different payment method .', 'woo-epayco-api'),
            'buyer_cc_rejected_card_disabled'               => __('<strong>You need to activate your card</strong><br>Please contact your bank by calling the number on the back of your card or choose another payment method.', 'woo-epayco-api'),
            'buyer_cc_rejected_max_attempts'                => __('<strong>You reached the limit of payment attempts with this card</strong><br>Please pay using another card or choose another payment method.', 'woo-epayco-api'),
            'buyer_cc_rejected_duplicated_payment'          => __('<strong>Your payment was declined because you already paid for this purchase</strong><br>Check your card transactions to verify it.', 'woo-epayco-api'),
            'buyer_bank_error'                              => __('<strong>The card issuing bank declined the payment</strong><br>We recommended paying with another payment method or contact your bank.', 'woo-epayco-api'),
            'buyer_cc_rejected_other_reason'                => __('<strong>The card issuing bank declined the payment</strong><br>We recommended paying with another payment method or contact your bank.', 'woo-epayco-api'),
            'buyer_rejected_by_bank'                        => __('<strong>The card issuing bank declined the payment</strong><br>We recommended paying with another payment method or contact your bank.', 'woo-epayco-api'),
            'buyer_cc_rejected_blacklist'                   => __('<strong>For safety reasons, the card issuing bank declined the payment</strong><br>We recommended paying with your usual payment method and device for online purchases.', 'woo-epayco-api'),
            'buyer_default'                                 => __('<strong>Your payment was declined because something went wrong</strong><br>We recommended trying again or paying with another method.', 'woo-epayco-api'),
        ];
    }

    /**
     * Set common messages translations
     *
     * @return void
     */
    private function setCommonMessagesTranslations(): void
    {
        $this->commonMessages = [
            'cho_default_error'                        => __('A problem was occurred when processing your payment. Please, try again.', 'woo-epayco-api'),
            'cho_form_error'                           => __('A problem was occurred when processing your payment. Are you sure you have correctly filled all information in the checkout form?', 'woo-epayco-api'),
            'cho_see_order_form'                       => __('See your order form', 'woo-epayco-api'),
            'cho_payment_declined'                     => __('Your payment was declined. You can try again.', 'woo-epayco-api'),
            'cho_button_try_again'                     => __('Click to try again', 'woo-epayco-api'),
            'cho_accredited'                           => __('That\'s it, payment accepted!', 'woo-epayco-api'),
            'cho_pending_contingency'                  => __('We are processing your payment. In less than an hour we will send you the result by email.', 'woo-epayco-api'),
            'cho_pending_review_manual'                => __('We are processing your payment. In less than 2 days we will send you by email if the payment has been approved or if additional information is needed.', 'woo-epayco-api'),
            'cho_cc_rejected_bad_filled_card_number'   => __('Check the card number.', 'woo-epayco-api'),
            'cho_cc_rejected_bad_filled_date'          => __('Check the expiration date.', 'woo-epayco-api'),
            'cho_cc_rejected_bad_filled_other'         => __('Check the information provided.', 'woo-epayco-api'),
            'cho_cc_rejected_bad_filled_security_code' => __('Check the informed security code.', 'woo-epayco-api'),
            'cho_cc_rejected_card_error'               => __('Your payment cannot be processed.', 'woo-epayco-api'),
            'cho_cc_rejected_blacklist'                => __('Your payment cannot be processed.', 'woo-epayco-api'),
            'cho_cc_rejected_call_for_authorize'       => __('You must authorize payments for your orders.', 'woo-epayco-api'),
            'cho_cc_rejected_card_disabled'            => __('Contact your card issuer to activate it. The phone is on the back of your card.', 'woo-epayco-api'),
            'cho_cc_rejected_duplicated_payment'       => __('You have already made a payment of this amount. If you have to pay again, use another card or other method of payment.', 'woo-epayco-api'),
            'cho_cc_rejected_high_risk'                => __('Your payment was declined. Please select another payment method. It is recommended in cash.', 'woo-epayco-api'),
            'cho_cc_rejected_insufficient_amount'      => __('Your payment does not have sufficient funds.', 'woo-epayco-api'),
            'cho_cc_rejected_invalid_installments'     => __('Payment cannot process the selected fee.', 'woo-epayco-api'),
            'cho_cc_rejected_max_attempts'             => __('You have reached the limit of allowed attempts. Choose another card or other payment method.', 'woo-epayco-api'),
            'invalid_users'                            => __('<strong>Invalid transaction attempt</strong><br>You are trying to perform a productive transaction using test credentials, or test transaction using productive credentials. Please ensure that you are using the correct environment settings for the desired action.', 'woo-epayco-api'),
            'invalid_operators'                        => __('<strong>Invalid transaction attempt</strong><br>It is not possible to pay with the email address entered. Please enter another e-mail address.', 'woo-epayco-api'),
            'cho_default'                              => __('This payment method cannot process your payment.', 'woo-epayco-api'),
        ];
    }
}