<?php
class Amelia_Class_REST_API {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_rest_fields'));
    }

    public function register_rest_fields() {
        register_rest_field('amelia_class', 'meta', array(
            'get_callback' => function($post) {
                return array(
                    'amelia_class_teacher' => get_post_meta($post['id'], 'amelia_class_teacher', true),
                    'amelia_class_schedule' => get_post_meta($post['id'], 'amelia_class_schedule', true),
                    'amelia_class_students' => get_post_meta($post['id'], 'amelia_class_students', true),
                    'amelia_class_sessions' => get_post_meta($post['id'], 'amelia_class_sessions', true)
                );
            }
        ));
    }
}