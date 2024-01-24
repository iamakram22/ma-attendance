<?php
/*
 * Plugin Name:       WP Attendance
 * Plugin URI:        https://github.com/iamakram22/wp-attendance.git
 * Description:       WP Attendance is a simple and effective plugin for taking daily attendance of users on your WordPress site.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Mohd Akram
 * Author URI:        https://iamakram.com
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.en.html
 * Text Domain:       wp-attendance
 * Domain Path:       /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_Attendance {

    public function __construct() {
        define( 'WP_ATTENDANCE_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
        define( 'WP_ATTENDANCE_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
        define( 'WP_ATTENDANCE_VERSION', '1.0.0' );

        // Include files
        include(WP_ATTENDANCE_DIR . '/wp-attendance-functions.php');
        include(WP_ATTENDANCE_DIR . '/admin/wp-attendance-settings.php');
        include(WP_ATTENDANCE_DIR . '/assets/wp-attendance-assets.php');

        // Activation hooks
        register_activation_hook(__FILE__, array($this, 'wp_attendance_activate'));
        register_deactivation_hook(__FILE__, array($this, 'wp_attendance_deactivate'));
    }

    /**
     * Create DB table for attendance
     *
     * @return void
     */
    public function wp_attendance_activate() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp_attendance';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            attendance_date DATE NOT NULL,
            status VARCHAR(10) NOT NULL
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql);
    }

    /**
     * Deactivation hook
     * No actions to perform during deactivation.
     *
     * @return void
     */
    public function wp_attendance_deactivate() {
        // No actions needed during deactivation.
    }
    
}

$wp_attendance = new WP_Attendance();