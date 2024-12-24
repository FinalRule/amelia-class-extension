<?php
// includes/reports.php

if (!defined('ABSPATH')) {
    exit;
}

function ace_generate_attendance_report($class_id, $start_date, $end_date) {
    global $wpdb;
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT 
            c.firstName, 
            c.lastName,
            COUNT(a.id) as total_sessions,
            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count
        FROM {$wpdb->prefix}amelia_customers c
        LEFT JOIN {$wpdb->prefix}amelia_class_attendance a 
        ON c.id = a.student_id
        WHERE a.session_id IN (
            SELECT id FROM {$wpdb->prefix}amelia_class_sessions 
            WHERE class_id = %d 
            AND created_at BETWEEN %s AND %s
        )
        GROUP BY c.id",
        $class_id,
        $start_date,
        $end_date
    ));
}

function ace_generate_progress_report($class_id, $start_date, $end_date) {
    global $wpdb;
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT 
            c.firstName,
            c.lastName,
            AVG(p.progress_value) as average_progress,
            MIN(p.progress_value) as min_progress,
            MAX(p.progress_value) as max_progress,
            COUNT(p.id) as progress_entries
        FROM {$wpdb->prefix}amelia_customers c
        LEFT JOIN {$wpdb->prefix}amelia_class_progress p 
        ON c.id = p.student_id
        WHERE p.class_id = %d 
        AND p.progress_date BETWEEN %s AND %s
        GROUP BY c.id",
        $class_id,
        $start_date,
        $end_date
    ));
}

function ace_generate_comprehensive_report($class_id, $start_date, $end_date) {
    $attendance = ace_generate_attendance_report($class_id, $start_date, $end_date);
    $progress = ace_generate_progress_report($class_id, $start_date, $end_date);
    
    $report = array();
    foreach ($attendance as $student) {
        $student_progress = array_filter($progress, function($p) use ($student) {
            return $p->firstName === $student->firstName && $p->lastName === $student->lastName;
        });
        
        $report[] = array(
            'name' => $student->firstName . ' ' . $student->lastName,
            'attendance_rate' => ($student->total_sessions > 0) ? 
                round(($student->present_count / $student->total_sessions) * 100, 2) : 0,
            'present_count' => $student->present_count,
            'absent_count' => $student->absent_count,
            'late_count' => $student->late_count,
            'average_progress' => !empty($student_progress) ? reset($student_progress)->average_progress : 0,
            'progress_trend' => !empty($student_progress) ? 
                (reset($student_progress)->max_progress - reset($student_progress)->min_progress) : 0
        );
    }
    
    return $report;
}
