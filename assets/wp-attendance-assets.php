<?php

class WP_Attendance_Assets {
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    public function admin_enqueue_scripts() {
        // Enqueue stylesheet only on WP Attendance pages
        $screen = get_current_screen();
        if ($screen && $screen->id === 'toplevel_page_wp-attendance' || $screen->id === 'wp-attendance_page_wp-attendance-report') {
            wp_enqueue_style( 'wp-attendance-admin-css', WP_ATTENDANCE_URL . '/assets/admin.css' );
        }
    }
}

$wp_attendance_assets = new WP_Attendance_Assets();