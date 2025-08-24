<?php 

/**
 * All function definitions
 */

if ( ! defined('ABSPATH')) exit();
if (! defined('PUBLICA_VERSION')) {
    exit;
}



/**
 * Include Rate Limit functionality (By Requester IP Address)
 */
function PbCheckRateLimit() {

    date_default_timezone_set('Asia/Tehran');
    session_start();

    // using the originating IP
    $rateLimiter = new RateLimiter($_SERVER["REMOTE_ADDR"]);

    $limit = 2; // number of connections to limit user to per $minutes
    $minutes = 1;   // number of $minutes to check for.
    $seconds = floor($minutes * 60);	//	retry after $minutes in seconds.

    try {
        $rateLimiter->limitRequestsInMinutes($limit, $minutes);
    } catch (RateExceededException $e) {
        header("HTTP/1.1 429 Too Many Requests");
        header(sprintf("Retry-After: %d", $seconds));
        $data = array(
            'status' => false,
            'message' => 'محدودیت تعداد درخواست. لطفا پس از گذشت یک دقیقه مجددا امتحان کنید'
        );
        die (json_encode($data));
    }

}




/**
 * attached to Activation Hook
 */
function pbPluginActivation() {
    if ( version_compare( $GLOBALS['wp_version'], PUBLICA_MINIMUM_WP_VERSION, '<' ) ) {
        load_plugin_textdomain( 'publica' );

        $message = '<strong>' .
            /* translators: 1: Current publica version number, 2: Minimum WordPress version number required. */
            sprintf( esc_html__( 'Publica %1$s requires WordPress %2$s or higher.', 'publica' ), PUBLICA_VERSION, PUBLICA_MINIMUM_WP_VERSION ) . '</strong> ' .
            /* translators: 1: WordPress documentation URL, 2: publica download URL. */
            sprintf( __( 'Please <a href="%1$s">upgrade WordPress</a> to a current version, or <a href="%2$s">downgrade to version 2.4 of the publica plugin</a>.', 'publica' ), 'https://codex.wordpress.org/Upgrading_WordPress', 'https://wordpress.org/plugins/publica' );

        echo $message;
    } elseif ( ! empty( $_SERVER['SCRIPT_NAME'] ) && false !== strpos( $_SERVER['SCRIPT_NAME'], '/wp-admin/plugins.php' ) ) {
        add_option( 'Activated_publica', true );
    }
}