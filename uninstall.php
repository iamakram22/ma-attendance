<?php

/**
 * Call when the plugin is uninstalled.
*/

// If WP_UNINSTALL_PLUGIN not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit();
}

// In the entire plugin's option name's array which is used in plugin for the deletion.
$options = array(
	'wp_attendance_all_users_show',
	'wp_attendance_enable_export',
);

// Delete the options
foreach ($options as $option) {
	delete_option(esc_attr($option));
}
