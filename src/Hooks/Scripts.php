<?php

namespace Epayco\Woocommerce\Hooks;
use Epayco\Woocommerce\Helpers\Url;

if (!defined('ABSPATH')) {
    exit;
}
class Scripts
{
    private const SUFFIX = '_params';

    private const NOTICES_SCRIPT_NAME = 'wc_epayco_notices';

    private Url $url;

    /**
     * Scripts constructor
     *
     * @param Url $url
     */
    public function __construct(Url $url)
    {
        $this->url    = $url;
    }

    /**
     * Register styles on admin
     *
     * @param string $name
     * @param string $file
     *
     * @return void
     */
    public function registerAdminStyle(string $name, string $file): void
    {
        add_action('admin_enqueue_scripts', function () use ($name, $file) {
            $this->registerStyle($name, $file);
        });
    }

    /**
     * Register scripts on admin
     *
     * @param string $name
     * @param string $file
     * @param array $variables
     *
     * @return void
     */
    public function registerAdminScript(string $name, string $file, array $variables = []): void
    {
        add_action('admin_enqueue_scripts', function () use ($name, $file, $variables) {
            $this->registerScript($name, $file, $variables);
        });
    }

    /**
     * Register styles on checkout
     *
     * @param string $name
     * @param string $file
     *
     * @return void
     */
    public function registerCheckoutStyle(string $name, string $file): void
    {
        add_action('wp_enqueue_scripts', function () use ($name, $file) {
            $this->registerStyle($name, $file);
        });
    }

    /**
     * Register scripts on checkout
     *
     * @param string $name
     * @param string $file
     * @param array $variables
     *
     * @return void
     */
    public function registerCheckoutScript(string $name, string $file, array $variables = []): void
    {
        add_action('wp_enqueue_scripts', function () use ($name, $file, $variables) {
            $this->registerScript($name, $file, $variables);
        });
    }

    /**
     * Register scripts for payment block
     *
     * @param string $name
     * @param string $file
     * @param string $version
     * @param array $deps
     * @param array $variables
     *
     * @return void
     */
    public function registerPaymentBlockScript(string $name, string $file, string $version, array $deps = [], array $variables = []): void
    {
        wp_register_script($name, $file, $deps, $version, true);
        if ($variables) {
            wp_localize_script($name, $name . self::SUFFIX, $variables);
        }
    }

    public function registerPaymentBlockStyle(string $name, string $file): void
    {
        add_action('enqueue_block_assets', fn() => $this->registerStyle($name, $file));
    }

    /**
     * Register styles
     *
     * @param string $name
     * @param string $file
     *
     * @return void
     */
    private function registerStyle(string $name, string $file): void
    {
        wp_register_style($name, $file, [], $this->url->assetVersion());
        wp_enqueue_style($name);
    }

    /**
     * Register scripts
     *
     * @param string $name
     * @param string $file
     * @param array $variables
     *
     * @return void
     */
    private function registerScript(string $name, string $file, array $variables = []): void
    {
        wp_enqueue_script($name, $file, [], $this->url->assetVersion(), true);

        if ($variables) {
            wp_localize_script($name, $name . self::SUFFIX, $variables);
        }
    }

    /**
     * Register notices script on admin
     *
     * @return void
     */
    public function registerNoticesAdminScript(): void
    {
        global $woocommerce;

        $variables = [
            'site_id'          => 'epayco',
            'container'        => '#wpbody-content',
            'public_key'       => 'epayco_public',
            'plugin_version'   => EP_VERSION,
            'platform_id'      => 'woocommerce',
            'platform_version' => $woocommerce->version,
        ];

        //$this->registerAdminScript(self::NOTICES_SCRIPT_NAME, null, $variables);
    }

    /**
     * Register scripts on store
     *
     * @param string $name
     * @param string $file
     * @param array $variables
     *
     * @return void
     */
    public function registerStoreScript(string $name, string $file, array $variables = []): void
    {
        $this->registerScript($name, $file, $variables);
    }

    /**
     * Register styles on store
     *
     * @param string $name
     * @param string $file
     *
     * @return void
     */
    public function registerStoreStyle(string $name, string $file): void
    {
        $this->registerStyle($name, $file);
    }


}