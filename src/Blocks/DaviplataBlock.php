<?php

namespace Epayco\Woocommerce\Blocks;

use Epayco\Woocommerce\Helpers\Template;

if (!defined('ABSPATH')) {
    exit;
}

class DaviplataBlock extends AbstractBlock
{
    protected $scriptName = 'daviplata';

    protected $name = 'woo-epayco-daviplata';

    /**
     * CustomBlock constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->storeTranslations = $this->epayco->storeTranslations->daviplataCheckout;
    }

    /**
     * Set payment block script params
     *
     * @return array
     */
    public function getScriptParams(): array
    {
        return [
            'content' => Template::html(
                'public/checkout/daviplata-checkout',
                $this->gateway->getPaymentFieldsParams()
            ),
        ];
    }
}
