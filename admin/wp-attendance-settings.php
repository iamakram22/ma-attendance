<?php

if( !defined('ABSPATH') ) { 
    exit;
}

class WP_Attendance_Settings {
    public function __construct() {
        add_action('admin_init', array($this, 'wp_attendance_register_settings'));
        add_action('admin_menu', array($this, 'wp_attendance_settings_menu'));
    }

    public function wp_attendance_settings_menu() {
        $menu_setting = __('Settings', 'wp-attendance');
        add_submenu_page('wp-attendance', $menu_setting, $menu_setting, 'manage_options', 'wp-attendance-settings', array($this, 'wp_attendance_settings'));
    }

    public function wp_attendance_settings() {
        ?>
        <div class="wrap">
            <h1><?php _e('WP Attendance Settings', 'wp-attendane') ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_attendance_settings_group');
                do_settings_sections('wp_attendance_settings_page');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function wp_attendance_register_settings() {
        register_setting('wp_attendance_settings_group', 'wp_attendance_all_users_show');
        register_setting('wp_attendance_settings_group', 'wp_attendance_enable_export');
    
        // Add settings section and field
        add_settings_section('wp_attendance_main_section', '', '', 'wp_attendance_settings_page');

        add_settings_field('wp_attendance_all_users_show', __('Show absent students in report', 'wp-attendance'), array($this, 'wp_attendance_show_users'), 'wp_attendance_settings_page', 'wp_attendance_main_section');
        add_settings_field('wp_attendance_enable_export', __('Export attendance report', 'wp-attendance'), array($this, 'wp_attendance_export_option'), 'wp_attendance_settings_page', 'wp_attendance_main_section');
    }
    
    // Field Show users
    public function wp_attendance_show_users() {
        $setting_value = get_option('wp_attendance_all_users_show');
        echo '<input type="checkbox" name="wp_attendance_all_users_show" value="1" ' . checked(1, $setting_value, false) . ' />';
    }

    // Field Export attendance
    public function wp_attendance_export_option() {
        $setting_value = get_option('wp_attendance_enable_export');
        echo '<input type="checkbox" name="wp_attendance_enable_export" value="1" ' . checked(1, $setting_value, false) . ' />';
    }
}

$wp_attendance_settings = new WP_Attendance_Settings();