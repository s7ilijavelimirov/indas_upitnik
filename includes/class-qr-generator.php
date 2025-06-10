<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * QR Generator klasa
 */
class INDAS_Survey_QR_Generator {
    
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
    }
    
    /**
     * Generiši QR kod za shortcode
     */
    public static function generate_qr_shortcode($atts) {
        $instance = self::get_instance();
        
        $type = $atts['type']; // personal ili feedback
        $lang = $atts['lang'];
        $size = intval($atts['size']);
        
        // Kreiraj session
        $session_id = $instance->db->create_session($type, $lang);
        
        if (!$session_id) {
            return '<p style="color: red;">Greška pri kreiranju QR koda.</p>';
        }
        
        // Generiši URL
        $url = $instance->generate_survey_url($type, $session_id, $lang);
        
        // Generiši QR kod
        $qr_url = $instance->generate_qr_image_url($url, $size);
        
        ob_start();
        ?>
        <div class="indas-qr-display">
            <div class="indas-qr-image">
                <img src="<?php echo esc_url($qr_url); ?>" alt="QR kod za <?php echo $type; ?> upitnik" />
            </div>
            <div class="indas-qr-info">
                <p><strong>Tip:</strong> <?php echo $type === 'personal' ? 'Lični podaci' : 'Feedback'; ?></p>
                <p><strong>Jezik:</strong> <?php echo strtoupper($lang); ?></p>
                <p><strong>Session ID:</strong> <code><?php echo esc_html($session_id); ?></code></p>
                <p><strong>URL:</strong> <a href="<?php echo esc_url($url); ?>" target="_blank"><?php echo esc_url($url); ?></a></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Generiši QR kodove za admin
     */
    public function generate_admin_qr($type, $lang, $count = 1) {
        $qr_codes = array();
        
        for ($i = 0; $i < $count; $i++) {
            // Kreiraj session
            $session_id = $this->db->create_session($type, $lang);
            
            if (!$session_id) {
                continue;
            }
            
            // Generiši URL
            $url = $this->generate_survey_url($type, $session_id, $lang);
            
            // Generiši QR kod
            $qr_url = $this->generate_qr_image_url($url, 200);
            
            $qr_codes[] = array(
                'session_id' => $session_id,
                'url' => $url,
                'qr_image_url' => $qr_url,
                'printable_url' => $this->generate_qr_image_url($url, 300) // Veći za štampanje
            );
        }
        
        return array(
            'qr_codes' => $qr_codes,
            'html' => $this->render_admin_qr_display($qr_codes, $type, $lang)
        );
    }
    
    /**
     * Generiši URL za upitnik
     */
    private function generate_survey_url($type, $session_id, $lang) {
        $page_slug = $type === 'personal' ? 'indas-personal-survey' : 'indas-feedback-survey';
        
        $base_url = get_permalink(get_page_by_path($page_slug));
        
        return add_query_arg(array(
            'session_id' => $session_id,
            'lang' => $lang
        ), $base_url);
    }
    
    /**
     * Generiši URL za QR sliku koristeći Google Charts API
     */
    private function generate_qr_image_url($data, $size = 200) {
        $base_url = 'https://chart.googleapis.com/chart';
        
        $params = array(
            'chs' => $size . 'x' . $size,
            'cht' => 'qr',
            'chl' => urlencode($data),
            'choe' => 'UTF-8',
            'chld' => 'M|0' // Error correction level M, margin 0
        );
        
        return $base_url . '?' . http_build_query($params);
    }
    
    /**
     * Alternativni QR generator koristeći QR Server API
     */
    private function generate_qr_image_url_alternative($data, $size = 200) {
        $base_url = 'https://api.qrserver.com/v1/create-qr-code/';
        
        $params = array(
            'size' => $size . 'x' . $size,
            'data' => $data,
            'format' => 'png',
            'ecc' => 'M'
        );
        
        return $base_url . '?' . http_build_query($params);
    }
    
    /**
     * Renderuj QR kodove za admin display
     */
    private function render_admin_qr_display($qr_codes, $type, $lang) {
        if (empty($qr_codes)) {
            return '<p style="color: red;">Nije moguće generisati QR kodove.</p>';
        }
        
        $type_label = $type === 'personal' ? 'Lični podaci' : 'Feedback';
        $lang_label = strtoupper($lang);
        
        ob_start();
        ?>
        <div class="indas-qr-admin-display">
            <h3><?php echo $type_label; ?> upitnik - <?php echo $lang_label; ?></h3>
            <p>Ukupno <?php echo count($qr_codes); ?> QR kod(ova) generisano.</p>
            
            <div class="indas-qr-actions">
                <button class="button" onclick="downloadAllQR('<?php echo $type; ?>', '<?php echo $lang; ?>')">
                    <span class="dashicons dashicons-download"></span>
                    Preuzmi sve QR kodove (ZIP)
                </button>
                <button class="button" onclick="printAllQR('<?php echo $type; ?>', '<?php echo $lang; ?>')">
                    <span class="dashicons dashicons-printer"></span>
                    Štampaj sve QR kodove
                </button>
            </div>
            
            <div class="indas-qr-grid">
                <?php foreach ($qr_codes as $index => $qr): ?>
                <div class="indas-qr-item">
                    <div class="indas-qr-header">
                        <strong>QR #<?php echo $index + 1; ?></strong>
                        <?php if ($type === 'feedback'): ?>
                            <span class="indas-qr-single-use">Jednokratna upoteba</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="indas-qr-image">
                        <img src="<?php echo esc_url($qr['qr_image_url']); ?>" alt="QR kod #<?php echo $index + 1; ?>" />
                    </div>
                    
                    <div class="indas-qr-details">
                        <p><strong>Session ID:</strong><br>
                           <code class="indas-session-id"><?php echo esc_html($qr['session_id']); ?></code>
                        </p>
                        
                        <div class="indas-qr-actions-single">
                            <a href="<?php echo esc_url($qr['printable_url']); ?>" target="_blank" class="button button-small">
                                Preuzmi PNG
                            </a>
                            <button class="button button-small" onclick="copyToClipboard('<?php echo esc_js($qr['url']); ?>')">
                                Kopiraj URL
                            </button>
                            <button class="button button-small" onclick="testQR('<?php echo esc_js($qr['url']); ?>')">
                                Testiraj
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Printable verzija (skrivena) -->
            <div id="printable-qr-<?php echo $type; ?>-<?php echo $lang; ?>" class="indas-printable-qr" style="display: none;">
                <style>
                @media print {
                    .indas-printable-qr { display: block !important; }
                    .wrap, .indas-qr-admin-display { display: none !important; }
                    .indas-print-page { page-break-after: always; }
                    .indas-print-qr { text-align: center; margin: 2cm; }
                }
                </style>
                
                <?php foreach ($qr_codes as $index => $qr): ?>
                <div class="indas-print-page">
                    <div class="indas-print-qr">
                        <h2>INDAS <?php echo $type_label; ?> Upitnik</h2>
                        <p>Skenirajte QR kod pomoću vašeg telefona</p>
                        <img src="<?php echo esc_url($qr['printable_url']); ?>" style="width: 300px; height: 300px;" />
                        <p><strong>QR #<?php echo $index + 1; ?></strong></p>
                        <?php if ($type === 'feedback'): ?>
                            <p><em>Ovaj QR kod može biti korišćen samo jednom</em></p>
                        <?php endif; ?>
                        <p><small>Session ID: <?php echo esc_html($qr['session_id']); ?></small></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('URL kopiran u clipboard!');
            }, function(err) {
                console.error('Greška pri kopiranju: ', err);
                // Fallback metoda
                const textArea = document.createElement("textarea");
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    alert('URL kopiran u clipboard!');
                } catch (err) {
                    alert('Greška pri kopiranju URL-a');
                }
                document.body.removeChild(textArea);
            });
        }
        
        function testQR(url) {
            window.open(url, '_blank');
        }
        
        function printAllQR(type, lang) {
            const printableDiv = document.getElementById('printable-qr-' + type + '-' + lang);
            const originalDisplay = printableDiv.style.display;
            printableDiv.style.display = 'block';
            window.print();
            printableDiv.style.display = originalDisplay;
        }
        
        function downloadAllQR(type, lang) {
            // Ova funkcija bi trebalo da pozove backend da generiše ZIP fajl
            alert('Download funkcionalnost će biti implementirana u sledećoj verziji');
        }
        </script>
        
        <style>
        .indas-qr-admin-display {
            margin-top: 20px;
        }
        
        .indas-qr-actions {
            margin: 20px 0;
        }
        
        .indas-qr-actions .button {
            margin-right: 10px;
        }
        
        .indas-qr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .indas-qr-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .indas-qr-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .indas-qr-single-use {
            background: #ff6b6b;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            text-transform: uppercase;
        }
        
        .indas-qr-image {
            text-align: center;
            margin: 15px 0;
        }
        
        .indas-qr-image img {
            max-width: 100%;
            height: auto;
        }
        
        .indas-session-id {
            font-size: 10px;
            word-break: break-all;
            background: #f5f5f5;
            padding: 4px;
            border-radius: 3px;
            display: block;
        }
        
        .indas-qr-actions-single {
            margin-top: 10px;
        }
        
        .indas-qr-actions-single .button {
            font-size: 11px;
            height: auto;
            padding: 4px 8px;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Validiraj QR session
     */
    public function validate_qr_session($session_id, $survey_type) {
        return $this->db->is_session_valid($session_id, $survey_type);
    }
    
    /**
     * Generiši bulk QR kodove za štampanje
     */
    public function generate_bulk_qr($type, $lang, $count, $format = 'pdf') {
        // Ova funkcija će biti proširena za generisanje PDF-a ili ZIP fajlova
        $qr_codes = array();
        
        for ($i = 0; $i < $count; $i++) {
            $session_id = $this->db->create_session($type, $lang);
            
            if ($session_id) {
                $url = $this->generate_survey_url($type, $session_id, $lang);
                $qr_codes[] = array(
                    'session_id' => $session_id,
                    'url' => $url,
                    'qr_data' => $url
                );
            }
        }
        
        return $qr_codes;
    }
    
    /**
     * Cleanup nekorišćenih QR session-a
     */
    public function cleanup_unused_sessions($older_than_hours = 48) {
        global $wpdb;
        
        $cutoff_time = date('Y-m-d H:i:s', time() - ($older_than_hours * 3600));
        
        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM " . INDAS_Survey_Database::$sessions_table . " 
                 WHERE status = 'active' AND kreiran < %s",
                $cutoff_time
            )
        );
    }
}