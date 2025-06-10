<?php

/**
 * Plugin Name: Dual Survey System
 * Description: Sistem za dva tipa upitnika - registracija polaznika i feedback upitnik
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SURVEY_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SURVEY_PLUGIN_URL', plugin_dir_url(__FILE__));

class DualSurveySystem
{

    public function __construct()
    {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init()
    {
        // Load required files
        require_once SURVEY_PLUGIN_PATH . 'includes/database.php';
        require_once SURVEY_PLUGIN_PATH . 'includes/shortcodes.php';
        require_once SURVEY_PLUGIN_PATH . 'includes/admin.php';

        // Initialize classes
        Survey_Shortcodes::init();
        Survey_Admin::init();

        // Load styles and scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function activate()
    {
        // Load database class first
        require_once SURVEY_PLUGIN_PATH . 'includes/database.php';

        // Create database tables
        if (class_exists('Survey_Database')) {
            Survey_Database::create_tables();
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    public function deactivate()
    {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    public function enqueue_frontend_assets()
    {
        wp_enqueue_script('jquery');
        wp_enqueue_style('survey-style', SURVEY_PLUGIN_URL . 'assets/survey-style.css', array(), '1.0.0');
        wp_enqueue_script('survey-script', SURVEY_PLUGIN_URL . 'assets/survey-script.js', array('jquery'), '1.0.0', true);

        // Localize script for AJAX
        wp_localize_script('survey-script', 'survey_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('survey_nonce')
        ));
    }

    public function enqueue_admin_assets()
    {
        $screen = get_current_screen();
        if (strpos($screen->id, 'survey') !== false) {
            wp_enqueue_style('survey-admin-style', SURVEY_PLUGIN_URL . 'assets/admin-style.css', array(), '1.0.0');
        }
    }
}

// Initialize the plugin
new DualSurveySystem();
