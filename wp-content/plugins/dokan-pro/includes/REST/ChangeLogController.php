<?php

namespace WeDevs\DokanPro\REST;

use WeDevs\Dokan\Abstracts\DokanRESTAdminController;
use WeDevs\Dokan\Cache;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Dokan Changelog handler class
 *
 * @since 3.4.3
 */
class ChangeLogController extends DokanRESTAdminController {
    /**
     * Route base.
     *
     * @var string
     */
    protected $base = 'changelog';

    /**
     * Register all routes related with stores
     *
     * @since 3.4.3
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace, '/' . $this->base . '/pro', [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_change_log' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                ],
            ]
        );
    }

    /**
     * Get Change Logs
     *
     * @since 3.4.3
     *
     * @return WP_REST_Response
     */
    public function get_change_log() {
        $cache_key = 'changelog_pro_' . DOKAN_PRO_PLUGIN_VERSION;
        $changelog = Cache::get_transient( $cache_key );

        if ( false === $changelog ) {
            require_once DOKAN_PRO_TEMPLATE_DIR . '/whats-new.php';
            $changelog = wp_json_encode( $changelog );
            Cache::set_transient( $cache_key, $changelog, '', MONTH_IN_SECONDS );
        }

        return rest_ensure_response( $changelog );
    }
}
