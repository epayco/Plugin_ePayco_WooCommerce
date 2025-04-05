<?php

namespace Epayco\Woocommerce\Helpers;

use WP_User;

if (!defined('ABSPATH')) {
    exit;
}

class CurrentUser
{

    /**
     * Verify if current_user has specifics roles
     *
     * @param array $roles
     *
     * @return bool
     */
    public function userHasRoles(array $roles): bool
    {
        return is_super_admin($this->getCurrentUser()) || !empty(array_intersect($roles, $this->getCurrentUserRoles()));
    }


    /**
     * Get WP current user
     *
     * @return WP_User
     */
    public function getCurrentUser(): WP_User
    {
        return wp_get_current_user();
    }

    /**
     * Get WP current user roles
     *
     * @return array
     */
    public function getCurrentUserRoles(): array
    {
        return $this->getCurrentUser()->roles;
    }

    /**
     * Validate if user has administrator or editor permissions
     *
     * @return void
     */
    public function validateUserNeededPermissions(): void
    {
        $neededRoles = ['administrator', 'manage_woocommerce'];

        if (!$this->userHasRoles($neededRoles)) {
            wp_send_json_error('Forbidden', 403);
        }
    }
}