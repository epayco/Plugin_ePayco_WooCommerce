<?php

/**
 * @var array $headerTranslations
 * @var array $credentialsTranslations
 * @var array $gatewaysTranslations
 * @var array $testModeTranslations
 * @var string $pcustid
 * @var string $publicKey
 * @var string $privateKey
 * @var string $pKey
 * @var array $links
 * @var bool  $testMode
 * @var array $allowedHtmlTags
 *
 * @see \EpaycoSubscription\Woocommerce\Admin\Settings
 */

if (!defined('ABSPATH')) {
    exit;
}

?>

<script>
    window.addEventListener("load", function() {
        mp_settings_screen_load();
    });
</script>

<span id='reference' value='{"ep-screen-name":"admin"}'></span>

<div class="ep-settings">
    <div class="ep-settings-header">
        <div class="ep-settings-header-img"></div>
        <div>
            <div class="ep-settings-header-logo"></div>

                <?= wp_kses($headerTranslations['title_header'], $allowedHtmlTags) ?>

        </div>
    </div>


    <p style="font-size: 24px;margin: 10px 0px 10px;font-weight: 600; color: #16161D; "> <?= wp_kses($headerTranslations['configuration'], $allowedHtmlTags) ?></p>

    <div class="ep-settings-credentials" style="margin: 10px 0px">
        <div id="ep-settings-step-one" class="ep-settings-title-align">
            <div class="ep-settings-title-container">
                <span class="ep-settings-font-color ep-settings-title-blocks ep-settings-margin-right">
                    <?= wp_kses($credentialsTranslations['title_credentials'], $allowedHtmlTags) ?>
                </span>
                <img class="ep-settings-margin-left ep-settings-margin-right" id="ep-settings-icon-credentials">
            </div>
            <div class="ep-settings-title-container ep-settings-margin-left">
                <img class="ep-settings-icon-open" id="ep-credentials-arrow-up">
            </div>
        </div>

        <div id="ep-step-1" class="ep-settings-block-align-top dropdown-hidden" >
            <div style="display: none">
                <p class="ep-settings-subtitle-font-size ep-settings-title-color">
                    <?= wp_kses($credentialsTranslations['first_text_subtitle_credentials'], $allowedHtmlTags) ?>
                    <a id="ep-get-credentials-link" class="ep-settings-blue-text" target="_blank" href="<?= wp_kses($links['epayco_credentials'], $allowedHtmlTags) ?>">
                        <?= wp_kses($credentialsTranslations['text_link_credentials'], $allowedHtmlTags) ?>
                    </a>
                    <?= wp_kses($credentialsTranslations['second_text_subtitle_credentials'], $allowedHtmlTags) ?>
                </p>
            </div>
            <div class="ep-message-credentials"></div>

            <div id="msg-info-credentials"></div>

            <div class="ep-container">
                <div class="ep-block ep-block-flex ep-settings-margin-right" hidden="true">
                    <fieldset class="ep-settings-fieldset">
                        <input type="text" id="ep-public-key-prod" class="ep-settings-input" value="" placeholder="" />
                    </fieldset>
                    <fieldset>
                        <input type="text" id="ep-access-token-prod" class="ep-settings-input" value="" placeholder="" />
                    </fieldset>
                </div>

                <div class="ep-block ep-block-flex ep-settings-margin-right" hidden="true">
                    <fieldset class="ep-settings-fieldset">
                        <input type="text" id="ep-public-key-test" class="ep-settings-input" value="" placeholder="" />
                    </fieldset>

                    <fieldset>
                        <input type="text" id="ep-access-token-test" class="ep-settings-input" value="" placeholder="" />
                    </fieldset>
                </div>

                <div id="credentials-setup" class="ep-block ep-block-flex ep-settings-margin-right">
                    <!--<p class="ep-settings-title-font-size">
                        <b><?= wp_kses($credentialsTranslations['title_credential'], $allowedHtmlTags) ?></b>
                    </p>-->
                    <fieldset class="ep-settings-fieldset">
                        <label for="ep-p_cust_id" class="ep-settings-label ep-settings-font-color">
                            <?= wp_kses($credentialsTranslations['p_cust_id'], $allowedHtmlTags) ?> <span style="color: red;">&nbsp;*</span>
                        </label>
                        <input type="text" id="ep-p_cust_id" class="ep-settings-input" value="<?= wp_kses($pcustid, $allowedHtmlTags) ?>" placeholder="<?= wp_kses($credentialsTranslations['placeholder_p_cust_id'], $allowedHtmlTags) ?>" />
                    </fieldset>

                    <fieldset class="ep-settings-fieldset">
                        <label for="ep-p_key" class="ep-settings-label ep-settings-font-color">
                            <?= wp_kses($credentialsTranslations['p_key'], $allowedHtmlTags) ?> <span style="color: red;">&nbsp;*</span>
                        </label>
                        <input type="text" id="ep-p_key" class="ep-settings-input" value="<?= wp_kses($pKey, $allowedHtmlTags) ?>" placeholder="<?= wp_kses($credentialsTranslations['placeholder_p_key'], $allowedHtmlTags) ?>" />
                    </fieldset>

                    <fieldset class="ep-settings-fieldset">
                        <label for="ep-publicKey" class="ep-settings-label ep-settings-font-color">
                            <?= wp_kses($credentialsTranslations['publicKey'], $allowedHtmlTags) ?> <span style="color: red;">&nbsp;*</span>
                        </label>
                        <input type="password" id="ep-publicKey" class="ep-settings-input" value="<?= wp_kses($publicKey, $allowedHtmlTags) ?>" placeholder="<?= wp_kses($credentialsTranslations['placeholder_publicKey'], $allowedHtmlTags) ?>" />
                        <span class="ep-credential-show-password show-password-1"></span>
                        <div>

                        </div>
                    </fieldset class="ep-settings-fieldset">

                    <fieldset class="ep-settings-fieldset">
                        <label for="ep-private_key" class="ep-settings-label ep-settings-font-color">
                            <?= wp_kses($credentialsTranslations['private_key'], $allowedHtmlTags) ?> <span style="color: red;">&nbsp;*</span>
                        </label>
                        <input type="password" id="ep-private_key" class="ep-settings-input" value="<?= wp_kses($privateKey, $allowedHtmlTags) ?>" placeholder="<?= wp_kses($credentialsTranslations['placeholder_private_key'], $allowedHtmlTags) ?>" />
                        <span class="ep-credential-show-password show-password-2"></span>
                    </fieldset>

                </div>
                <div class="loader" id="loader"></div>
            </div>

            <button class="ep-button ep-button-large" id="ep-btn-credentials">
                <?= wp_kses($credentialsTranslations['button_credentials'], $allowedHtmlTags) ?>
            </button>
        </div>
    </div>

    <div class="ep-settings-payment" style="margin: 10px 0px">
        <div id="ep-settings-step-three" class="ep-settings-title-align">
            <div class="ep-settings-title-container">
                <span class="ep-settings-font-color ep-settings-title-blocks ep-settings-margin-right">
                    <?= wp_kses($gatewaysTranslations['title_payments'], $allowedHtmlTags) ?>
                </span>
                <img class="ep-settings-margin-left ep-settings-margin-right" id="ep-settings-icon-payment">
            </div>

            <div class="ep-settings-title-container ep-settings-margin-left">
                <img class="ep-settings-icon-open" id="ep-payments-arrow-up" />
            </div>
        </div>
        <div id="ep-step-3" class="ep-settings-block-align-top dropdown-hidden">
            <p  id="ep-payment" class="ep-settings-subtitle-font-size ep-settings-title-color" hidden="hidden">
                <?php /* wp_kses($gatewaysTranslations['subtitle_payments'], $allowedHtmlTags)*/ ?>
            </p>
            <button id="ep-payment-method-continue" class="ep-button ep-button-large">
                <?= wp_kses($gatewaysTranslations['button_payment'], $allowedHtmlTags) ?>
            </button>
        </div>
    </div>

    <div class="ep-settings-mode" style="margin: 10px 0px">
        <div id="ep-settings-step-four" class="ep-settings-title-align">
            <div class="ep-settings-title-container">
                <div class="ep-align-items-center">
                    <span class="ep-settings-font-color ep-settings-title-blocks ep-settings-margin-right">
                        <?= wp_kses($testModeTranslations['title_test_mode'], $allowedHtmlTags) ?>
                    </span>
                    <div id="ep-mode-badge" class="ep-settings-margin-left ep-settings-margin-right <?= $testMode ? 'ep-settings-test-mode-epayco-alert' : 'ep-settings-prod-mode-alert' ?>">
                        <span id="ep-mode-badge-test" style="display: <?= $testMode ? 'block' : 'none' ?>">
                            <?= wp_kses($testModeTranslations['badge_test'], $allowedHtmlTags) ?>
                        </span>
                        <span id="ep-mode-badge-prod" style="display: <?= $testMode ? 'none' : 'block' ?>">
                            <?= wp_kses($testModeTranslations['badge_mode'], $allowedHtmlTags) ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="ep-settings-title-container ep-settings-margin-left">
                <img class="ep-settings-icon-open" id="ep-modes-arrow-up" />
            </div>
        </div>

        <div id="ep-step-4" class="ep-message-test-mode-epayco ep-settings-block-align-top dropdown-hidden">
            <p class="ep-heading-test-mode-epayco ep-settings-subtitle-font-size ep-settings-title-color" style="display: none">
                <!--<?= wp_kses($testModeTranslations['subtitle_test_mode'], $allowedHtmlTags) ?>-->
            </p>

            <div class="ep-container">
                <div class="ep-block ep-settings-choose-mode">
                    <div>
                        <p class="ep-settings-title-font-size">
                            <!--<b><?= wp_kses($testModeTranslations['title_mode'], $allowedHtmlTags) ?></b>-->
                        </p>
                    </div>

                    <div class="ep-settings-mode-container">
                        <div class="ep-settings-mode-spacing">
                            <input type="radio" id="ep-settings-testmode-test" class="ep-settings-radio-button" name="ep-test-prod" value="yes" <?= checked($testMode) ?> />
                        </div>
                        <label for="ep-settings-testmode-test">
                            <span class="ep-settings-subtitle-font-size ep-settings-font-color">
                                <?= wp_kses($testModeTranslations['title_test'], $allowedHtmlTags) ?>
                            </span>
                            <br />
                            <span class="ep-settings-subtitle-font-size ep-settings-title-color">
                                <?= wp_kses($testModeTranslations['subtitle_test'], $allowedHtmlTags) ?>
                                <span>
                                    <!--<a id="ep-test-mode-epayco-rules-link" class="ep-settings-blue-text" target="_blank" href="<?= wp_kses($links['docs_integration_test'], $allowedHtmlTags) ?>">
                                        <?= wp_kses($testModeTranslations['subtitle_test_link'], $allowedHtmlTags) ?>
                                    </a>-->
                        </label>
                    </div>

                    <div class="ep-settings-mode-container">
                        <div class="ep-settings-mode-spacing">
                            <input type="radio" id="ep-settings-testmode-prod" class="ep-settings-radio-button" name="ep-test-prod" value="no" <?= checked(!$testMode) ?> />
                        </div>
                        <label for="ep-settings-testmode-prod">
                            <span class="ep-settings-subtitle-font-size ep-settings-font-color">
                                <?= wp_kses($testModeTranslations['title_prod'], $allowedHtmlTags) ?>
                            </span>
                            <br />
                            <span class="ep-settings-subtitle-font-size ep-settings-title-color">
                                <?= wp_kses($testModeTranslations['subtitle_prod'], $allowedHtmlTags) ?>
                            </span>
                        </label>
                    </div>

                    <div class="ep-settings-alert-payment-methods" style="display:none;">
                        <div id="ep-red-badge" class="ep-settings-alert-red"></div>

                    </div>

                    <div class="ep-settings-alert-payment-methods">
                        <div ></div>
                        <div  id="ep-orange-badge" class="<?= $testMode ? 'ep-settings-alert-payment-methods-yellow' : 'ep-settings-alert-payment-methods-green' ?>">
                            <div class="ep-settings-margin-right ep-settings-mode-style">
                                <span id="ep-icon-badge" class="<?= $testMode ? 'ep-settings-icon-warning' : 'ep-settings-icon-success' ?>"></span>
                            </div>

                            <div class="ep-settings-mode-warning">
                                <div class="ep-settings-margin-left">
                                    <div class="ep-settings-alert-mode-title">
                                        <span id="ep-title-helper-prod" style="display: <?= $testMode ? 'none' : 'block' ?>">
                                            <span id="ep-text-badge" class="ep-display-block"> <?= wp_kses($testModeTranslations['title_message_prod'], $allowedHtmlTags) ?></span>
                                        </span>
                                        <span id="ep-title-helper-test" style="display: <?= $testMode ? 'block' : 'none' ?>">
                                            <span id="ep-text-badge" class="ep-display-block"><?= wp_kses($testModeTranslations['title_message_test'], $allowedHtmlTags) ?></span>
                                        </span>
                                    </div>

                                    <div id="ep-helper-badge-div" class="ep-settings-alert-mode-body ep-settings-font-color">
                                        <span id="ep-helper-prod" style="display: <?= $testMode ? 'none' : 'block' ?>">
                                            <!--<?= wp_kses($testModeTranslations['subtitle_message_prod'], $allowedHtmlTags) ?>-->
                                        </span>
                                        <span id="ep-helper-test" style="display: <?= $testMode ? 'block' : 'none' ?>">

                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button class="ep-button ep-button-large" id="ep-store-mode-save">
                <?= wp_kses($testModeTranslations['button_test_mode'], $allowedHtmlTags) ?>
            </button>
        </div>
    </div>

    <div id="ep-step-5" style="display: none;"></div>

</div>