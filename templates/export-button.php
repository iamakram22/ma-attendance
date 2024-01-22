<!-- Export button -->
<br />
<form method="post">
    <input type="hidden" name="export_attendance" value="1">
    <input type="hidden" name="selected_date" value="<?php echo $selected_date ?>">
    <input type="submit" name="export_button" class="button button-primary" value="<?php _e('Export Attendance', 'wp-attendance') ?>">
</form>