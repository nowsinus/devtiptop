<?php

namespace WeDevs\DokanPro\Admin\Notices;

/**
 * What's new notice handler class
 *
 * @since 3.4.3
 */
class WhatsNew {
    /**
     * Class Constructor
     */
    public function __construct() {
        add_action( 'dokan_admin_notices', [ $this, 'show_whats_new_notice' ] );
        add_action( 'wp_ajax_dokan-pro-whats-new-notice', [ $this, 'dismiss_new_notice' ] );
    }

    /**
     * Show update notice
     *
     * @since 1.0
     *
     * @param array $notices
     *
     * @return array
     */
    public function show_whats_new_notice( $notices ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return $notices;
        }

        // check if it has already been dismissed
        $versions = get_option( 'dokan_pro_whats_new_versions', [] );

        if ( in_array( DOKAN_PRO_PLUGIN_VERSION, $versions, true ) ) {
            return $notices;
        }

        $notices[] = [
            'type'              => 'info',
            /* translators: %s: plugin version */
            'title'             => sprintf( __( 'Check What\'s new in Dokan PRO Version %s', 'dokan' ), DOKAN_PRO_PLUGIN_VERSION ),
            'priority'          => 10,
            'show_close_button' => true,
            'ajax_data'         => [
                'action'                    => 'dokan-pro-whats-new-notice',
                'dokan_promotion_dismissed' => true,
                'nonce'                     => wp_create_nonce( 'dokan_admin' ),
            ],
            'actions'           => [
                [
                    'type'   => 'primary',
                    'text'   => __( 'View Details', 'dokan' ),
                    'action' => esc_url( add_query_arg( array( 'page' => 'dokan#/changelog?plugin=dokan-pro' ), admin_url( 'admin.php' ) ) ),
                ],
            ],
        ];

        return $notices;
    }

    /**
     * Dismiss new notice
     *
     * @since 1.0
     *
     * @return void
     */
    public function dismiss_new_notice() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), 'dokan_admin' ) ) {
            wp_send_json_error( __( 'Invalid nonce', 'dokan' ) );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( ! empty( sanitize_key( wp_unslash( $_POST['dokan_promotion_dismissed'] ) ) ) ) {
            $versions = get_option( 'dokan_pro_whats_new_versions', [] );

            if ( ! in_array( DOKAN_PRO_PLUGIN_VERSION, $versions, true ) ) {
                $versions[] = DOKAN_PRO_PLUGIN_VERSION;
            }

            update_option( 'dokan_pro_whats_new_versions', $versions );

            wp_send_json_success();
        }
    }
}
