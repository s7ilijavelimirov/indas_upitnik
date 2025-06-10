<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Session Manager klasa - upravlja session-ima za QR kodove
 */
class INDAS_Survey_Session_Manager {
    
    private static $instance = null;
    private $db;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->db = INDAS_Survey_Database::get_instance();
        
        // Hook za početak session-a
        add_action('init', array($this, 'maybe_start_survey_session'));
    }
    
    /**
     * Pokreni survey session ako postoji session_id u URL-u
     */
    public function maybe_start_survey_session() {
        if (!is_admin() && isset($_GET['session_id'])) {
            $session_id = sanitize_text_field($_GET['session_id']);
            $this->validate_and_start_session($session_id);
        }
    }
    
    /**
     * Validiraj i pokreni session
     */
    private function validate_and_start_session($session_id) {
        // Pronađi session u bazi
        $session = $this->get_session_data($session_id);
        
        if (!$session) {
            $this->handle_invalid_session('Session not found');
            return false;
        }
        
        // Proveri da li je session istekao
        if ($this->is_session_expired($session)) {
            $this->handle_invalid_session('Session expired');
            return false;
        }
        
        // Proveri da li je već korišćen (samo za feedback)
        if ($session->survey_type === 'feedback' && $this->is_session_used($session_id)) {
            $this->handle_already_used_session();
            return false;
        }
        
        // Session je valjan, sačuvaj u WordPress session
        $this->store_session_data($session);
        
        return true;
    }
    
    /**
     * Preuzmi session podatke iz baze
     */
    private function get_session_data($session_id) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT * FROM " . INDAS_Survey_Database::$sessions_table . " 
             WHERE session_id = %s",
            $session_id
        );
        
        return $wpdb->get_row($sql);
    }
    
    /**
     * Proveri da li je session istekao
     */
    private function is_session_expired($session) {
        if (empty($session->expires_at)) {
            return false;
        }
        
        return strtotime($session->expires_at) < time();
    }
    
    /**
     * Proveri da li je session već korišćen
     */
    public static function is_session_used($session_id) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT status FROM " . INDAS_Survey_Database::$sessions_table . " 
             WHERE session_id = %s",
            $session_id
        );
        
        $status = $wpdb->get_var($sql);
        
        return $status === 'used';
    }
    
    /**
     * Označi session kao korišćen
     */
    public static function mark_session_used($session_id) {
        global $wpdb;
        
        return $wpdb->update(
            INDAS_Survey_Database::$sessions_table,
            array(
                'status' => 'used',
                'koriscen' => current_time('mysql')
            ),
            array('session_id' => $session_id),
            array('%s', '%s'),
            array('%s')
        );
    }
    
    /**
     * Sačuvaj session podatke u WordPress session/cookies
     */
    private function store_session_data($session) {
        // Koristimo WordPress transients za session storage
        $session_data = array(
            'session_id' => $session->session_id,
            'survey_type' => $session->survey_type,
            'jezik' => $session->jezik,
            'started_at' => time(),
            'expires_at' => strtotime($session->expires_at)
        );
        
        // Sačuvaj u transient sa session_id kao ključ
        set_transient('indas_session_' . $session->session_id, $session_data, 24 * HOUR_IN_SECONDS);
        
        // Takođe sačuvaj u cookie za lakši pristup
        setcookie(
            'indas_survey_session', 
            $session->session_id, 
            strtotime($session->expires_at), 
            COOKIEPATH, 
            COOKIE_DOMAIN, 
            is_ssl(), 
            true
        );
    }
    
    /**
     * Preuzmi aktuelni session
     */
    public function get_current_session() {
        // Prvo pokušaj iz cookie
        if (isset($_COOKIE['indas_survey_session'])) {
            $session_id = sanitize_text_field($_COOKIE['indas_survey_session']);
            $session_data = get_transient('indas_session_' . $session_id);
            
            if ($session_data && $session_data['expires_at'] > time()) {
                return $session_data;
            }
        }
        
        // Pokušaj iz GET parametra
        if (isset($_GET['session_id'])) {
            $session_id = sanitize_text_field($_GET['session_id']);
            $session_data = get_transient('indas_session_' . $session_id);
            
            if ($session_data) {
                return $session_data;
            }
        }
        
        return null;
    }
    
    /**
     * Obriši trenutni session
     */
    public function clear_current_session() {
        $session = $this->get_current_session();
        
        if ($session) {
            // Obriši transient
            delete_transient('indas_session_' . $session['session_id']);
            
            // Obriši cookie
            setcookie(
                'indas_survey_session', 
                '', 
                time() - 3600, 
                COOKIEPATH, 
                COOKIE_DOMAIN
            );
        }
    }
    
    /**
     * Preuzmi session podatke za form
     */
    public function get_session_for_form() {
        $session = $this->get_current_session();
        
        if (!$session) {
            return array(
                'session_id' => '',
                'survey_type' => '',
                'jezik' => 'sr',
                'valid' => false,
                'error' => 'No active session'
            );
        }
        
        return array(
            'session_id' => $session['session_id'],
            'survey_type' => $session['survey_type'],
            'jezik' => $session['jezik'],
            'valid' => true,
            'expires_at' => $session['expires_at']
        );
    }
    
    /**
     * Obriši istekle session-e iz transients
     */
    public function cleanup_expired_transients() {
        global $wpdb;
        
        // WordPress automatski briše istekle transients, ali možemo forsirane da očistimo
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_timeout_indas_session_%' 
             AND option_value < UNIX_TIMESTAMP()"
        );
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_indas_session_%' 
             AND option_name NOT IN (
                 SELECT CONCAT('_transient_', SUBSTRING(option_name, 20)) 
                 FROM {$wpdb->options} 
                 WHERE option_name LIKE '_transient_timeout_indas_session_%'
             )"
        );
    }
    
    /**
     * Handle nevalidni session
     */
    private function handle_invalid_session($reason = '') {
        // Log greške za debugging
        if (WP_DEBUG) {
            error_log('INDAS Survey: Invalid session - ' . $reason);
        }
        
        // Redirectuj na error stranicu ili prikaži poruku
        $this->show_session_error('Neispravan ili istekao QR kod. Molimo skenirati novi QR kod.');
    }
    
    /**
     * Handle već korišćen session
     */
    private function handle_already_used_session() {
        $this->show_session_error('Ovaj QR kod je već korišćen. Svaki QR kod može biti korišćen samo jednom.');
    }
    
    /**
     * Prikaži session error
     */
    private function show_session_error($message) {
        // Dodaj error message u WordPress notices
        add_action('wp_footer', function() use ($message) {
            echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    var errorDiv = document.createElement("div");
                    errorDiv.className = "indas-session-error";
                    errorDiv.innerHTML = "<h2>Greška sa QR kodom</h2><p>' . esc_js($message) . '</p>";
                    errorDiv.style.cssText = `
                        position: fixed;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        background: white;
                        border: 2px solid #dc3232;
                        border-radius: 8px;
                        padding: 20px;
                        max-width: 400px;
                        text-align: center;
                        z-index: 9999;
                        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                    `;
                    document.body.appendChild(errorDiv);
                    
                    // Auto-close nakon 5 sekundi
                    setTimeout(function() {
                        errorDiv.remove();
                    }, 5000);
                    
                    // Close na click
                    errorDiv.addEventListener("click", function() {
                        errorDiv.remove();
                    });
                });
            </script>';
        });
        
        // Sakri form content
        add_filter('the_content', function($content) {
            if (has_shortcode($content, 'indas_personal_survey') || has_shortcode($content, 'indas_feedback_survey')) {
                return '<div class="indas-session-error-placeholder" style="text-align: center; padding: 40px;">
                    <h3>QR kod greška</h3>
                    <p>Molimo skenirati valjan QR kod da biste pristupili upitniku.</p>
                </div>';
            }
            return $content;
        });
    }
    
    /**
     * Generiši novi session ID
     */
    public function generate_session_id() {
        return 'indas_' . wp_generate_password(20, false, false) . '_' . time();
    }
    
    /**
     * Prosledi session info u JavaScript
     */
    public function localize_session_data() {
        $session = $this->get_session_for_form();
        
        wp_localize_script('indas-survey-script', 'indas_session', $session);
    }
    
    /**
     * Kreiraj novi session (wrapper za database create_session)
     */
    public function create_session($survey_type, $jezik = 'sr', $expires_hours = 24) {
        return $this->db->create_session($survey_type, $jezik, $expires_hours);
    }
    
    /**
     * Debug info za session
     */
    public function get_session_debug_info($session_id = null) {
        if (!$session_id) {
            $current_session = $this->get_current_session();
            $session_id = $current_session ? $current_session['session_id'] : null;
        }
        
        if (!$session_id) {
            return array('error' => 'No session ID provided');
        }
        
        $db_session = $this->get_session_data($session_id);
        $transient_session = get_transient('indas_session_' . $session_id);
        
        return array(
            'session_id' => $session_id,
            'db_session' => $db_session,
            'transient_session' => $transient_session,
            'is_expired' => $db_session ? $this->is_session_expired($db_session) : true,
            'is_used' => $this->is_session_used($session_id),
            'current_time' => time(),
            'expires_at' => $db_session ? strtotime($db_session->expires_at) : null
        );
    }
}