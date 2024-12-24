<?php
// Create this as a temporary file, like create-table.php
require_once('../../../wp-load.php');

global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}amelia_class_sessions (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    class_id bigint(20) NOT NULL,
    appointment_id bigint(20) NOT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY class_id (class_id),
    KEY appointment_id (appointment_id)
) $charset_collate;";

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);

// Add this to check if table was created
$table_name = $wpdb->prefix . 'amelia_class_sessions';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

if ($table_exists) {
    echo "Table created successfully!";
} else {
    echo "Table creation failed. Error: " . $wpdb->last_error;
}