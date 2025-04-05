<?php

/**
 * @var string $field_key
 * @var string $field_value
 * @var array  $settings
 *
 * @see \Epayco\Woocommerce\Gateways\AbstractGateway
 */

if (!defined('ABSPATH')) {
    exit;
}

?>

<tr valign="top">
    <th scope="row" class="titledesc">
        <label for="<?= esc_attr($field_key); ?>">
            <?= esc_html($settings['title']) ?>

            <?php if (isset($settings['desc_tip'])) : ?>
                <span class="woocommerce-help-tip" data-tip="<?= esc_html($settings['desc_tip']) ?>"></span>
            <?php endif; ?>

            <?php if (isset($settings['title_badge'])) : ?>
                <span class="woocommerce-help-tip" data-tip="<?= esc_html($settings['title_badge']) ?>"></span>
            <?php endif; ?>

            <?php if ($settings['subtitle']) : ?>
                <p class="description ep-toggle-subtitle"><?= wp_kses_post($settings['subtitle']) ?></p>
            <?php endif; ?>
        </label>
    </th>

    <td class="forminp">
        <div class="ep-component-card">
            <label class="ep-toggle">
                <input
                    id="<?= esc_attr($field_key) ?>"
                    name="<?= esc_attr($field_key) ?>"
                    class="ep-toggle-checkbox"
                    type="checkbox"
                    value="yes"
                    <?= checked($field_value, 'yes') ?>
                />

                <div class="ep-toggle-switch"></div>

                <div class="ep-toggle-label">
                    <span class="ep-toggle-label-enabled"><?= wp_kses($settings['descriptions']['enabled'], 'b') ?></span>
                    <span class="ep-toggle-label-disabled"><?= wp_kses($settings['descriptions']['disabled'], 'b') ?></span>
                </div>
            </label>
        </div>

        <?php
        if (isset($settings['after_toggle'])) {
            echo wp_kses_post($settings['after_toggle']);
        }
        ?>
    </td>
</tr>
