<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get existing values
$teacher_id = get_post_meta($post->ID, '_teacher_id', true);
$students = get_post_meta($post->ID, '_students', true) ?: array();
$start_date = get_post_meta($post->ID, '_start_date', true);
$end_date = get_post_meta($post->ID, '_end_date', true);
$class_time = get_post_meta($post->ID, '_class_time', true);
$class_days = get_post_meta($post->ID, '_class_days', true) ?: array();

// Get providers (teachers) from Amelia users table
$providers = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}amelia_users WHERE type = 'provider' ORDER BY firstName, lastName");

// Get customers (students) from Amelia users table
$students_list = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}amelia_users WHERE type = 'customer' ORDER BY firstName, lastName");
?>

<div class="ace-meta-box">
    <?php wp_nonce_field('ace_save_class_meta', 'ace_class_meta_nonce'); ?>
    
    <div class="ace-form-row">
        <label for="teacher_id"><?php _e('Teacher', 'amelia-class-extension'); ?></label>
        <select name="teacher_id" id="teacher_id" required>
            <option value=""><?php _e('Select Teacher', 'amelia-class-extension'); ?></option>
            <?php foreach ($providers as $provider): ?>
                <option value="<?php echo esc_attr($provider->id); ?>" 
                    <?php selected($teacher_id, $provider->id); ?>>
                    <?php echo esc_html($provider->firstName . ' ' . $provider->lastName); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="ace-form-row">
        <h3><?php _e('Class Schedule', 'amelia-class-extension'); ?></h3>
        <div class="schedule-inputs">
            <div>
                <label for="start_date"><?php _e('Start Date', 'amelia-class-extension'); ?></label>
                <input type="date" name="start_date" id="start_date" value="<?php echo esc_attr($start_date); ?>" required>
            </div>
            <div>
                <label for="end_date"><?php _e('End Date', 'amelia-class-extension'); ?></label>
                <input type="date" name="end_date" id="end_date" value="<?php echo esc_attr($end_date); ?>" required>
            </div>
            <div>
                <label for="class_time"><?php _e('Time', 'amelia-class-extension'); ?></label>
                <input type="time" name="class_time" id="class_time" value="<?php echo esc_attr($class_time); ?>" required>
            </div>
        </div>
        
        <div class="class-days">
            <label><?php _e('Class Days', 'amelia-class-extension'); ?></label>
            <?php
            $days = array(
                'monday' => __('Monday', 'amelia-class-extension'),
                'tuesday' => __('Tuesday', 'amelia-class-extension'),
                'wednesday' => __('Wednesday', 'amelia-class-extension'),
                'thursday' => __('Thursday', 'amelia-class-extension'),
                'friday' => __('Friday', 'amelia-class-extension'),
                'saturday' => __('Saturday', 'amelia-class-extension'),
                'sunday' => __('Sunday', 'amelia-class-extension')
            );
            foreach ($days as $value => $label): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="class_days[]" value="<?php echo esc_attr($value); ?>"
                           <?php checked(in_array($value, $class_days)); ?>>
                    <?php echo esc_html($label); ?>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="ace-form-row">
        <h3><?php _e('Students', 'amelia-class-extension'); ?></h3>
        <div class="student-management">
            <select name="student_id" id="student_id" style="width: 100%;">
                <option value=""><?php _e('Select Student', 'amelia-class-extension'); ?></option>
                <?php foreach ($students_list as $student): ?>
                    <option value="<?php echo esc_attr($student->id); ?>">
                        <?php echo esc_html($student->firstName . ' ' . $student->lastName . ' (' . $student->email . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" id="add_student" class="button button-secondary">
                <?php _e('Add Student', 'amelia-class-extension'); ?>
            </button>
        </div>
        
        <div id="student_list" class="ace-student-list">
            <?php if (!empty($students)): ?>
                <table>
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'amelia-class-extension'); ?></th>
                            <th><?php _e('Email', 'amelia-class-extension'); ?></th>
                            <th><?php _e('Phone', 'amelia-class-extension'); ?></th>
                            <th><?php _e('Actions', 'amelia-class-extension'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student_id): 
                            $student = $wpdb->get_row($wpdb->prepare(
                                "SELECT * FROM {$wpdb->prefix}amelia_users WHERE id = %d AND type = 'customer'",
                                $student_id
                            ));
                            if ($student): ?>
                                <tr data-student-id="<?php echo esc_attr($student_id); ?>">
                                    <td><?php echo esc_html($student->firstName . ' ' . $student->lastName); ?></td>
                                    <td><?php echo esc_html($student->email); ?></td>
                                    <td><?php echo esc_html($student->phone); ?></td>
                                    <td>
                                        <button type="button" class="button button-small remove-student">
                                            <?php _e('Remove', 'amelia-class-extension'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-students"><?php _e('No students added to this class yet.', 'amelia-class-extension'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($post->post_status !== 'auto-draft'): ?>
    <div class="ace-form-row">
        <h3><?php _e('Class Sessions', 'amelia-class-extension'); ?></h3>
        <div id="class_sessions">
            <?php
            $sessions = ace_get_class_sessions($post->ID);
            if (!empty($sessions)): ?>
                <table>
                    <thead>
                        <tr>
                            <th><?php _e('Date', 'amelia-class-extension'); ?></th>
                            <th><?php _e('Time', 'amelia-class-extension'); ?></th>
                            <th><?php _e('Status', 'amelia-class-extension'); ?></th>
                            <th><?php _e('Attendance', 'amelia-class-extension'); ?></th>
                            <th><?php _e('Actions', 'amelia-class-extension'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $session): ?>
                            <tr data-session-id="<?php echo esc_attr($session->id); ?>">
                                <td><?php echo esc_html(date_i18n('Y-m-d', strtotime($session->bookingStart))); ?></td>
                                <td><?php echo esc_html(date_i18n('H:i', strtotime($session->bookingStart))); ?></td>
                                <td><?php echo esc_html(ucfirst($session->appointment_status)); ?></td>
                                <td>
                                    <button type="button" class="button button-small take-attendance">
                                        <?php _e('Take Attendance', 'amelia-class-extension'); ?>
                                    </button>
                                </td>
                                <td>
                                    <button type="button" class="button button-small edit-session">
                                        <?php _e('Edit', 'amelia-class-extension'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-sessions"><?php _e('No sessions scheduled yet.', 'amelia-class-extension'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Attendance Modal -->
<div id="attendance_modal" class="ace-modal" style="display: none;">
    <!-- Content will be loaded here -->
</div>
