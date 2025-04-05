<?php

use Epayco\Woocommerce\Helpers\Template;

/**
 * @var bool $test_mode
 * @var string $test_mode_title
 * @var string $test_mode_description
 * @var string $test_mode_link_text
 * @var string $test_mode_link_src
 * @var string $site_id
 * @var string $input_name_label
 * @var string $input_name_helper
 * @var string $input_email_label
 * @var string $input_email_helper
 * @var string $input_address_label
 * @var string $input_address_helper
 * @var string $input_ind_phone_label
 * @var string $input_ind_phone_helper
 * @var string $input_country_label
 * @var string $input_country_helper
 * @var string $person_type_label
 * @var string $input_document_label
 * @var string $input_document_helper
 * @var string $daviplata_text_label
 * @var string $input_table_button
 * @var string $payment_methods
 * @var string $input_helper_label
 * @var string $amount
 * @var string $currency_ratio
 * @var string $terms_and_conditions_label
 * @var string $terms_and_conditions_description
 * @var string $terms_and_conditions_link_text
 * @var string $terms_and_conditions_link_src
 * @var string $city
 * @var string $customer_title
 * @var string $logo
 * @var string $personal_data_processing_link_text
 * @var string $personal_data_processing_link_src
 * @var string $and_the
 * @var string $icon_warning
 * @see \Epayco\Woocommerce\Gateways\DaviplataGateway
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class='ep-checkout-container'>
    <div class="ep-checkout-daviplata-container" style="max-width: 452px;margin: auto;">
        <div class="ep-checkout-daviplata-content">
            <?php if ($test_mode) : ?>
                <div class="ep-checkout-daviplata-test-mode-epayco">
                    <test-mode-epayco
                        title="<?= esc_html($test_mode_title); ?>"
                        description="<?= esc_html($test_mode_description); ?>"
                        link-text="<?= esc_html($test_mode_link_text); ?>"
                        link-src="<?= esc_html($test_mode_link_src); ?>"
                        icon-src="<?php echo esc_html($icon_warning); ?>"
                    >
                    </test-mode-epayco>
                </div>
            <?php endif; ?>
            <div style="margin-top: 10px; font-weight: bold; display: flex; align-items: center;">
                <svg width="21" height="16" viewBox="0 0 21 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13.013 8.528H17.7695V7.38514H13.013V8.528ZM13.013 5.36229H17.7695V4.21943H13.013V5.36229ZM3.2305 11.7806H10.9492V11.5909C10.9492 10.9242 10.6069 10.408 9.9225 10.0423C9.23806 9.67657 8.29383 9.49371 7.08983 9.49371C5.88583 9.49371 4.94122 9.67657 4.256 10.0423C3.57078 10.408 3.22894 10.9242 3.2305 11.5909V11.7806ZM7.08983 7.648C7.58217 7.648 7.99672 7.48305 8.3335 7.15314C8.67105 6.82247 8.83983 6.416 8.83983 5.93371C8.83983 5.45143 8.67105 5.04533 8.3335 4.71543C7.99594 4.38552 7.58139 4.22019 7.08983 4.21943C6.59828 4.21867 6.18372 4.384 5.84617 4.71543C5.50861 5.04686 5.33983 5.45295 5.33983 5.93371C5.33983 6.41448 5.50861 6.82095 5.84617 7.15314C6.18372 7.48533 6.59828 7.65028 7.08983 7.648ZM1.88533 16C1.34789 16 0.8995 15.824 0.540167 15.472C0.180833 15.12 0.000777778 14.6804 0 14.1531V1.84686C0 1.32038 0.180056 0.881142 0.540167 0.529143C0.900278 0.177143 1.34828 0.000761905 1.88417 0H19.1158C19.6525 0 20.1005 0.176381 20.4598 0.529143C20.8192 0.881904 20.9992 1.32114 21 1.84686V14.1543C21 14.68 20.8199 15.1192 20.4598 15.472C20.0997 15.8248 19.6517 16.0008 19.1158 16H1.88533ZM1.88533 14.8571H19.1158C19.2947 14.8571 19.4592 14.784 19.6093 14.6377C19.7594 14.4914 19.8341 14.3299 19.8333 14.1531V1.84686C19.8333 1.67086 19.7587 1.50933 19.6093 1.36229C19.46 1.21524 19.2955 1.1421 19.1158 1.14286H1.88417C1.70528 1.14286 1.54078 1.216 1.39067 1.36229C1.24056 1.50857 1.16589 1.6701 1.16667 1.84686V14.1543C1.16667 14.3295 1.24133 14.4907 1.39067 14.6377C1.54 14.7848 1.7045 14.8579 1.88417 14.8571" fill="black"/>
                </svg>
                <p style="margin-left: 10px;"><?= esc_html($customer_title) ?></p>
            </div>
            <div id="ep-custom-checkout-form-container" style="margin: 10px;">
                <div class="ep-checkout-daviplata-input-document">
                    <input-name-epayco
                        labelMessage="<?= esc_html($input_name_label); ?>"
                        helperMessage="<?= esc_html($input_name_helper); ?>"
                        placeholder="Ex: John Doe"
                        inputName='epayco_daviplata[name]'
                        flagError='epayco_daviplata[nameError]'
                        validate=true
                        hiddenId="hidden-name-daviplata"
                    >
                    </input-name-epayco>
                </div>

                <div class="ep-checkout-daviplata-input-document">
                    <input-email-epayco
                        labelMessage="<?= esc_html($input_email_label); ?>"
                        helperMessage="<?= esc_html($input_email_helper); ?>"
                        placeholder="Johndoe@example.com"
                        inputName='epayco_daviplata[email]'
                        flagError='epayco_daviplata[emailError]'
                        validate=true
                        hiddenId= "hidden-email-daviplata"
                    >
                    </input-email-epayco>
                </div>

                <!--<div class="ep-checkout-daviplata-input-document">
                    <input-address
                        labelMessage="<?= esc_html($input_address_label); ?>"
                        helperMessage="<?= esc_html($input_address_helper); ?>"
                        placeholder="Street 123"
                        inputName='epayco_daviplata[address]'
                        flagError='epayco_daviplata[addressError]'
                        validate=true
                        hiddenId= "hidden-address-daviplata"
                    >
                    </input-address>
                </div>-->

                <div class="ep-checkout-daviplata-input-document">
                    <input-cellphone-epayco
                        label-message="<?= esc_html($input_ind_phone_label); ?>"
                        helper-message="<?= esc_html($input_ind_phone_helper); ?>"
                        input-name-epayco='epayco_daviplata[cellphone]'
                        hidden-id="cellphoneType"
                        input-data-checkout="cellphone_number"
                        select-id="cellphoneType"
                        input-id="cellphoneTypeNumber"
                        select-name="epayco_daviplata[cellphoneType]"
                        select-data-checkout="cellphone_type"
                        flag-error="cellphoneTypeError"
                        validate=true
                        placeholder="0000000000"
                    >
                    </input-cellphone-epayco>
                </div>

                <!--<div class="ep-checkout-daviplata-input-document">
                    <input-select
                        name="epayco_daviplata[person_type]"
                        label=<?= esc_html($person_type_label); ?>
                        optional="false"
                        options='[{"id":"PN", "description": "Persona natural"},{"id":"PJ", "description": "Persona jurídica"}]'
                    >
                    </input-select>
                </div>-->

                <div class="ep-checkout-daviplata-input-document">
                    <input-document-epayco
                        label-message="<?= esc_html($input_document_label); ?>"
                        helper-message="<?= esc_html($input_document_helper); ?>"
                        input-name-epayco='epayco_daviplata[document]'
                        hidden-id="documentType"
                        input-data-checkout="document_number"
                        select-id="documentType"
                        input-id="documentTypeNumber"
                        select-name="epayco_daviplata[documentType]"
                        select-data-checkout=document_type"
                        flag-error="documentTypeError"
                        documents='[
                                    {"id":"Type"},
                                    {"id":"CC"},
                                    {"id":"CE"},
                                    {"id":"NIT"},
                                    {"id":"TI"},
                                    {"id":"PPN"},
                                    {"id":"SSN"},
                                    {"id":"LIC"},
                                    {"id":"DNI"}
                                    ]'
                        validate=true
                        placeholder="0000000000"
                    >
                    </input-document-epayco>
                </div>

                <!--<div class="ep-checkout-daviplata-input-document">
                    <input-country
                        label-message="<?= esc_html($input_country_label); ?>"
                        helper-message="<?= esc_html($input_country_helper); ?>"
                        input-name-epayco='epayco_daviplata[country]'
                        hidden-id="countryType"
                        input-data-checkout="country_number"
                        select-id="countryType"
                        input-id="countryTypeNumber"
                        select-name="epayco_daviplata[countryType]"
                        select-data-checkout="doc_type"
                        flag-error="countryTypeError"
                        validate=true
                        placeholder="<?= esc_html($city); ?>"
                    >
                    </input-country>
                </div>-->


                <!-- NOT DELETE LOADING-->
                <div id="ep-box-loading"></div>

                <!-- utilities -->
                <div id="epayco-utilities" style="display:none;">
                    <input type="hidden" id="site_id" value="<?= esc_textarea($site_id); ?>" name="epayco_daviplata[site_id]" />
                    <input type="hidden" id="daviplata_amount" value="<?= esc_textarea($amount); ?>" name="epayco_daviplata[amount]" />
                    <input type="hidden" id="daviplata_campaign_id" name="epayco_daviplata[campaign_id]" />
                    <input type="hidden" id="daviplata_campaign" name="epayco_daviplata[campaign]" />
                    <input type="hidden" id="daviplata_discount" name="epayco_daviplata[discount]" />
                </div>
            </div>
        </div>
        <div style="margin-top: 15px;"></div>

        <div  style="margin-left:21px" class="ep-checkout-daviplata-terms-and-conditions">
            <terms-and-conditions
                    label="<?= esc_html($terms_and_conditions_label); ?>"
                    description="<?= esc_html($terms_and_conditions_description); ?>"
                    link-text="<?= esc_html($terms_and_conditions_link_text); ?>"
                    link-src="<?= esc_html($terms_and_conditions_link_src); ?>"
                    link-condiction-text="<?= esc_html($personal_data_processing_link_text); ?>"
                    and_the="<?= esc_html($and_the); ?>"
                    link-condiction-src="<?= esc_html($personal_data_processing_link_src); ?>"
            >
            </terms-and-conditions>
        </div>
        <div style="display: flex;justify-content: center; align-items: center;padding: 15px;">
            <p>Secure by</p>
            <img width="65px" src="<?php echo esc_html($logo); ?>">
        </div>
    </div>
</div>

<script type="text/javascript">
    if (document.getElementById("payment_method_woo-epayco-custom")) {
        jQuery("form.checkout").on("checkout_place_order_woo-epayco-daviplata", function() {
            cardFormLoad();
        });
    }
</script>