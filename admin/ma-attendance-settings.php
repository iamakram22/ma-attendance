<?php

if( !defined('ABSPATH') ) { 
    exit;
}

class MA_Attendance_Settings {
    public function __construct() {
        add_action('admin_init', array($this, 'ma_attendance_register_settings'));
        add_action('admin_menu', array($this, 'ma_attendance_settings_menu'));
    }

    /**
     * Create settings page menu
     *
     * @since 1.0.0
     * @return void
     */
    public function ma_attendance_settings_menu() {
        $menu_setting = __('Settings', MA_ATTENDANCE_DOMAIN);
        add_submenu_page(MA_ATTENDANCE_DOMAIN, $menu_setting, $menu_setting, 'manage_options', 'ma-attendance-settings', array($this, 'ma_attendance_settings'));
    }

    /**
     * Render settings page
     *
     * @since 1.0.0
     * @return void
     */
    public function ma_attendance_settings() {
        ?>
        <div class="wrap ma-attendance-container">
            <h1><?php _e('MA Attendance Settings', MA_ATTENDANCE_DOMAIN) ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ma_attendance_settings_group');
                do_settings_sections('ma_attendance_settings_page');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register plugin settings
     *
     * @since 1.0.0
     * @return void
     */
    public function ma_attendance_register_settings() {
        // Set default values if options don't exist
        if (false === get_option('ma_attendance_enable_export')) {
            add_option('ma_attendance_enable_export', 1);
        }

        // Register settings
        register_setting('ma_attendance_settings_group', 'ma_attendance_all_users_show');
        register_setting('ma_attendance_settings_group', 'ma_attendance_enable_export');
        register_setting('ma_attendance_settings_group', 'ma_attendance_delete_data');
    
        // Add settings section and field
        add_settings_section('ma_attendance_main_section', '', '', 'ma_attendance_settings_page');

        // Create setting fields
        add_settings_field('ma_attendance_all_users_show', __('Show absent students in report', MA_ATTENDANCE_DOMAIN), array($this, 'render_ma_attendance_all_users_show'), 'ma_attendance_settings_page', 'ma_attendance_main_section');
        add_settings_field('ma_attendance_enable_export', __('Export attendance report', MA_ATTENDANCE_DOMAIN), array($this, 'render_ma_attendance_enable_export'), 'ma_attendance_settings_page', 'ma_attendance_main_section');
        add_settings_field('ma_attendance_delete_data', __('Delete data on uninstall', MA_ATTENDANCE_DOMAIN), array($this, 'render_ma_attendance_delete_data'), 'ma_attendance_settings_page', 'ma_attendance_main_section');
    }
    
    // Field Show users
    public function render_ma_attendance_all_users_show() {
        $setting_value = get_option('ma_attendance_all_users_show');
        echo '<input type="checkbox" name="ma_attendance_all_users_show" value="1" ' . checked(1, $setting_value, false) . ' />';
    }

    // Field Export attendance
    public function render_ma_attendance_enable_export() {
        $setting_value = get_option('ma_attendance_enable_export');
        echo '<input type="checkbox" name="ma_attendance_enable_export" value="1" ' . checked(1, $setting_value, false) . ' />';
    }

    // Field Delete data
    public function render_ma_attendance_delete_data() {
        $setting_value = get_option('ma_attendance_delete_data');
        echo '<input type="checkbox" name="ma_attendance_delete_data" value="1" ' . checked(1, $setting_value, false) . ' />';
    }
}

$ma_attendance_settings = new MA_Attendance_Settings();