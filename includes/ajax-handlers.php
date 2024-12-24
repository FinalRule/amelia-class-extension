<?php
// includes/ajax-handlers.php

if (!defined('ABSPATH')) {
    exit;
}

// Add student to class
function ace_ajax_add_student() {
    check_ajax_referer('ace_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $class_id = intval($_POST['class_id']);
    $student_id = intval($_POST['student_id']);
    
    global $wpdb;
    
    // Verify student exists and is actually a customer
    $student = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}amelia_users WHERE id = %d AND type = 'customer'",
        $student_id
    ));
    
    if (!$student) {
        wp_send_json_error('Invalid student');
        return;
    }
    
    // Get all appointments for this class
    $appointments = $wpdb->get_col($wpdb->prepare(
        "SELECT appointment_id FROM {$wpdb->prefix}amelia_class_sessions WHERE class_id = %d",
        $class_id
    ));
    
    foreach ($appointments as $appointment_id) {
        $wpdb->insert($wpdb->prefix . 'amelia_customer_bookings', array(
            'appointmentId' => $appointment_id,
            'customerId' => $student_id,
            'status' => 'approved'
        ));
    }
    
    $current_students = get_post_meta($class_id, '_students', true);
    if (!is_array($current_students)) {
        $current_students = array();
    }
    $current_students[] = $student_id;
    update_post_meta($class_id, '_students', array_unique($current_students));
    
    wp_send_json_success();
}
add_action('wp_ajax_ace_add_student', 'ace_ajax_add_student');

// Remove student
function ace_ajax_remove_student() {
    check_ajax_referer('ace_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $class_id = intval($_POST['class_id']);
    $student_id = intval($_POST['student_id']);
    
    global $wpdb;
    
    // Remove from all appointments
    $appointments = $wpdb->get_col($wpdb->prepare(
        "SELECT appointment_id FROM {$wpdb->prefix}amelia_class_sessions WHERE class_id = %d",
        $class_id
    ));
    
    foreach ($appointments as $appointment_id) {
        $wpdb->delete(
            $wpdb->prefix . 'amelia_customer_bookings',
            array(
                'appointmentId' => $appointment_id,
                'customerId' => $student_id
            )
        );
    }
    
    // Remove from class meta
    $students = get_post_meta($class_id, '_students', true);
    if (is_array($students)) {
        $students = array_diff($students, array($student_id));
        update_post_meta($class_id, '_students', $students);
    }
    
    wp_send_json_success();
}
add_action('wp_ajax_ace_remove_student', 'ace_ajax_remove_student');

// Get attendance form
function ace_ajax_get_attendance_form() {
    check_ajax_referer('ace_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Insufficient permissions');
    }

    $session_id = intval($_GET['session_id']);
    global $wpdb;

    // Get appointment ID from session
    $appointment_id = $wpdb->get_var($wpdb->prepare(
        "SELECT appointment_id FROM {$wpdb->prefix}amelia_class_sessions WHERE id = %d",
        $session_id
    ));

    // Get students for this appointment
    $students = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            u.*, 
            a.status as attendance_status,
            a.notes as attendance_notes
        FROM {$wpdb->prefix}amelia_users u
        JOIN {$wpdb->prefix}amelia_customer_bookings cb ON cb.customerId = u.id
        LEFT JOIN {$wpdb->prefix}amelia_class_attendance a ON a.student_id = u.id AND a.session_id = %d
        WHERE cb.appointmentId = %d AND u.type = 'customer'
        ORDER BY u.firstName, u.lastName",
        $session_id,
        $appointment_id
    ));

    ob_start();
    ?>
    <div class="attendance-form">
        <h3><?php _e('Take Attendance', 'amelia-class-extension'); ?></h3>
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
    <?php
    $html = ob_get_clean();
    
    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_ace_get_attendance_form', 'ace_ajax_get_attendance_form');

// Save attendance
function ace_ajax_save_attendance() {
    check_ajax_referer('ace_save_attendance', 'attendance_nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Insufficient permissions');
    }

    $session_id = intval($_POST['session_id']);
    $attendance_data = $_POST['attendance'];
    
    global $wpdb;
    
    foreach ($attendance_data as $student_id => $data) {
        $wpdb->replace(
            $wpdb->prefix . 'amelia_class_attendance',
            array(
                'session_id' => $session_id,
                'student_id' => intval($student_id),
                'status' => sanitize_text_field($data['status']),
                'notes' => sanitize_textarea_field($data['notes'])
            ),
            array('%d', '%d', '%s', '%s')
        );
    }
    
    wp_send_json_success();
}
add_action('wp_ajax_ace_save_attendance', 'ace_ajax_save_attendance');

// Get class sessions
function ace_ajax_get_class_sessions() {
    check_ajax_referer('ace_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Insufficient permissions');
    }

    $class_id = intval($_GET['class_id']);
    
    global $wpdb;
    
    $sessions = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            s.*,
            a.bookingStart,
            a.bookingEnd,
            a.status as appointment_status
        FROM {$wpdb->prefix}amelia_class_sessions s
        JOIN {$wpdb->prefix}amelia_appointments a ON a.id = s.appointment_id
        WHERE s.class_id = %d
        ORDER BY a.bookingStart ASC",
        $class_id
    ));
    
    wp_send_json_success(['sessions' => $sessions]);
}
add_action('wp_ajax_ace_get_class_sessions', 'ace_ajax_get_class_sessions');

// Save session modifications
function ace_ajax_save_session() {
    check_ajax_referer('ace_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Insufficient permissions');
    }

    $session_id = intval($_POST['session_id']);
    $new_date = sanitize_text_field($_POST['date']);
    $new_time = sanitize_text_field($_POST['time']);
    
    global $wpdb;
    
    // Get appointment ID
    $appointment_id = $wpdb->get_var($wpdb->prepare(
        "SELECT appointment_id FROM {$wpdb->prefix}amelia_class_sessions WHERE id = %d",
        $session_id
    ));
    
    if ($appointment_id) {
        $datetime = $new_date . ' ' . $new_time;
        $end_datetime = date('Y-m-d H:i:s', strtotime($datetime . ' +1 hour'));
        
        $wpdb->update(
            $wpdb->prefix . 'amelia_appointments',
            array(
                'bookingStart' => $datetime,
                'bookingEnd' => $end_datetime
            ),
            array('id' => $appointment_id),
            array('%s', '%s'),
            array('%d')
        );
        
        wp_send_json_success();
    } else {
        wp_send_json_error('Invalid session');
    }
}
add_action('wp_ajax_ace_save_session', 'ace_ajax_save_session');
?>