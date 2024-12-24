<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="attendance-form">
    <div class="modal-content">
        <h3><?php printf(__('Attendance for %s', 'amelia-class-extension'), 
            date_i18n('F j, Y', strtotime($appointment->bookingStart))); ?></h3>
        
        <form id="attendance_form" data-session-id="<?php echo esc_attr($session_id); ?>">
            <?php wp_nonce_field('ace_save_attendance', 'attendance_nonce'); ?>
            
            <table class="wp-list-table widefat">
                <thead>
                    <tr>
                        <th><?php _e('Student', 'amelia-class-extension'); ?></th>
                        <th><?php _e('Status', 'amelia-class-extension'); ?></th>
                        <th><?php _e('Notes', 'amelia-class-extension'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo esc_html($student->firstName . ' ' . $student->lastName); ?></td>
                            <td>
                                <select name="attendance[<?php echo $student->id; ?>][status]">
                                    <option value="present" <?php selected($student->attendance_status, 'present'); ?>>
                                        <?php _e('Present', 'amelia-class-extension'); ?>
                                    </option>
                                    <option value="absent" <?php selected($student->attendance_status, 'absent'); ?>>
                                        <?php _e('Absent', 'amelia-class-extension'); ?>
                                    </option>
                                    <option value="late" <?php selected($student->attendance_status, 'late'); ?>>
                                        <?php _e('Late', 'amelia-class-extension'); ?>
                                    </option>
                                </select>
                            </td>
                            <td>
                                <input type="text" 
                                       name="attendance[<?php echo $student->id; ?>][notes]" 
                                       value="<?php echo esc_attr($student->attendance_notes); ?>"
                                       class="widefat">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="attendance-actions">
                <button type="submit" class="button button-primary">
                    <?php _e('Save Attendance', 'amelia-class-extension'); ?>
                </button>
                <button type="button" class="button close-modal">
                    <?php _e('Cancel', 'amelia-class-extension'); ?>
                </button>
            </div>
        </form>
    </div>
</div>
