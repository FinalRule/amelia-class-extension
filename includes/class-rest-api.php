<?php
/**
 * Register REST API endpoints for Amelia Class Extension
 *
 * @package Amelia_Class_Extension
 */

class Amelia_Class_Rest_Api {

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_rest_fields'));
    }

    /**
     * Register REST API fields for amelia_class post type
     */
    public function register_rest_fields() {
        register_rest_field('amelia_class', 'class_details', array(
            'get_callback' => array($this, 'get_class_details'),
            'schema' => array(
                'description' => 'Class Details',
                'type'        => 'object'
            ),
        ));
    }

    /**
     * Get callback for class details rest field
     *
     * @param array $post Post object.
     * @return array Class details.
     */
    public function get_class_details($post) {
        return array(
            'teacher'   => get_post_meta($post['id'], '_amelia_class_teacher', true),
            'schedule'  => get_post_meta($post['id'], '_amelia_class_schedule', true),
            'students'  => get_post_meta($post['id'], '_amelia_class_students', true),
            'sessions'  => get_post_meta($post['id'], '_amelia_class_sessions', true)
        );
    }
}