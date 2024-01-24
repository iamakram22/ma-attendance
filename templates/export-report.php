<?php

// Check if data is set
if(!$this->users) $this->users = get_users();
if(!$this->date) $this->date = date('Y-m-d');
if(!$this->export) $this->export = get_option('wp_attendance_enable_export');
if(!$this->show_users) $this->show_users = get_option('wp_attendance_all_users_show', 0);

$output = '';
// Export attendance data if Export button is clicked
if (isset($_POST['export_attendance']) && $_POST['export_attendance'] == 1) {
    $selected_date = isset($_POST['selected_date']) ? sanitize_text_field($_POST['selected_date']) : $this->date;
    $attendance_date = date('Y-m-d', strtotime($selected_date));

    // Fetch attendance data
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wp_attendance WHERE attendance_date = %s ORDER BY attendance_date DESC",
            $attendance_date
        )
    );

    if ($results) {
        $filename = 'attendance_report_' . $attendance_date . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=' . $filename);

        // clean out other output buffers
        ob_end_clean();

        $output = fopen('php://output', 'w');
        // File header row
        $header_row = array(
            0 => 'user_id', 
            1 => 'username',
            2 => 'full_name',
            3 => 'status'
        );
        // Write the header
        fputcsv($output, $header_row);

        /**
         * Render attendance data for file export
         */
        if (!$this->show_users) {

            foreach ($results as $row) {
                $user_info = get_userdata($row->user_id);
                $username = $user_info->user_login;
                $full_name = $user_info->first_name . ' ' . $user_info->last_name;
                $output_record = array(
                    $row->user_id,
                    $username,
                    $full_name,
                    $row->status
                );

                // Write data to the file
                fputcsv($output, $output_record);
            }

        } else {
            // store list of users with attendance status
            $attendance_status = array();
            foreach ($results as $row) {
                $attendance_status[$row->user_id] = $row->status;
            }

            foreach ($this->users as $user) {
                $user_id = $user->ID;
                if('subscriber' !== $user->roles[0]){
                    continue;
                }

                $user_info = get_userdata($user_id);
                $username = $user_info->user_login;
                $full_name = $user_info->first_name . ' ' . $user_info->last_name;

                // Check if user has attendance record for the selected date
                $status = isset($attendance_status[$user_id]) ? $attendance_status[$user_id] : 'absent';

                $output_record = array(
                    $user_id,
                    $username,
                    $full_name,
                    $status
                );

                // Write data to the file
                fputcsv($output, $output_record);
            }
        }

        fclose($output);

        exit(); // Stop any more exporting to the file
    }
}