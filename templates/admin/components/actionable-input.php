<?php

/**
 * @var string $field_key
 * @var string $field_key_checkbox
 * @var string $field_value
 * @var string $enabled
 * @var string $custom_attributes
 * @var array $settings
 * @var array $allowedHtmlTags
 *
 * @see \Epayco\Woocommerce\Gateways\AbstractGateway
 */

if (!defined('ABSPATH')) {
    exit;
}

?>

<tr valign="top">
    <th scope="row" class="titledesc ep-pb-0">
        <label for="<?= esc_attr($field_key); ?>">
            <?= esc_html($settings['title']); ?>
            <?php if (isset($settings['desc_tip'])) { ?>
                <span class="woocommerce-help-tip" data-tip="<?= esc_html($settings['desc_tip']); ?>"></span>
            <?php } ?>
            <?php if ($settings['description']) { ?>
                <p class="description ep-activable-input-subtitle"><?= wp_kses_post($settings['description']); ?></p>
            <?php } ?>
        </label>
    </th>

    <td class="forminp">
        <div>
            <fieldset>
                <input
                    class="input-text regular-input"
                    type="<?= esc_attr($settings['input_type']); ?>"
                    name="<?= esc_attr($field_key); ?>"
                    id="<?= esc_attr($field_key); ?>"
                    style="<?= esc_attr(isset($settings['css'])); ?>"
                    value="<?= esc_attr($field_value); ?>"
                    placeholder="<?= esc_attr(isset($settings['placeholder'])); ?>"
                    <?= wp_kses($custom_attributes, $allowedHtmlTags) ?>
                />
                <br/>
                <label for="<?= esc_attr($field_key_checkbox); ?>">
                    <input
                        type="checkbox"
                        name="<?= esc_attr($field_key_checkbox); ?>"
                        id="<?= esc_attr($field_key_checkbox); ?>"
                        value="1"
                        <?= checked($enabled, 'yes'); ?>
                    />
                    <?= wp_kses_post($settings['checkbox_label']); ?>
                </label>
            </fieldset>
        </div>
    </td>
</tr>
