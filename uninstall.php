<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Obriši tabele
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}course_participants");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}course_feedback");

// Obriši opcije
delete_option('survey_notification_email');
delete_option('survey_test_mode');
delete_option('survey_plugin_version');
