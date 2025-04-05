<?php

/**
 * Part of Woo Epayco Module
 * Author - Epayco
 * Developer
 * Copyright - Copyright(c) Epayco [https://www.epayco.com]
 * License - https://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 * @package Epayco
 */

if (!defined('ABSPATH')) {
    exit;
}

?><tr valign="top">
    <th scope="row" class="titledesc">
        <label><?php echo esc_html($settings['title']); ?>
        <?php if (isset($settings['desc_tip'])) { ?>
            <span class="woocommerce-help-tip" data-tip="<?php echo esc_html($settings['desc_tip']); ?>"></span>
        <?php } ?>
        </label>
    </th>
    <td class="forminp">
        <div class="ep-mw-100 ep-component-card">
            <p class="ep-checkbox-list-description"><?php echo esc_html($settings['description']); ?></p>
            <?php foreach ($settings['payment_method_types'] as $key => $payment_method_type) { ?>
            <ul class="ep-list-group">
                <li class="ep-list-group-item">
                    <div class="ep-custom-checkbox">
                        <input class="ep-custom-checkbox-input ep-selectall" id="<?php echo esc_attr($key); ?>_payments" type="checkbox" data-group="<?php echo esc_attr($key); ?>">
                        <label class="ep-custom-checkbox-label" for="<?php echo esc_attr($key); ?>_payments"><b><?php echo esc_html($payment_method_type['label']); ?></b></label>
                    </div>
                </li>
                <?php foreach ($payment_method_type['list'] as $payment_method) { ?>
                <li class="ep-list-group-item">
                    <div class="ep-custom-checkbox">
                        <fieldset>
                            <input class="ep-custom-checkbox-input ep-child" id="<?php echo esc_attr($payment_method['field_key']); ?>" name="<?php echo esc_attr($payment_method['field_key']); ?>" type="checkbox" value="1" data-group="<?php echo esc_attr($key); ?>" <?= checked($payment_method['value'], 'yes'); ?>>
                            <label class="ep-custom-checkbox-label" for="<?php echo esc_attr($payment_method['field_key']); ?>"><?php echo esc_html($payment_method['label']); ?></label>
                        </fieldset>
                    </div>
                </li>
                <?php } ?>
            </ul>
            <?php } ?>
        </div>
    </td>
</tr>
