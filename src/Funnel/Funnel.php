<?php

namespace Epayco\Woocommerce\Funnel;

use Exception;
use Epayco\Woocommerce\Helpers\Gateways;
class Funnel
{
    private Gateways $gateways;

    /**
     * Funnel constructor
     *
     * @param Gateways $gateways
     */
    public function __construct(Gateways $gateways)
    {
        $this->gateways = $gateways;
    }

    /**
     * Create seller funnel
     */
    public function create(?\Closure $after = null): void
    {
        if (!$this->canCreate()) {
            return;
        }
    }

    public function created(): bool
    {
        return true;
    }

    public function updateStepCredentials(?\Closure $after = null): void
    {
        $this->update([
            'plugin_mode'                    => $this->getPluginMode(),

        ], $after);
    }

    /**
     * @return void
     */
    public function updateStepPaymentMethods(?\Closure $after = null): void
    {
        $this->update(['accepted_payments' => $this->gateways->getEnabledPaymentGateways()], $after);
    }


    public function updateStepPluginMode(?\Closure $after = null): void
    {
        $this->update(['plugin_mode' => $this->getPluginMode()], $after);
    }

    public function updateStepUninstall(?\Closure $after = null): void
    {
        $this->update(['is_deleted' => true], $after);
    }

    public function updateStepDisable(?\Closure $after = null): void
    {
        $this->update(['is_disabled' => true], $after);
    }

    public function updateStepActivate(?\Closure $after = null): void
    {
        $this->update(['is_disabled' => false], $after);
    }

    public function updateStepPluginVersion(?\Closure $after = null): void
    {
        $this->update(['plugin_version' => EP_VERSION], $after);
    }

    /**
     * Update seller funnel using the given attributes
     *
     * @param array $attrs Funnel attribute values map
     * @param \Closure $after Function to run after funnel updated, inside treatment
     */
    private function update(array $attrs, ?\Closure $after = null): void
    {
        if (!$this->created()) {
            return;
        }

    }

    private function canCreate(): bool
    {
        return !$this->created() && empty($this->gateways->getEnabledPaymentGateways());
    }

    private function getPluginMode(): string
    {
        return  'Test';
    }

    private function getWoocommerceVersion(): string
    {
        return $GLOBALS['woocommerce']->version ?? "";
    }


    private function runWithTreatment(\Closure $callback): void
    {
        try {
            $callback();

        } catch (Exception $ex) {
            $GLOBALS['epayco']->logs->file->error(sprintf("Error on %s\n%s", __METHOD__, $ex), __CLASS__);
        }
    }
}