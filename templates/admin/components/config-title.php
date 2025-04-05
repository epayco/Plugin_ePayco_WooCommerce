<?php

/**
 * @var array $settings
 *
 * @see \Epayco\Woocommerce\Gateways\AbstractGateway
 */

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="row ep-pt-20">
    <div class="ep-col-md-12 ep-subtitle-header">
        <?= esc_html($settings['title']) ?>
    </div>

    <div class="ep-col-md-12">
        <p class="ep-text-checkout-body ep-mb-0">
            <?= esc_html($settings['description']) ?>
        </p>
    </div>
</div>
