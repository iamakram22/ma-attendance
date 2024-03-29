<?php

/**
 * Call when the plugin is uninstalled.
*/

// If WP_UNINSTALL_PLUGIN not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

$delete_data = get_option( 'ma_attendance_delete_data' );

if ($delete_data) {
	global $wpdb;
    $table_name = $wpdb->prefix . 'ma_attendance';

    // Drop the table (change this based on your data structure)
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

// In the entire plugin's option name's array which is used in plugin for the deletion.
$options = array(
	'ma_attendance_all_users_show',
	'ma_attendance_enable_export',
	'ma_attendance_delete_data'
);

// Delete the options
foreach ($options as $option) {
	delete_option(esc_attr($option));
}
