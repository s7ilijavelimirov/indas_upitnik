<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin interface klasa
 */
class INDAS_Survey_Admin
{

    private static $instance = null;
    private $db;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->db = INDAS_Survey_Database::get_instance();

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_admin_actions'));

        // AJAX hook-ovi za admin
        add_action('wp_ajax_indas_export_csv', array($this, 'export_csv'));
        add_action('wp_ajax_indas_generate_qr', array($this, 'generate_qr_admin'));
        add_action('wp_ajax_indas_delete_survey', array($this, 'delete_survey'));
    }

    /**
     * Dodaj admin meni
     */
    public function add_admin_menu()
    {
        $capability = 'manage_options';

        // Glavni meni
        add_menu_page(
            'INDAS Upitnici',
            'INDAS Upitnici',
            $capability,
            'indas-survey',
            array($this, 'admin_dashboard'),
            'dashicons-feedback',
            30
        );

        // Dashboard submeni
        add_submenu_page(
            'indas-survey',
            'Pregled svih upitnika',
            'Dashboard',
            $capability,
            'indas-survey',
            array($this, 'admin_dashboard')
        );

        // Lični podaci submeni
        add_submenu_page(
            'indas-survey',
            'Lični podaci polaznika',
            'Lični podaci',
            $capability,
            'indas-survey-personal',
            array($this, 'admin_personal_surveys')
        );

        // Feedback submeni
        add_submenu_page(
            'indas-survey',
            'Feedback upitnici',
            'Feedback',
            $capability,
            'indas-survey-feedback',
            array($this, 'admin_feedback_surveys')
        );

        // QR kodovi submeni
        add_submenu_page(
            'indas-survey',
            'QR kodovi',
            'QR kodovi',
            $capability,
            'indas-survey-qr',
            array($this, 'admin_qr_codes')
        );

        // Podesavanja submeni
        add_submenu_page(
            'indas-survey',
            'Podešavanja',
            'Podešavanja',
            $capability,
            'indas-survey-settings',
            array($this, 'admin_settings')
        );
    }

    /**
     * Admin dashboard glavna stranica
     */
    public function admin_dashboard()
    {
        $personal_count = $this->db->count_personal_surveys();
        $feedback_count = $this->db->count_feedback_surveys();

        // Preuzmi poslednje upitnike
        $recent_personal = $this->db->get_personal_surveys(5, 0);
        $recent_feedback = $this->db->get_feedback_surveys(5, 0);

?>
        <div class="wrap">
            <h1>INDAS Survey Dashboard</h1>

            <!-- Statistike -->
            <div class="indas-stats-grid">
                <div class="indas-stat-card">
                    <div class="indas-stat-number"><?php echo $personal_count; ?></div>
                    <div class="indas-stat-label">Popunjeni lični upitnici</div>
                    <a href="<?php echo admin_url('admin.php?page=indas-survey-personal'); ?>" class="indas-stat-link">Prikaži sve →</a>
                </div>

                <div class="indas-stat-card">
                    <div class="indas-stat-number"><?php echo $feedback_count; ?></div>
                    <div class="indas-stat-label">Popunjeni feedback upitnici</div>
                    <a href="<?php echo admin_url('admin.php?page=indas-survey-feedback'); ?>" class="indas-stat-link">Prikaži sve →</a>
                </div>

                <div class="indas-stat-card">
                    <div class="indas-stat-number"><?php echo round(($feedback_count / max($personal_count, 1)) * 100); ?>%</div>
                    <div class="indas-stat-label">Stopa odziva (feedback)</div>
                </div>
            </div>

            <!-- Brze akcije -->
            <div class="indas-quick-actions">
                <h2>Brze akcije</h2>
                <div class="indas-actions-grid">
                    <a href="<?php echo admin_url('admin.php?page=indas-survey-qr'); ?>" class="indas-action-btn indas-btn-primary">
                        <span class="dashicons dashicons-grid-view"></span>
                        Generiši QR kodove
                    </a>

                    <button class="indas-action-btn indas-btn-secondary" onclick="exportAllData()">
                        <span class="dashicons dashicons-download"></span>
                        Izvezi sve podatke (CSV)
                    </button>

                    <a href="<?php echo admin_url('admin.php?page=indas-survey-settings'); ?>" class="indas-action-btn indas-btn-outline">
                        <span class="dashicons dashicons-admin-settings"></span>
                        Podešavanja
                    </a>
                </div>
            </div>

            <!-- Poslednji upitnici -->
            <div class="indas-recent-grid">
                <div class="indas-recent-section">
                    <h3>Poslednji lični upitnici</h3>
                    <?php if (!empty($recent_personal)): ?>
                        <table class="indas-table">
                            <thead>
                                <tr>
                                    <th>Ime i prezime</th>
                                    <th>Firma</th>
                                    <th>Email</th>
                                    <th>Datum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_personal as $survey): ?>
                                    <tr>
                                        <td><?php echo esc_html($survey->ime . ' ' . $survey->prezime); ?></td>
                                        <td><?php echo esc_html($survey->naziv_firme); ?></td>
                                        <td><?php echo esc_html($survey->email); ?></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($survey->datum_kreiranja)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Nema popunjenih upitnika.</p>
                    <?php endif; ?>
                </div>

                <div class="indas-recent-section">
                    <h3>Poslednji feedback upitnici</h3>
                    <?php if (!empty($recent_feedback)): ?>
                        <table class="indas-table">
                            <thead>
                                <tr>
                                    <th>Opšta ocena</th>
                                    <th>Preporuka</th>
                                    <th>Datum</th>
                                    <th>Akcije</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_feedback as $survey): ?>
                                    <tr>
                                        <td>
                                            <div class="indas-rating-stars">
                                                <?php echo $this->render_stars($survey->opsta_ocena); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="indas-rating-stars">
                                                <?php echo $this->render_stars($survey->preporuka_kursa); ?>
                                            </div>
                                        </td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($survey->datum_kreiranja)); ?></td>
                                        <td>
                                            <button class="indas-btn-small" onclick="viewFeedback(<?php echo $survey->id; ?>)">Prikaži</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Nema popunjenih feedback upitnika.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script>
            function exportAllData() {
                if (confirm('Da li želite da izvezete sve podatke u CSV format?')) {
                    window.location.href = ajaxurl + '?action=indas_export_csv&type=all&_wpnonce=' + '<?php echo wp_create_nonce('indas_export_nonce'); ?>';
                }
            }

            function viewFeedback(id) {
                window.location.href = '<?php echo admin_url('admin.php?page=indas-survey-feedback&view='); ?>' + id;
            }
        </script>
    <?php
    }

    /**
     * Admin stranica za lične podatke
     */
    public function admin_personal_surveys()
    {
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        $surveys = $this->db->get_personal_surveys($per_page, $offset);
        $total = $this->db->count_personal_surveys();
        $total_pages = ceil($total / $per_page);

    ?>
        <div class="wrap">
            <h1>Lični podaci polaznika</h1>

            <div class="indas-admin-header">
                <div class="indas-admin-actions">
                    <button class="button button-primary" onclick="exportPersonalData()">
                        <span class="dashicons dashicons-download"></span>
                        Izvezi CSV
                    </button>
                </div>
            </div>

            <?php if (!empty($surveys)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ime</th>
                            <th>Prezime</th>
                            <th>Firma</th>
                            <th>Email</th>
                            <th>Telefon</th>
                            <th>Jezik</th>
                            <th>Datum</th>
                            <th>Akcije</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($surveys as $survey): ?>
                            <tr>
                                <td><?php echo $survey->id; ?></td>
                                <td><?php echo esc_html($survey->ime); ?></td>
                                <td><?php echo esc_html($survey->prezime); ?></td>
                                <td><?php echo esc_html($survey->naziv_firme); ?></td>
                                <td><a href="mailto:<?php echo esc_attr($survey->email); ?>"><?php echo esc_html($survey->email); ?></a></td>
                                <td><?php echo esc_html($survey->telefon ?: '-'); ?></td>
                                <td><?php echo strtoupper($survey->jezik); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($survey->datum_kreiranja)); ?></td>
                                <td>
                                    <button class="button button-small" onclick="deleteSurvey('personal', <?php echo $survey->id; ?>)">
                                        Obriši
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Paginacija -->
                <?php if ($total_pages > 1): ?>
                    <div class="tablenav">
                        <div class="tablenav-pages">
                            <?php
                            echo paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total' => $total_pages,
                                'current' => $page
                            ));
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="notice notice-info">
                    <p>Nema popunjenih upitnika sa ličnim podacima.</p>
                </div>
            <?php endif; ?>
        </div>

        <script>
            function exportPersonalData() {
                window.location.href = ajaxurl + '?action=indas_export_csv&type=personal&_wpnonce=' + '<?php echo wp_create_nonce('indas_export_nonce'); ?>';
            }

            function deleteSurvey(type, id) {
                if (confirm('Da li ste sigurni da želite da obrišete ovaj upitnik?')) {
                    jQuery.post(ajaxurl, {
                        action: 'indas_delete_survey',
                        type: type,
                        id: id,
                        _wpnonce: '<?php echo wp_create_nonce('indas_delete_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Greška pri brisanju upitnika.');
                        }
                    });
                }
            }
        </script>
    <?php
    }

    /**
     * Admin stranica za feedback upitnike
     */
    public function admin_feedback_surveys()
    {
        // Ako je view parametar poslat, prikaži detaljan view
        if (isset($_GET['view'])) {
            $this->admin_feedback_detail(intval($_GET['view']));
            return;
        }

        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        $surveys = $this->db->get_feedback_surveys($per_page, $offset);
        $total = $this->db->count_feedback_surveys();
        $total_pages = ceil($total / $per_page);

    ?>
        <div class="wrap">
            <h1>Feedback upitnici</h1>

            <div class="indas-admin-header">
                <div class="indas-admin-actions">
                    <button class="button button-primary" onclick="exportFeedbackData()">
                        <span class="dashicons dashicons-download"></span>
                        Izvezi CSV
                    </button>
                </div>
            </div>

            <?php if (!empty($surveys)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Opšta ocena</th>
                            <th>Preporuka</th>
                            <th>Predavač</th>
                            <th>Sadržaj</th>
                            <th>Objekat</th>
                            <th>Hrana</th>
                            <th>Organizacija</th>
                            <th>Datum</th>
                            <th>Akcije</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($surveys as $survey): ?>
                            <tr>
                                <td><?php echo $survey->id; ?></td>
                                <td><?php echo $this->render_stars($survey->opsta_ocena); ?></td>
                                <td><?php echo $this->render_stars($survey->preporuka_kursa); ?></td>
                                <td><?php echo $this->render_stars($survey->ocena_predavaca); ?></td>
                                <td><?php echo $this->render_stars($survey->ocena_sadrzaja); ?></td>
                                <td><?php echo $this->render_stars($survey->ocena_objekta); ?></td>
                                <td><?php echo $this->render_stars($survey->ocena_hrane); ?></td>
                                <td><?php echo $this->render_stars($survey->ocena_organizacije); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($survey->datum_kreiranja)); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=indas-survey-feedback&view=' . $survey->id); ?>" class="button button-small">
                                        Prikaži
                                    </a>
                                    <button class="button button-small" onclick="deleteSurvey('feedback', <?php echo $survey->id; ?>)">
                                        Obriši
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Paginacija -->
                <?php if ($total_pages > 1): ?>
                    <div class="tablenav">
                        <div class="tablenav-pages">
                            <?php
                            echo paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total' => $total_pages,
                                'current' => $page
                            ));
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="notice notice-info">
                    <p>Nema popunjenih feedback upitnika.</p>
                </div>
            <?php endif; ?>
        </div>

        <script>
            function exportFeedbackData() {
                window.location.href = ajaxurl + '?action=indas_export_csv&type=feedback&_wpnonce=' + '<?php echo wp_create_nonce('indas_export_nonce'); ?>';
            }

            function deleteSurvey(type, id) {
                if (confirm('Da li ste sigurni da želite da obrišete ovaj upitnik?')) {
                    jQuery.post(ajaxurl, {
                        action: 'indas_delete_survey',
                        type: type,
                        id: id,
                        _wpnonce: '<?php echo wp_create_nonce('indas_delete_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Greška pri brisanju upitnika.');
                        }
                    });
                }
            }
        </script>
    <?php
    }

    /**
     * QR kodovi admin stranica
     */
    public function admin_qr_codes()
    {
    ?>
        <div class="wrap">
            <h1>QR kodovi za upitnike</h1>

            <div class="indas-qr-generator">
                <div class="indas-qr-section">
                    <h2>Lični podaci upitnik</h2>
                    <p>QR kod koji vodi na upitnik za unos ličnih podataka polaznika.</p>

                    <div class="indas-qr-options">
                        <label>
                            <input type="radio" name="personal_lang" value="sr" checked> Srpski
                        </label>
                        <label>
                            <input type="radio" name="personal_lang" value="en"> Engleski
                        </label>
                    </div>

                    <button class="button button-primary" onclick="generateQR('personal')">
                        Generiši QR kod
                    </button>

                    <div id="personal-qr-result" class="indas-qr-result"></div>
                </div>

                <div class="indas-qr-section">
                    <h2>Feedback upitnik</h2>
                    <p>QR kod koji vodi na anonimni feedback upitnik.</p>

                    <div class="indas-qr-options">
                        <label>
                            <input type="radio" name="feedback_lang" value="sr" checked> Srpski
                        </label>
                        <label>
                            <input type="radio" name="feedback_lang" value="en"> Engleski
                        </label>

                        <div class="indas-qr-count">
                            <label>Broj QR kodova:
                                <input type="number" id="feedback_count" value="10" min="1" max="100">
                            </label>
                            <small>Svaki QR kod može biti korišćen samo jednom</small>
                        </div>
                    </div>

                    <button class="button button-primary" onclick="generateQR('feedback')">
                        Generiši QR kodove
                    </button>

                    <div id="feedback-qr-result" class="indas-qr-result"></div>
                </div>
            </div>
        </div>

        <script>
            function generateQR(type) {
                const lang = document.querySelector(`input[name="${type}_lang"]:checked`).value;
                const count = type === 'feedback' ? document.getElementById('feedback_count').value : 1;

                document.getElementById(type + '-qr-result').innerHTML = '<p>Generiše se...</p>';

                jQuery.post(ajaxurl, {
                    action: 'indas_generate_qr',
                    type: type,
                    lang: lang,
                    count: count,
                    _wpnonce: '<?php echo wp_create_nonce('indas_qr_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        document.getElementById(type + '-qr-result').innerHTML = response.data.html;
                    } else {
                        document.getElementById(type + '-qr-result').innerHTML = '<p style="color: red;">Greška: ' + response.data.message + '</p>';
                    }
                });
            }
        </script>
    <?php
    }

    /**
     * Podešavanja admin stranica
     */
    public function admin_settings()
    {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }

        $settings = get_option('indas_survey_settings', array());
    ?>
        <div class="wrap">
            <h1>Podešavanja INDAS upitnika</h1>

            <form method="post" action="">
                <?php wp_nonce_field('indas_settings_save'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">Email za slanje upitnika</th>
                        <td>
                            <input type="email" name="admin_email" value="<?php echo esc_attr($settings['admin_email'] ?? get_admin_email()); ?>" class="regular-text" />
                            <p class="description">Email adresa na koju će stizati kopije popunjenih upitnika</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Automatsko slanje email-a</th>
                        <td>
                            <label>
                                <input type="checkbox" name="send_email_notifications" value="1" <?php checked($settings['send_email_notifications'] ?? true); ?> />
                                Pošalji email notifikaciju kada se upitnik popuni
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Session trajanje (sati)</th>
                        <td>
                            <input type="number" name="session_duration" value="<?php echo esc_attr($settings['session_duration'] ?? 24); ?>" min="1" max="168" />
                            <p class="description">Koliko sati QR kod ostaje aktivan (1-168 sati)</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Tema upitnika</th>
                        <td>
                            <select name="survey_theme">
                                <option value="default" <?php selected($settings['survey_theme'] ?? 'default', 'default'); ?>>Standardna</option>
                                <option value="indas" <?php selected($settings['survey_theme'] ?? 'default', 'indas'); ?>>INDAS brend</option>
                                <option value="minimal" <?php selected($settings['survey_theme'] ?? 'default', 'minimal'); ?>>Minimalna</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
<?php
    }

    /**
     * Renderuj zvezdice za ocenu
     */
    private function render_stars($rating)
    {
        if (empty($rating)) return '-';

        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            $stars .= $i <= $rating ? '★' : '☆';
        }
        return '<span class="indas-stars" title="' . $rating . '/5">' . $stars . '</span>';
    }

    /**
     * Obradi admin akcije
     */
    public function handle_admin_actions()
    {
        // Cleanup expired sessions daily
        if (!wp_next_scheduled('indas_cleanup_sessions')) {
            wp_schedule_event(time(), 'daily', 'indas_cleanup_sessions');
        }
        add_action('indas_cleanup_sessions', array($this->db, 'cleanup_expired_sessions'));
    }

    /**
     * AJAX - Export CSV
     */
    public function export_csv()
    {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'indas_export_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $type = sanitize_text_field($_GET['type']);
        $exporter = new INDAS_Survey_CSV_Export();
        $exporter->export($type);
    }

    /**
     * AJAX - Generiši QR kod
     */
    public function generate_qr_admin()
    {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'indas_qr_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        $type = sanitize_text_field($_POST['type']);
        $lang = sanitize_text_field($_POST['lang']);
        $count = intval($_POST['count']);

        $qr_generator = INDAS_Survey_QR_Generator::get_instance();
        $result = $qr_generator->generate_admin_qr($type, $lang, $count);

        wp_send_json_success($result);
    }

    /**
     * AJAX - Obriši upitnik
     */
    public function delete_survey()
    {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'indas_delete_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        global $wpdb;
        $type = sanitize_text_field($_POST['type']);
        $id = intval($_POST['id']);

        $table = $type === 'personal' ?
            INDAS_Survey_Database::$personal_survey_table :
            INDAS_Survey_Database::$feedback_survey_table;

        $result = $wpdb->delete($table, array('id' => $id), array('%d'));

        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => 'Failed to delete survey'));
        }
    }

    /**
     * Sačuvaj podešavanja
     */
    private function save_settings()
    {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'indas_settings_save')) {
            return;
        }

        $settings = array(
            'admin_email' => sanitize_email($_POST['admin_email']),
            'send_email_notifications' => isset($_POST['send_email_notifications']),
            'session_duration' => intval($_POST['session_duration']),
            'survey_theme' => sanitize_text_field($_POST['survey_theme'])
        );

        update_option('indas_survey_settings', $settings);

        echo '<div class="notice notice-success"><p>Podešavanja su sačuvana!</p></div>';
    }
}
