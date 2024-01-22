<?php
/**
 * Common functions
*/

if( !defined('ABSPATH') ) { 
    exit;
}

class WP_Attendance_Page {
    public function __construct() {
        add_action('admin_menu', array($this, 'wp_attendance_menu'));
    }

    public function wp_attendance_menu() {
        $menu_name = __('WP Attendance', 'wp-attendance');
        $menu_report = __('Attendance Report', 'wp-attendance');
        add_menu_page($menu_name, $menu_name, 'manage_options', 'wp-attendance', array($this, 'wp_attendance_page'), 'dashicons-list-view', 2);
        add_submenu_page('wp-attendance', $menu_report, $menu_report, 'manage_options', 'wp-attendance-report', array($this, 'wp_attendance_report'));
    }

    public function wp_attendance_page() {
        global $wpdb;

        // Handle form submission
        if (isset($_POST['submit_attendance'])) {
            $selected_date = isset($_POST['selected_date']) ? sanitize_text_field($_POST['selected_date']) : date('Y-m-d');
            $users = $_POST['user'];

            // Check if attendance already exists for the selected date and user ID
            foreach ($users as $user_id => $status) {
                $status = sanitize_text_field($status);
                
                // Check if the attendance entry already exists for the user and date
                $existing_attendance = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}wp_attendance WHERE user_id = %d AND attendance_date = %s",
                        $user_id,
                        $selected_date
                    )
                );

                if (!$existing_attendance) {
                    // Save attendance only if it doesn't exist
                    $wpdb->insert(
                        $wpdb->prefix . 'wp_attendance',
                        array(
                            'user_id' => $user_id,
                            'attendance_date' => $selected_date,
                            'status' => $status,
                        ),
                        array('%d', '%s', '%s')
                    );
                }
            }

            echo '<div class="updated"><p>' . __('Attendance marked for', 'wp-attendance') . ' ' . date('j F Y', strtotime($selected_date)) . '.</p></div>';
        }

        // Fetch attendance data for selected date
        $selected_date = isset($_POST['selected_date']) ? sanitize_text_field($_POST['selected_date']) : date('Y-m-d');
        $attendance_date = date('Y-m-d', strtotime($selected_date));
        $today_attendance = $wpdb->get_results("SELECT user_id, status FROM {$wpdb->prefix}wp_attendance WHERE attendance_date = '$attendance_date'", ARRAY_A);
        $attendance_map = array_column($today_attendance, 'status', 'user_id');

        ?>
        <!-- Date filter for attendance report -->
        <div class="wrap">
            <h1><?php _e('Take Attendance', 'wp-attendance') ?></h1>
            <form method="post">
                <label for="attendance_date"><?php _e('Select Date:', 'wp-attendance') ?></label>
                <input type="date" id="attendance_date" name="selected_date" value="<?php echo $selected_date ?>">
                <input type="submit" name="select_date" class="button button-primary" value="Select Date">
            </form>
        </div>

        <!-- Display attendance form -->
        <div class="wrap">
            <h2><?php _e('Take Attendance for', 'wp-attendance') . ' ' . date('j F Y', strtotime($selected_date)) ?> </h2>
            <form method="post">
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('User ID', 'wp-attendance') ?></th>
                            <th><?php _e('User Name', 'wp-attendance') ?></th>
                            <th><?php _e('Full Name', 'wp-attendance') ?></th>
                            <th><?php _e('Attendance', 'wp-attendance') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $users = get_users();
                            foreach ($users as $user) {
                                $user_id = $user->ID;
                                if('subscriber' !== $user->roles[0]){
                                    continue;
                                }
                                $user_name = $user->display_name;
                                $user_meta = get_userdata($user_id);
                                $full_name = $user_meta->first_name . ' ' . $user_meta->last_name;
                                $checked = isset($attendance_map[$user_id]) && $attendance_map[$user_id] === 'present' ? 'checked' : '';
                                ?>
                                <tr>
                                    <td><?php echo $user_id ?></td>
                                    <td><?php echo $user_name ?></td>
                                    <td><?php echo $full_name ?></td>
                                    <td><input type='checkbox' name='user[<?php echo $user_id ?>]' value='present' <?php echo $checked ?> /></td>
                                </tr>
                                <?php
                            }
                        ?>
                    </tbody>
                </table>
                <input type="hidden" id="selected_date" name="selected_date" value="<?php echo $selected_date ?>" />
                <br/>
                <input type="submit" name="submit_attendance" class="button button-primary" value="Submit Attendance" />
            </form>
        </div>
        <?php
    }

    // Attendance report page content
    public function wp_attendance_report() {
        global $wpdb;

        $selected_date = isset($_POST['selected_date']) ? sanitize_text_field($_POST['selected_date']) : date('Y-m-d');
        $export = get_option('wp_attendance_enable_export', 0);
        ?>

        <div class="wrap">
        <h1><?php _e('Attendance Report', 'wp-attendance') ?></h1>

        <!-- Date filter for attendance report -->
        <form method="post">
            <label for="report_date"><?php _e('Select Date', 'wp-attendance') ?>:</label>
            <input type="date" id="report_date" name="selected_date" value="<?php echo $selected_date ?>">
            <input type="submit" name="get_report" class="button button-primary" value="Get Report">
        </form>
        <br />
        <?php

        // Fetch attendance data for selected date
        $attendance_date = date('Y-m-d', strtotime($selected_date));
        $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wp_attendance WHERE attendance_date = '$attendance_date' ORDER BY attendance_date DESC");
        $show_users = get_option( 'wp_attendance_all_users_show', 0 );

        // Attendance table
        if(!$show_users){
            if ($results) {
                ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('User ID', 'wp-attendance') ?></th>
                            <th><?php _e('Username', 'wp-attendance') ?></th>
                            <th><?php _e('Full Name', 'wp-attendance') ?></th>
                            <th><?php _e('Status', 'wp-attendance') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($results as $row) {
                            $user_id = $row->user_id;
                            $user_info = get_userdata($user_id);
                            $user_name = $user_info->user_login;
                            $user_meta = get_userdata($user_id);
                            $full_name = $user_meta->first_name . ' ' . $user_meta->last_name;
                            ?>
                            <tr>
                                <td><?php echo $user_id ?></td>
                                <td><?php echo $user_name ?></td>
                                <td><?php echo $full_name ?></td>
                                <td><?php echo $row->status ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
                <?php
                if($export) {
                    include(WP_ATTENDANCE_DIR . '/templates/export-button.php');
                }
            } else {
                echo '<p>' . __('No attendance records found for selected date', 'wp-attendance') . '.</p>';
            }
        }
        else{
            if ($results) {
                // show list of users with attendance status
                $attendance_status = array();
                foreach ($results as $row) {
                    $attendance_status[$row->user_id] = $row->status;
                }
                ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('User ID', 'wp-attendance') ?></th>
                            <th><?php _e('Username', 'wp-attendance') ?></th>
                            <th><?php _e('Full Name', 'wp-attendance') ?></th>
                            <th><?php _e('Status', 'wp-attendance') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $users = get_users();
                        foreach ($users as $user) {
                            $user_id = $user->ID;
                            if('subscriber' !== $user->roles[0]){
                                continue;
                            }
                            $user_info = get_userdata($user_id);
                            $user_name = $user_info->user_login;
                            $full_name = $user_info->first_name . ' ' . $user_info->last_name;

                            // Check if user has attendance record for the selected date
                            $status = isset($attendance_status[$user_id]) ? $attendance_status[$user_id] : 'absent';
                            ?>
                            <tr>
                                <td><?php echo $user_id ?></td>
                                <td><?php echo $user_name ?></td>
                                <td><?php echo $full_name ?></td>
                                <td><?php echo $status ?></td>
                            </tr>
                        <?php }
                        ?>
                    </tbody>
                </table>
                <?php
                if($export) {
                    include(WP_ATTENDANCE_DIR . '/templates/export-button.php');
                }
            } else {
                echo '<p>' . __('No attendance records found for selected date', 'wp-attendance') . '.</p>';
            }
        }
    }
}

$wp_attendance_page = new WP_Attendance_Page();