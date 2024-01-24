<?php

class MA_Attendance_Assets {
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    public function admin_enqueue_scripts() {
        // Enqueue stylesheet only on MA Attendance pages
        $screen = get_current_screen();
        $prefix = 'ma-attendance_page_ma-attendance-';
        $MA_ATTENDANCE_pages = array(
            'toplevel_page_wp-attendance',
            $prefix . 'report',
            $prefix . 'settings'
        );
        if ($screen && in_array($screen->id, $MA_ATTENDANCE_pages)) {
            wp_enqueue_style( 'ma-attendance-admin-css', MA_ATTENDANCE_URL . '/assets/admin.css', array(), MA_ATTENDANCE_VERSION );
        }
    }
}

$ma_attendance_assets = new MA_Attendance_Assets();