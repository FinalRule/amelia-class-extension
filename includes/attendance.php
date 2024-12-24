<?php
// includes/attendance.php

if (!defined('ABSPATH')) {
    exit;
}

function ace_save_attendance($session_id, $student_id, $status, $notes = '') {
    global $wpdb;
    
    return $wpdb->replace(
        $wpdb->prefix . 'amelia_class_attendance',
        array(
            'session_id' => $session_id,
            'student_id' => $student_id,
            'status' => $status,
            'notes' => $notes
        ),
        array('%d', '%d', '%s', '%s')
    );
}

function ace_get_session_attendance($session_id) {
    global $wpdb;
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT 
            a.*,
            c.firstName,
            c.lastName
        FROM {$wpdb->prefix}amelia_class_attendance a
        JOIN {$wpdb->prefix}amelia_customers c ON c.id = a.student_id
        WHERE a.session_id = %d",
        $session_id
    ));
}

function ace_get_student_attendance($student_id, $class_id) {
    global $wpdb;
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT 
            a.*,
            s.appointment_id,
            ap.bookingStart
        FROM {$wpdb->prefix}amelia_class_attendance a
        JOIN {$wpdb->prefix}amelia_class_sessions s ON s.id = a.session_id
        JOIN {$wpdb->prefix}amelia_appointments ap ON ap.id = s.appointment_id
        WHERE a.student_id = %d
        AND s.class_id = %d
        ORDER BY ap.bookingStart DESC",
        $student_id,
        $class_id
    ));
}
