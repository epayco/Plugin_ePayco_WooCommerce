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

<div class="ep-card-info">
    <div class="<?= esc_html($settings['value']['color_card']); ?>"></div>

    <div class="ep-card-body-payments <?= esc_html($settings['value']['size_card']); ?>">
        <!--<div class="<?= esc_html($settings['value']['icon']); ?>"></div>-->
        <img src="<?= esc_html($settings['value']['icon']); ?>" alt="info" style="height: 25px;margin: 15px">
        <div>
            <span class="ep-text-title"><b><?= esc_html($settings['value']['title']); ?></b></span>
            <span class="ep-text-subtitle"><?= wp_kses($settings['value']['subtitle'], 'b'); ?></span>
            <a class="ep-button-payments-a" target="<?= esc_html($settings['value']['target']); ?>"
               href="<?= esc_html($settings['value']['button_url']); ?>">
                <button type="button"
                        class="ep-button-payments"><?= esc_html($settings['value']['button_text']); ?></button>
            </a>
        </div>
    </div>
</div>
