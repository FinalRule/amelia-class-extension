<?php
// includes/ajax-handlers.php

if (!defined('ABSPATH')) {
    exit;
}

// Add student to class
function ace_ajax_add_student() {
    check_ajax_referer('ace_nonce', 'nonce');
    
    error_log('Debug: ace_ajax_add_student called');
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ace_nonce')) {
        error_log('Debug: Nonce verification failed');
        wp_send_json_error('Security check failed');
        return;
    }
    
    if (!current_user_can('edit_posts')) {
        error_log('Debug: Insufficient permissions');
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    $class_id = intval($_POST['class_id']);
    $student_id = intval($_POST['student_id']);
  
    error_log("Debug: Adding student $student_id to class $class_id");

    global $wpdb;
    
    // Verify student exists
    $student = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}amelia_users WHERE id = %d AND type = 'customer'",
        $student_id
    ));
    
    if (!$student) {
        error_log('Debug: Invalid student');
        wp_send_json_error('Invalid student');
        return;
    }

    try {
        // Add to class meta
        $current_students = get_post_meta($class_id, '_students', true);
        if (!is_array($current_students)) {
            $current_students = array();
        }
        $current_students[] = $student_id;
        update_post_meta($class_id, '_students', array_unique($current_students));
        
        error_log('Debug: Student added successfully');
        wp_send_json_success();
    } catch (Exception $e) {
        error_log('Debug: Error adding student: ' . $e->getMessage());
        wp_send_json_error($e->getMessage());
    }
    

// Get all appointments for this class
    $appointments = $wpdb->get_results($wpdb->prepare(
        "SELECT appointment_id FROM {$wpdb->prefix}amelia_class_sessions WHERE class_id = %d",
        $class_id
    ));
    
    // Add student to each appointment
    foreach ($appointments as $session) {
        $wpdb->insert(
            $wpdb->prefix . 'amelia_customer_bookings',
            array(
                'appointmentId' => $session->appointment_id,
                'customerId' => $student_id,
                'status' => 'approved',
                'price' => 0,
                'persons' => 1,
                'created' => current_time('mysql')
            )
        );
    }
    
    // Add to class meta
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
    
    // Get all appointments for this class
    $appointments = $wpdb->get_results($wpdb->prepare(
        "SELECT appointment_id FROM {$wpdb->prefix}amelia_class_sessions WHERE class_id = %d",
        $class_id
    ));
    
    // Remove from each appointment
    foreach ($appointments as $session) {
        $wpdb->delete(
            $wpdb->prefix . 'amelia_customer_bookings',
            array(
                'appointmentId' => $session->appointment_id,
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

    // Get appointment ID
    $appointment = $wpdb->get_row($wpdb->prepare(
        "SELECT s.appointment_id, a.bookingStart 
         FROM {$wpdb->prefix}amelia_class_sessions s
         JOIN {$wpdb->prefix}amelia_appointments a ON a.id = s.appointment_id
         WHERE s.id = %d",
        $session_id
    ));

    if (!$appointment) {
        wp_send_json_error('Session not found');
        return;
    }

    // Get students
    $students = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            u.*, 
            ca.status as attendance_status,
            ca.notes as attendance_notes
        FROM {$wpdb->prefix}amelia_users u
        JOIN {$wpdb->prefix}amelia_customer_bookings cb ON cb.customerId = u.id
        LEFT JOIN {$wpdb->prefix}amelia_class_attendance ca ON ca.student_id = u.id AND ca.session_id = %d
        WHERE cb.appointmentId = %d AND u.type = 'customer'
        ORDER BY u.firstName, u.lastName",
        $session_id,
        $appointment->appointment_id
    ));

    ob_start();
    include(ACE_PLUGIN_PATH . 'templates/attendance-form.php');
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
                'notes' => sanitize_textarea_field($data['notes']),
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
    }
    
    wp_send_json_success();
}
add_action('wp_ajax_ace_save_attendance', 'ace_ajax_save_attendance');
