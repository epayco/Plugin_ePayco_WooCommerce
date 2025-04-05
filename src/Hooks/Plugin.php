<?php

namespace Epayco\Woocommerce\Hooks;

class Plugin
{
    public const EXECUTE_ACTIVATE_PLUGIN = 'ep_execute_activate';

    public const UPDATE_TEST_MODE_ACTION = 'epayco_plugin_test_mode_updated';

    public const UPDATE_CREDENTIALS_ACTION = 'epayco_plugin_credentials_updated';

    /**
     * Register to plugin update event
     *
     * @param mixed $callback
     *
     * @return void
     */
    public function registerOnPluginCredentialsUpdate($callback): void
    {
        add_action(self::UPDATE_CREDENTIALS_ACTION, $callback);
    }

    /**
     * Register to plugin test mode update event
     *
     * @param mixed $callback
     *
     * @return void
     */
    public function registerOnPluginTestModeUpdate($callback): void
    {
        add_action(self::UPDATE_TEST_MODE_ACTION, $callback);
    }


    /**
     * Execute plugin activate event
     *
     * @return void
     */
    public function executeActivatePluginAction(): void
    {
        do_action(self::EXECUTE_ACTIVATE_PLUGIN);
    }

    /**
     * Execute credential update event
     *
     * @return void
     */
    public function executeUpdateCredentialAction(): void
    {
        do_action(self::UPDATE_CREDENTIALS_ACTION);
    }

    /**
     * Execute test mode update event
     *
     * @return void
     */
    public function executeUpdateTestModeAction(): void
    {
        do_action(self::UPDATE_TEST_MODE_ACTION);
    }
}