<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;
$table_subscription_epayco = $wpdb->prefix . 'epayco_subscription';
$sql = "DROP TABLE IF EXISTS $table_subscription_epayco";
$wpdb->query($sql);