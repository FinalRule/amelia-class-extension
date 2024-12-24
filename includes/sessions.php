<?php
// includes/sessions.php

if (!defined('ABSPATH')) {
    exit;
}

function ace_create_class_appointments($class_id) {
    global $wpdb;
    
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $class_time = $_POST['class_time'];
    $class_days = $_POST['class_days'];
    $teacher_id = $_POST['teacher_id'];
    
    $dates = ace_get_class_dates($start_date, $end_date, $class_days);
    
    foreach ($dates as $date) {
        $appointment_data = array(
            'providerId' => $teacher_id,
            'serviceId' => 1, // You'll need to set up a service in Amelia
            'status' => 'approved',
            'bookingStart' => $date . ' ' . $class_time,
            'bookingEnd' => $date . ' ' . date('H:i', strtotime($class_time . ' +1 hour')),
            'notifyParticipants' => 1
        );
        
        $wpdb->insert($wpdb->prefix . 'amelia_appointments', $appointment_data);
        $appointment_id = $wpdb->insert_id;
        
        if ($appointment_id) {
            $wpdb->insert($wpdb->prefix . 'amelia_class_sessions', array(
                'class_id' => $class_id,
                'appointment_id' => $appointment_id
            ));
        }
    }
}

function ace_get_class_dates($start_date, $end_date, $class_days) {
    $dates = array();
    $current = new DateTime($start_date);
    $end = new DateTime($end_date);
    
    while ($current <= $end) {
        $day_of_week = strtolower($current->format('l'));
        if (in_array($day_of_week, $class_days)) {
            $dates[] = $current->format('Y-m-d');
        }
        $current->modify('+1 day');
    }
    
    return $dates;
}

function ace_get_class_sessions($class_id) {
    global $wpdb;
    
    return $wpdb->get_results($wpdb->prepare(
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
}
