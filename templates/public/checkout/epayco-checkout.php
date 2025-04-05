<?php

/**
 * @var bool $test_mode
 * @var string $test_mode_title
 * @var string $test_mode_description
 * @var string $amount
 * @var string $message_error_amount
 * @var string $terms_and_conditions_label
 * @var string $terms_and_conditions_description
 * @var string $terms_and_conditions_link_text
 * @var string $terms_and_conditions_link_src
 * @var string $personal_data_processing_link_text
 * @var string $personal_data_processing_link_src
 * @var string $and_the
 * @var string $icon_warning
 * @see \Epayco\Woocommerce\Gateways\CheckoutGateway
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class='ep-checkout-container'>
    <div class="ep-checkout-epayco-container">
        <div class="ep-checkout-epayco-content">
            <?php if ($test_mode) : ?>
                <div class="ep-checkout-ticket-test-mode-epayco">
                    <test-mode-epayco
                        title="<?= esc_html($test_mode_title); ?>"
                        description="<?= esc_html($test_mode_description); ?>"
                        icon-src="<?php echo esc_html($icon_warning); ?>"
                        >
                    </test-mode-epayco>
                </div>
            <?php endif; ?>
            <!-- NOT DELETE LOADING-->
            <div id="ep-box-loading"></div>
        </div>
    </div>
</div>

