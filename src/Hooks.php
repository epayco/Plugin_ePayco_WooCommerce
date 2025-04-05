<?php

namespace Epayco\Woocommerce;

use Epayco\Woocommerce\Hooks\Options;
use Epayco\Woocommerce\Hooks\Order;
use Epayco\Woocommerce\Hooks\Plugin;
use Epayco\Woocommerce\Hooks\Admin;
use Epayco\Woocommerce\Hooks\Blocks;
use Epayco\Woocommerce\Hooks\Checkout;
use Epayco\Woocommerce\Hooks\Endpoints;
use Epayco\Woocommerce\Hooks\Scripts;
use Epayco\Woocommerce\Hooks\Gateway;
use Epayco\Woocommerce\Hooks\Template;

if (!defined('ABSPATH')) {
    exit;
}

class Hooks
{
    public Admin $admin;

    public Blocks $blocks;

    public Checkout $checkout;

    public Endpoints $endpoints;

    public Gateway $gateway;

    public Options $options;

    public Order $order;

    public Plugin $plugin;

    public Scripts $scripts;

    public Template $template;

    public function __construct(
        Admin $admin,
        Blocks $blocks,
        Checkout $checkout,
        Endpoints $endpoints,
        Gateway $gateway,
        Options $options,
        Order $order,
        Plugin $plugin,
        Scripts $scripts,
        Template $template
    ){
        $this->admin     = $admin;
        $this->blocks    = $blocks;
        $this->checkout  = $checkout;
        $this->endpoints = $endpoints;
        $this->gateway   = $gateway;
        $this->options   = $options;
        $this->order     = $order;
        $this->plugin    = $plugin;
        $this->scripts   = $scripts;
        $this->template  = $template;
    }


}