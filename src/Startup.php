<?php

namespace Epayco\Woocommerce;

if (!defined('ABSPATH')) {
    exit;
}

class Startup
{
    /**
     * Verify if plugin has its packages and autoloader file
     *
     * @return bool
     */
    public static function available(): bool
    {
        return self::haveAutoload();
    }

    /**
     * Check's if autoload file is present and is readable
     *
     * @return bool
     */
    protected static function haveAutoload(): bool
    {
        $file = dirname(__DIR__) . '/vendor/autoload.php';

        if (is_file($file) && is_readable($file)) {
            return true;
        }

        return false;
    }
}
