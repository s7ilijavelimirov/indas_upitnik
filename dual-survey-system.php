<?php
/**
 * Plugin Name: INDAS Survey System
 * Description: Sistem upitnika sa QR kodovima za INDAS kurseve
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: indas-survey
 */

// Sprečavanje direktnog pristupa
if (!defined('ABSPATH')) {
    exit;
}

// Definisanje konstanti
define('INDAS_SURVEY_VERSION', '1.0.0');
define('INDAS_SURVEY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('INDAS_SURVEY_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Glavna klasa plugina
 */
class INDAS_Survey_Plugin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    public function init() {
        // Učitaj jezike
        load_plugin_textdomain('indas-survey', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Uključi potrebne fajlove
        $this->include_files();
        
        // Inicijalizuj komponente
        $this->init_components();
        
        // Registruj hook-ove
        $this->register_hooks();
    }
    
    private function include_files() {
        // Core klase
        require_once INDAS_SURVEY_PLUGIN_DIR . 'includes/class-database.php';
        require_once INDAS_SURVEY_PLUGIN_DIR . 'includes/class-admin.php';  
        require_once INDAS_SURVEY_PLUGIN_DIR . 'includes/class-frontend.php';
        require_once INDAS_SURVEY_PLUGIN_DIR . 'includes/class-qr-generator.php';
        require_once INDAS_SURVEY_PLUGIN_DIR . 'includes/class-session-manager.php';
        require_once INDAS_SURVEY_PLUGIN_DIR . 'includes/class-csv-export.php';
        
        // Survey klase
        require_once INDAS_SURVEY_PLUGIN_DIR . 'includes/surveys/class-personal-survey.php';
        require_once INDAS_SURVEY_PLUGIN_DIR . 'includes/surveys/class-feedback-survey.php';
        
        // Helper klase
        require_once INDAS_SURVEY_PLUGIN_DIR . 'includes/helpers/class-form-validator.php';
        require_once INDAS_SURVEY_PLUGIN_DIR . 'includes/helpers/class-email-sender.php';
    }
    
    private function init_components() {
        // Inicijalizuj bazu podataka
        INDAS_Survey_Database::get_instance();
        
        // Inicijalizuj admin interfejs
        if (is_admin()) {
            INDAS_Survey_Admin::get_instance();
        }
        
        // Inicijalizuj frontend
        INDAS_Survey_Frontend::get_instance();
        
        // Inicijalizuj QR generator
        INDAS_Survey_QR_Generator::get_instance();
        
        // Inicijalizuj session manager
        INDAS_Survey_Session_Manager::get_instance();
    }
    
    private function register_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Enqueue skripte i stilove
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX hook-ovi
        add_action('wp_ajax_indas_submit_survey', array($this, 'handle_survey_submission'));
        add_action('wp_ajax_nopriv_indas_submit_survey', array($this, 'handle_survey_submission'));
        
        // Shortcode registracija
        add_shortcode('indas_personal_survey', array($this, 'render_personal_survey'));
        add_shortcode('indas_feedback_survey', array($this, 'render_feedback_survey'));
        add_shortcode('indas_qr_code', array($this, 'render_qr_code'));
    }
    
    public function activate() {
        // Kreiraj tabele u bazi
        INDAS_Survey_Database::create_tables();
        
        // Kreiraj potrebne stranice
        $this->create_survey_pages();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Cleanup ako je potreban
        flush_rewrite_rules();
    }
    
    private function create_survey_pages() {
        // Kreiraj stranicu za lični upitnik
        $personal_page = array(
            'post_title'   => __('Lični podaci - Upitnik', 'indas-survey'),
            'post_content' => '[indas_personal_survey]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_name'    => 'indas-personal-survey'
        );
        
        if (!get_page_by_path('indas-personal-survey')) {
            wp_insert_post($personal_page);
        }
        
        // Kreiraj stranicu za feedback upitnik
        $feedback_page = array(
            'post_title'   => __('Feedback - Upitnik', 'indas-survey'),
            'post_content' => '[indas_feedback_survey]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_name'    => 'indas-feedback-survey'
        );
        
        if (!get_page_by_path('indas-feedback-survey')) {
            wp_insert_post($feedback_page);
        }
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style(
            'indas-survey-style',
            INDAS_SURVEY_PLUGIN_URL . 'assets/css/survey-style.css',
            array(),
            INDAS_SURVEY_VERSION
        );
        
        wp_enqueue_script(
            'indas-survey-script',
            INDAS_SURVEY_PLUGIN_URL . 'assets/js/survey-script.js',
            array('jquery'),
            INDAS_SURVEY_VERSION,
            true
        );
        
        // Localize script za AJAX
        wp_localize_script('indas-survey-script', 'indas_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('indas_survey_nonce'),
            'messages' => array(
                'required_field' => __('Ovo polje je obavezno', 'indas-survey'),
                'invalid_email'  => __('Neispravna email adresa', 'indas-survey'),
                'submit_success' => __('Upitnik je uspešno poslat', 'indas-survey'),
                'submit_error'   => __('Greška pri slanju upitnika', 'indas-survey'),
                'already_submitted' => __('Već ste popunili ovaj upitnik', 'indas-survey')
            )
        ));
    }
    
    public function enqueue_admin_scripts($hook) {
        // Učitaj admin skripte samo na našim stranicama
        if (strpos($hook, 'indas-survey') === false) {
            return;
        }
        
        wp_enqueue_style(
            'indas-survey-admin-style',
            INDAS_SURVEY_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            INDAS_SURVEY_VERSION
        );
        
        wp_enqueue_script(
            'indas-survey-admin-script',
            INDAS_SURVEY_PLUGIN_URL . 'assets/js/admin-script.js',
            array('jquery'),
            INDAS_SURVEY_VERSION,
            true
        );
    }
    
    public function handle_survey_submission() {
        // Verifikuj nonce
        if (!wp_verify_nonce($_POST['nonce'], 'indas_survey_nonce')) {
            wp_die('Security check failed');
        }
        
        $survey_type = sanitize_text_field($_POST['survey_type']);
        $session_id = sanitize_text_field($_POST['session_id']);
        
        // Proveri session za anonimni upitnik
        if ($survey_type === 'feedback') {
            if (INDAS_Survey_Session_Manager::is_session_used($session_id)) {
                wp_send_json_error(array(
                    'message' => __('Već ste popunili ovaj upitnik', 'indas-survey')
                ));
            }
        }
        
        // Delegiraj processing odgovarajućoj klasi
        if ($survey_type === 'personal') {
            $result = INDAS_Survey_Personal::process_submission($_POST);
        } else {
            $result = INDAS_Survey_Feedback::process_submission($_POST);
        }
        
        if ($result['success']) {
            // Označiti session kao korišćen za feedback
            if ($survey_type === 'feedback') {
                INDAS_Survey_Session_Manager::mark_session_used($session_id);
            }
            
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    public function render_personal_survey($atts) {
        $atts = shortcode_atts(array(
            'lang' => 'sr',
            'session_id' => ''
        ), $atts);
        
        return INDAS_Survey_Personal::render_form($atts);
    }
    
    public function render_feedback_survey($atts) {
        $atts = shortcode_atts(array(
            'lang' => 'sr',
            'session_id' => ''
        ), $atts);
        
        return INDAS_Survey_Feedback::render_form($atts);
    }
    
    public function render_qr_code($atts) {
        $atts = shortcode_atts(array(
            'type' => 'personal', // personal ili feedback
            'lang' => 'sr',
            'size' => '200'
        ), $atts);
        
        return INDAS_Survey_QR_Generator::generate_qr_shortcode($atts);
    }
}

// Inicijalizuj plugin
INDAS_Survey_Plugin::get_instance();