<?php

class WP_Attendance_Assets {
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    public function admin_enqueue_scripts() {
        // Enqueue stylesheet only on WP Attendance pages
        $screen = get_current_screen();
        $prefix = 'wp-attendance_page_wp-attendance-';
        $wp_attendance_pages = array(
            'toplevel_page_wp-attendance',
            $prefix . 'report',
            $prefix . 'settings'
        );
        if ($screen && in_array($screen->id, $wp_attendance_pages)) {
            wp_enqueue_style( 'wp-attendance-admin-css', WP_ATTENDANCE_URL . '/assets/admin.css', array(), WP_ATTENDANCE_VERSION );
        }
    }
}

$wp_attendance_assets = new WP_Attendance_Assets();