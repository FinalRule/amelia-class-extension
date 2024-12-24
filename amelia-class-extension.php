<?php
/**
 * Plugin Name: Amelia Class Extension
 * Description: Adds class management functionality to Amelia booking plugin
 * Version: 1.0
 * Author: Your Name
 * Text Domain: amelia-class-extension
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ACE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ACE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Create tables on plugin activation
function ace_create_db_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}amelia_class_sessions (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        class_id bigint(20) NOT NULL,
        appointment_id bigint(20) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY class_id (class_id),
        KEY appointment_id (appointment_id)
    ) $charset_collate;

    CREATE TABLE IF NOT EXISTS {$wpdb->prefix}amelia_class_attendance (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        session_id bigint(20) NOT NULL,
        student_id bigint(20) NOT NULL,
        status varchar(20) NOT NULL,
        notes text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY session_id (session_id),
        KEY student_id (student_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Verify table creation
    $table_name = $wpdb->prefix . 'amelia_class_sessions';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    if (!$table_exists) {
        error_log('Failed to create table: ' . $table_name);
    }
}
register_activation_hook(__FILE__, 'ace_create_db_tables');

// Class registration
function ace_register_class_post_type() {
    register_post_type('amelia_class', array(
        'labels' => array(
            'name' => 'Classes',
            'singular_name' => 'Class',
            'add_new' => 'Add New Class',
            'add_new_item' => 'Add New Class',
            'edit_item' => 'Edit Class',
            'new_item' => 'New Class',
            'view_item' => 'View Class',
            'search_items' => 'Search Classes',
            'not_found' => 'No classes found',
            'not_found_in_trash' => 'No classes found in trash'
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor'),
        'menu_icon' => 'dashicons-groups',
        'show_in_menu' => true
    ));
}
add_action('init', 'ace_register_class_post_type');

// Add meta box
function ace_add_class_meta_box() {
    add_meta_box(
        'class_details',
        'Class Details',
        'ace_class_meta_box_callback',
        'amelia_class',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'ace_add_class_meta_box');

// Meta box callback
function ace_class_meta_box_callback($post) {
    require_once ACE_PLUGIN_PATH . 'templates/class-meta-box.php';
}

// Save class meta and create appointments
function ace_save_class_meta($post_id) {
    // Verify nonce and permissions
    if (!isset($_POST['ace_class_meta_nonce']) || 
        !wp_verify_nonce($_POST['ace_class_meta_nonce'], 'ace_save_class_meta') ||
        defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ||
        !current_user_can('edit_post', $post_id)) {
        return;
    }

    global $wpdb;

    // Save basic meta
    if (isset($_POST['teacher_id'])) {
        update_post_meta($post_id, '_teacher_id', sanitize_text_field($_POST['teacher_id']));
    }

    // Save schedule
    $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
    $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
    $class_time = isset($_POST['class_time']) ? sanitize_text_field($_POST['class_time']) : '';
    $class_days = isset($_POST['class_days']) ? array_map('sanitize_text_field', $_POST['class_days']) : array();

    update_post_meta($post_id, '_start_date', $start_date);
    update_post_meta($post_id, '_end_date', $end_date);
    update_post_meta($post_id, '_class_time', $class_time);
    update_post_meta($post_id, '_class_days', $class_days);

    // Check if we have all required data
    if (empty($start_date) || empty($end_date) || empty($class_time) || 
        empty($class_days) || empty($_POST['teacher_id'])) {
        return;
    }

    $teacher_id = intval($_POST['teacher_id']);

    // Get or create service
    $service = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}amelia_services WHERE name = 'Class Session'");
    if (!$service) {
        $wpdb->insert(
            $wpdb->prefix . 'amelia_services',
            array(
                'name' => 'Class Session',
                'description' => 'Automatically created for class sessions',
                'color' => '#1788FB',
                'price' => 0,
                'status' => 'visible',
                'categoryId' => null,
                'minCapacity' => 1,
                'maxCapacity' => 20,
                'duration' => 3600,
                'timeBefore' => 0,
                'timeAfter' => 0,
                'show' => 1
            )
        );
        $service_id = $wpdb->insert_id;

        // Link service to provider
        $wpdb->insert(
            $wpdb->prefix . 'amelia_providers_to_services',
            array(
                'userId' => $teacher_id,
                'serviceId' => $service_id,
                'price' => 0,
                'minCapacity' => 1,
                'maxCapacity' => 20
            )
        );
    } else {
        $service_id = $service->id;
    }

    // Remove existing sessions
    $existing_sessions = $wpdb->get_results($wpdb->prepare(
        "SELECT appointment_id FROM {$wpdb->prefix}amelia_class_sessions WHERE class_id = %d",
        $post_id
    ));

    foreach ($existing_sessions as $session) {
        $wpdb->delete($wpdb->prefix . 'amelia_customer_bookings', array('appointmentId' => $session->appointment_id));
        $wpdb->delete($wpdb->prefix . 'amelia_appointments', array('id' => $session->appointment_id));
    }
    $wpdb->delete($wpdb->prefix . 'amelia_class_sessions', array('class_id' => $post_id));

    // Create new sessions
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $end->modify('+1 day');
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end);

    foreach ($period as $date) {
        $day_of_week = strtolower($date->format('l'));
        if (in_array($day_of_week, $class_days)) {
            $booking_start = $date->format('Y-m-d') . ' ' . $class_time;
            $booking_end = date('Y-m-d H:i:s', strtotime($booking_start . ' +1 hour'));

            // Create appointment
            $appointment_data = array(
                'serviceId' => $service_id,
                'providerId' => $teacher_id,
                'status' => 'approved',
                'bookingStart' => $booking_start,
                'bookingEnd' => $booking_end,
                'notifyParticipants' => 0,
                'created' => current_time('mysql')
            );

            $wpdb->insert($wpdb->prefix . 'amelia_appointments', $appointment_data);
            $appointment_id = $wpdb->insert_id;

            if ($appointment_id) {
                // Link to class
                $wpdb->insert(
                    $wpdb->prefix . 'amelia_class_sessions',
                    array(
                        'class_id' => $post_id,
                        'appointment_id' => $appointment_id,
                        'created_at' => current_time('mysql')
                    )
                );

                // Add existing students
                $students = get_post_meta($post_id, '_students', true);
                if (!empty($students) && is_array($students)) {
                    foreach ($students as $student_id) {
                        $wpdb->insert(
                            $wpdb->prefix . 'amelia_customer_bookings',
                            array(
                                'appointmentId' => $appointment_id,
                                'customerId' => $student_id,
                                'status' => 'approved',
                                'price' => 0,
                                'persons' => 1,
                                'created' => current_time('mysql')
                            )
                        );
                    }
                }
            }
        }
    }
}
add_action('save_post_amelia_class', 'ace_save_class_meta');

// Helper function to get class sessions
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

// Enqueue scripts and styles
function ace_enqueue_admin_scripts($hook) {
    global $post;

    if (!in_array($hook, array('post.php', 'post-new.php')) || 
        !is_object($post) || 
        'amelia_class' !== $post->post_type) {
        return;
    }

    wp_enqueue_style('ace-admin', ACE_PLUGIN_URL . 'css/admin.css');
    wp_enqueue_script('ace-admin', ACE_PLUGIN_URL . 'js/admin.js', array('jquery'), '1.0', true);
    wp_localize_script('ace-admin', 'aceAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ace_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'ace_enqueue_admin_scripts');

// Include AJAX handlers
require_once ACE_PLUGIN_PATH . 'includes/ajax-handlers.php';