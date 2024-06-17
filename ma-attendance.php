<?php
/*
 * Plugin Name:       MA Attendance
 * Plugin URI:        https://github.com/iamakram22/wp-attendance.git
 * Description:       MA Attendance is a simple and effective plugin for taking daily attendance of users on your WordPress site.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Mohd Akram
 * Author URI:        https://github.com/iamakram22
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.en.html
 * Text Domain:       ma-attendance
 * Domain Path:       /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MA_Attendance {

    public function __construct() {
        define( 'MA_ATTENDANCE_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
        define( 'MA_ATTENDANCE_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
        define( 'MA_ATTENDANCE_VERSION', '1.0.0' );

        // Include files
        include(MA_ATTENDANCE_DIR . '/ma-attendance-functions.php');
        include(MA_ATTENDANCE_DIR . '/admin/ma-attendance-settings.php');
        include(MA_ATTENDANCE_DIR . '/assets/ma-attendance-assets.php');

        // Activation hooks
        register_activation_hook(__FILE__, array($this, 'ma_attendance_activate'));
        register_deactivation_hook(__FILE__, array($this, 'ma_attendance_deactivate'));
    }

    /**
     * Create DB table for attendance
     *
     * @since 1.0.0
     * @return void
     */
    public function ma_attendance_activate() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'ma_attendance';
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
     * @since 1.0.0
     * @return void
     */
    public function ma_attendance_deactivate() {
        // No actions needed during deactivation.
    }
    
}

$ma_attendance = new MA_Attendance();
