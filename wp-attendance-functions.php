<?php
if( !defined('ABSPATH') ) { 
    exit;
}

class WP_Attendance_Page {
    
    private $users, $date;

    public function __construct() {
        add_action('admin_menu', array($this, 'wp_attendance_menu'));
        $this->users = get_users();
        $this->date = date('Y-m-d');
    }

    /**
     * Add WP Attendance menu in dashboard
     *
     * @return void
     */
    public function wp_attendance_menu() {
        $menu_name = __('WP Attendance', 'wp-attendance');
        $menu_report = __('Attendance Report', 'wp-attendance');
        add_menu_page($menu_name, $menu_name, 'manage_options', 'wp-attendance', array($this, 'wp_attendance_page'), 'dashicons-list-view', 2);
        add_submenu_page('wp-attendance', $menu_report, $menu_report, 'manage_options', 'wp-attendance-report', array($this, 'wp_attendance_report'));
    }

    /**
     * Render Attendance page
     *
     * @return void
     */
    public function wp_attendance_page() {
        global $wpdb;

        // Handle attendance submission
        if (isset($_POST['submit_attendance'])) {
            $selected_date = isset($_POST['selected_date']) ? sanitize_text_field($_POST['selected_date']) : $this->date;
            $users = $_POST['user'];

            $user_ids = array_keys($users);

            // Check if attendance already exists for the selected date and user IDs
            $existing_attendance = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT user_id FROM {$wpdb->prefix}wp_attendance WHERE user_id IN (%s) AND attendance_date = %s",
                    implode(',', $user_ids),
                    $selected_date
                ), ARRAY_A
            );

            $existing_user_ids = array_column($existing_attendance, 'user_id');

            foreach ($user_ids as $user_id) {
                if (!in_array($user_id, $existing_user_ids)) {
                    // Save attendance only if it doesn't exist
                    $status = sanitize_text_field($users[$user_id]);
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
        $selected_date = isset($_POST['selected_date']) ? sanitize_text_field($_POST['selected_date']) : $this->date;
        $attendance_date = date('Y-m-d', strtotime($selected_date));
        $today_attendance = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wp_attendance WHERE attendance_date = %s ORDER BY attendance_date DESC",
                $attendance_date
            )
        );
        $attendance_map = array_column($today_attendance, 'status', 'user_id');

        ?>

        <!-- Date selector for attendance -->
        <div class="wrap">
            <h1><?php _e('Take Attendance', 'wp-attendance') ?></h1>
            <form method="post">
                <label for="attendance_date"><?php _e('Select Date:', 'wp-attendance') ?></label>
                <input type="date" id="attendance_date" name="selected_date" value="<?php echo $selected_date ?>" max="<?php echo $this->date ?>">
                <input type="submit" name="select_date" class="button button-primary" value="<?php _e('Select Date', 'wp-attendance') ?>">
            </form>
        </div>

        <!-- Display attendance form -->
        <div class="wrap wp-attendance-table-container">
            <h2><?php _e('Take Attendance for', 'wp-attendance') . ' ' . date('j F Y', strtotime($selected_date)) ?> </h2>
            <form method="post">
                <table class="widefat wp-attendance-table">
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
                            foreach ($this->users as $user) {
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
                <input type="submit" name="submit_attendance" class="button button-primary" value="<?php _e('Submit Attendance', 'wp-attendance') ?>" />
            </form>
        </div>
        <?php
    }

    /**
     * Render Attendance report page
     *
     * @return void
     */
    public function wp_attendance_report() {
        global $wpdb;

        $selected_date = isset($_POST['selected_date']) ? sanitize_text_field($_POST['selected_date']) : $this->date;
        $export = get_option('wp_attendance_enable_export');
        ?>

        <div class="wrap">
        <h1><?php _e('Attendance Report', 'wp-attendance') ?></h1>

        <!-- Date filter for attendance report -->
        <form method="post">
            <label for="report_date"><?php _e('Select Date', 'wp-attendance') ?>:</label>
            <input type="date" id="report_date" name="selected_date" value="<?php echo $selected_date ?>" max="<?php echo $this->date ?>">
            <input type="submit" name="get_report" class="button button-primary" value="<?php _e('Get Report', 'wp-attendance') ?>">
        </form>
        <br />
        <?php

        // Fetch attendance data for selected date
        $attendance_date = date('Y-m-d', strtotime($selected_date));
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wp_attendance WHERE attendance_date = %s ORDER BY attendance_date DESC",
                $attendance_date
            )
        );
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
                /**
                 * Add export button if enabled in setting
                 */
                if($export) {
                    include(WP_ATTENDANCE_DIR . '/templates/export-button.php');
                }
            } else {
                echo '<p>' . __('No attendance records found for selected date', 'wp-attendance') . '.</p>';
            }
        }
        else {
            if ($results) {
                // store list of users with attendance status
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
                        foreach ($this->users as $user) {
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
                /**
                 * Add export button if enabled in setting
                 */
                if($export) {
                    include(WP_ATTENDANCE_DIR . '/templates/export-button.php');
                }
            } else {
                echo '<p>' . __('No attendance records found for selected date', 'wp-attendance') . '.</p>';
            }
        }

        /**
         * Export attendance to csv file
         */
        if ($export) {
            include(WP_ATTENDANCE_DIR . '/templates/export-report.php');
        }
    }
}

$wp_attendance_page = new WP_Attendance_Page();