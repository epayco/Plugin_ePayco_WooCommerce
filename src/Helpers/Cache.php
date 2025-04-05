<?php

namespace Epayco\Woocommerce\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

class Cache
{
    /**
     * Get cache on database
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getCache(string $key)
    {
        return get_transient(sha1($key));
    }

    /**
     * Set cache on database
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl
     *
     * @return void
     */
    public function setCache(string $key, $value, int $ttl = 60)
    {
        set_transient(sha1($key), $value, $ttl);
    }

    /**
     * Delete cache from database
     *
     * @param string $key
     *
     * @return void
     */
    public function deleteCache(string $key)
    {
        delete_transient(sha1($key));
    }
}
