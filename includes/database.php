<?php
// includes/database.php

if (!defined('ABSPATH')) {
    exit;
}

class Survey_Database
{

    public static function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Tabela za podatke polaznika
        $table_participants = $wpdb->prefix . 'course_participants';
        $sql1 = "CREATE TABLE $table_participants (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            participant_name varchar(200) NOT NULL,
            company varchar(255) NOT NULL,
            address varchar(255) NOT NULL,
            position varchar(255) NOT NULL,
            phone varchar(50) NOT NULL,
            mobile varchar(50) NOT NULL,
            email varchar(100) NOT NULL,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            language varchar(2) DEFAULT 'sr',
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Tabela za feedback upitnik
        $table_feedback = $wpdb->prefix . 'course_feedback';
        $sql2 = "CREATE TABLE $table_feedback (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            expectations_met varchar(10) NOT NULL,
            expectations_level int(1),
            lecture_quality int(1),
            lecturer_quality int(1),
            practical_application int(1),
            literature int(1),
            premises int(1),
            food int(1),
            cooperation int(1),
            advanced_step7 text,
            other_courses text,
            improvements text,
            additional_comments text,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            language varchar(2) DEFAULT 'sr',
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
    }

    public static function save_participant($data)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'course_participants';

        $result = $wpdb->insert(
            $table_name,
            array(
                'participant_name' => sanitize_text_field($data['participant_name']),
                'company' => sanitize_text_field($data['company']),
                'address' => sanitize_text_field($data['address']),
                'position' => sanitize_text_field($data['position']),
                'phone' => sanitize_text_field($data['phone']),
                'mobile' => sanitize_text_field($data['mobile']),
                'email' => sanitize_email($data['email']),
                'language' => sanitize_text_field($data['language'])
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        return $result;
    }

    // Ostale funkcije ostaju iste...
    public static function save_feedback($data)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'course_feedback';

        $result = $wpdb->insert(
            $table_name,
            array(
                'expectations_met' => sanitize_text_field($data['expectations_met']),
                'expectations_level' => intval($data['expectations_level']),
                'lecture_quality' => intval($data['lecture_quality']),
                'lecturer_quality' => intval($data['lecturer_quality']),
                'practical_application' => intval($data['practical_application']),
                'literature' => intval($data['literature']),
                'premises' => intval($data['premises']),
                'food' => intval($data['food']),
                'cooperation' => intval($data['cooperation']),
                'advanced_step7' => sanitize_textarea_field($data['advanced_step7']),
                'other_courses' => sanitize_textarea_field($data['other_courses']),
                'improvements' => sanitize_textarea_field($data['improvements']),
                'additional_comments' => sanitize_textarea_field($data['additional_comments']),
                'language' => sanitize_text_field($data['language'])
            ),
            array('%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s')
        );

        return $result;
    }

    public static function get_participants($limit = 20, $offset = 0)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'course_participants';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY submitted_at DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            )
        );

        return $results;
    }

    public static function get_feedback($limit = 20, $offset = 0)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'course_feedback';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY submitted_at DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            )
        );

        return $results;
    }

    public static function get_participants_count()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'course_participants';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }

    public static function get_feedback_count()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'course_feedback';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }
}
