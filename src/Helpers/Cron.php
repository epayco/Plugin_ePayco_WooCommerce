<?php

namespace Epayco\Woocommerce\Helpers;

use WP_User;

if (!defined('ABSPATH')) {
    exit;
}

class Cron
{
    /**
     * Register an scheduled event
     *
     * @return void
     */
    public function registerScheduledEvent(string $periodicy, $hook): void
    {
        try {
            if (!wp_next_scheduled($hook)) {
                wp_schedule_event(time(), $periodicy, $hook);
            }
            if ( function_exists( 'as_next_scheduled_action' ) && false === as_next_scheduled_action( $hook ) ) {
                //as_schedule_recurring_action(time() + 3600, 3600, $hook );
            }
        } catch (\Exception $ex) {
            if ( class_exists( 'WC_Logger' ) ) {
                $logger = new \WC_Logger();
                $logger->add( 'ePayco',"Unable to unregister event {$hook}, got error: {$ex->getMessage()}" );
            }
        }
    }

    /**
     * Unregister an scheduled event
     *
     * @return void
     */
    public function unregisterScheduledEvent(string $hook): void
    {
        try {
            $timestamp = wp_next_scheduled($hook);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $hook);
            }
            //wp_clear_scheduled_hook($hook);
            //as_unschedule_action($hook);

        } catch (\Exception $ex) {
            if ( class_exists( 'WC_Logger' ) ) {
                $logger = new \WC_Logger();
                $logger->add( 'ePayco',"Unable to unregister event {$hook}, got error: {$ex->getMessage()}" );
            }
        }
    }
}