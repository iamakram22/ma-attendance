<?php
if( !defined('ABSPATH') ) { 
    exit;
}

class WP_Attendance_Page {
    
    private $users, $date, $export, $show_users;

    public function __construct() {
        add_action('admin_menu', array($this, 'wp_attendance_menu'));
        $this->users = get_users();
        $this->date = date('Y-m-d');
        $this->export = get_option('wp_attendance_enable_export');
        $this->show_users = get_option('wp_attendance_enable_export', 0);
    }

    /**
     * Add WP Attendance menu in dashboard
     *
     * @since 1.0.0
     * @return void
     */
    public function wp_attendance_menu() {
        $menu_name = __('WP Attendance', WP_ATTENDANCE_DOMAIN);
        $menu_report = __('Attendance Report', WP_ATTENDANCE_DOMAIN);
        add_menu_page($menu_name, $menu_name, 'manage_options', WP_ATTENDANCE_DOMAIN, array($this, 'wp_attendance_page'), 'dashicons-list-view', 2);
        add_submenu_page(WP_ATTENDANCE_DOMAIN, $menu_report, $menu_report, 'manage_options', 'wp-attendance-report', array($this, 'wp_attendance_report'));
    }

    /**
     * Render Attendance page
     *
     * @since 1.0.0
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

            echo '<div class="updated"><p>' . __('Attendance marked for', WP_ATTENDANCE_DOMAIN) . ' ' . date('j F Y', strtotime($selected_date)) . '.</p></div>';
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

        <div class="wrap wp-attendance-container">
            <h1><?php _e('Take Attendance', WP_ATTENDANCE_DOMAIN) ?></h1>
            <!-- Date selector for attendance -->
            <form method="post">
                <label for="attendance_date"><?php _e('Select Date:', WP_ATTENDANCE_DOMAIN) ?></label>
                <input type="date" id="attendance_date" name="selected_date" value="<?php echo $selected_date ?>" max="<?php echo $this->date ?>">
                <input type="submit" name="select_date" class="button button-primary" value="<?php _e('Select Date', WP_ATTENDANCE_DOMAIN) ?>">
            </form>

            <!-- Display attendance form -->
            <div class="wrap wp-attendance-table-container">
                <h2><?php echo __('Take Attendance for', WP_ATTENDANCE_DOMAIN) . ' ' . date('j F Y', strtotime($selected_date)) ?></h2>
                <form method="post">
                    <table class="widefat wp-attendance-table">
                        <thead>
                            <tr>
                                <th><?php _e('User ID', WP_ATTENDANCE_DOMAIN) ?></th>
                                <th><?php _e('User Name', WP_ATTENDANCE_DOMAIN) ?></th>
                                <th><?php _e('Full Name', WP_ATTENDANCE_DOMAIN) ?></th>
                                <th><?php _e('Attendance', WP_ATTENDANCE_DOMAIN) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                foreach ($this->users as $user) {
                                    $user_id = $user->ID;
                                    $attendance_roles = array('subscriber');
                                    apply_filters( 'wp_attendance_attendance_roles', $attendance_roles );

                                    if(!in_array($user->roles[0], $attendance_roles)) {
                                        continue;
                                    }

                                    $username = $user->user_login;
                                    $user_meta = get_userdata($user_id);
                                    $full_name = $user_meta->first_name . ' ' . $user_meta->last_name;
                                    $checked = isset($attendance_map[$user_id]) && $attendance_map[$user_id] === 'present' ? 'checked' : '';
                                    ?>
                                    <tr>
                                        <td><?php echo $user_id ?></td>
                                        <td><?php echo $username ?></td>
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
                    <input type="submit" name="submit_attendance" class="button button-primary" value="<?php _e('Submit Attendance', WP_ATTENDANCE_DOMAIN) ?>" />
                </form>
            </div>
        </div> <!-- .wp-attendance-container -->
        <?php
    }

    /**
     * Render Attendance report page
     *
     * @since 1.0.0
     * @return void
     */
    public function wp_attendance_report() {
        global $wpdb;

        $selected_date = isset($_POST['selected_date']) ? sanitize_text_field($_POST['selected_date']) : $this->date;
        ?>

        <div class="wrap wp-attendance-container">
            <h1><?php _e('Attendance Report', WP_ATTENDANCE_DOMAIN) ?></h1>

            <!-- Date filter for attendance report -->
            <form method="post">
                <label for="report_date"><?php _e('Select Date', WP_ATTENDANCE_DOMAIN) ?>:</label>
                <input type="date" id="report_date" name="selected_date" value="<?php echo $selected_date ?>" max="<?php echo $this->date ?>">
                <input type="submit" name="get_report" class="button button-primary" value="<?php _e('Get Report', WP_ATTENDANCE_DOMAIN) ?>">
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

            // Attendance table
            if(!$this->show_users){
                if ($results) {
                    ?>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php _e('User ID', WP_ATTENDANCE_DOMAIN) ?></th>
                                <th><?php _e('Username', WP_ATTENDANCE_DOMAIN) ?></th>
                                <th><?php _e('Full Name', WP_ATTENDANCE_DOMAIN) ?></th>
                                <th><?php _e('Status', WP_ATTENDANCE_DOMAIN) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($results as $row) {
                                $user_id    = $row->user_id;
                                $user_meta  = get_userdata($user_id);
                                $username   = $user_meta->user_login;
                                $full_name  = $user_meta->first_name . ' ' . $user_meta->last_name;
                                ?>
                                <tr>
                                    <td><?php echo $user_id ?></td>
                                    <td><?php echo $username ?></td>
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
                    if($this->export) {
                        $this->render_export_button($selected_date);
                    }
                } else {
                    echo '<p>' . __('No attendance records found for selected date', WP_ATTENDANCE_DOMAIN) . '.</p>';
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
                                <th><?php _e('User ID', WP_ATTENDANCE_DOMAIN) ?></th>
                                <th><?php _e('Username', WP_ATTENDANCE_DOMAIN) ?></th>
                                <th><?php _e('Full Name', WP_ATTENDANCE_DOMAIN) ?></th>
                                <th><?php _e('Status', WP_ATTENDANCE_DOMAIN) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($this->users as $user) {
                                $user_id = $user->ID;
                                $attendance_roles = array('subscriber');
                                apply_filters( 'wp_attendance_attendance_roles', $attendance_roles );

                                if(!in_array($user->roles[0], $attendance_roles)) {
                                    continue;
                                }

                                $user_meta = get_userdata($user_id);
                                $user_name = $user_meta->user_login;
                                $full_name = $user_meta->first_name . ' ' . $user_meta->last_name;

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
                    if($this->export) {
                        $this->render_export_button($selected_date);
                    }
                } else {
                    echo '<p>' . __('No attendance records found for selected date', WP_ATTENDANCE_DOMAIN) . '.</p>';
                }
            }

            /**
             * Export attendance to csv file
             */
            if ($this->export) {
                include(WP_ATTENDANCE_DIR . '/templates/export-report.php');
            }
            ?>
        </div> <!-- .wp-attendance-wrap -->
        <?php
    }

    /**
     * Render Export button HTML
     *
     * @since 1.0.0
     * @param string $selected_date The selected date for export
     */
    public function render_export_button($selected_date) {
        ?>
        <div class="wp-attendance-export-button-container">
            <form method="post">
                <input type="hidden" name="export_attendance" value="1">
                <input type="hidden" name="selected_date" value="<?php echo $selected_date ?>">
                <input type="submit" name="export_button" class="button button-primary" value="<?php _e('Export Attendance', WP_ATTENDANCE_DOMAIN) ?>">
            </form>
        </div>
        <?php
    }
}

$wp_attendance_page = new WP_Attendance_Page();