<?php
// includes/admin.php

if (!defined('ABSPATH')) {
    exit;
}

class Survey_Admin
{

    public static function init()
    {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_init', array(__CLASS__, 'handle_csv_export'));
    }

    public static function add_admin_menu()
    {
        add_menu_page(
            'Upitnici',
            'Upitnici',
            'manage_options',
            'survey-dashboard',
            array(__CLASS__, 'dashboard_page'),
            'dashicons-feedback',
            30
        );

        add_submenu_page(
            'survey-dashboard',
            'Polaznici',
            'Polaznici',
            'manage_options',
            'survey-registrations',
            array(__CLASS__, 'registrations_page')
        );

        add_submenu_page(
            'survey-dashboard',
            'Upitnici',
            'Upitnici',
            'manage_options',
            'survey-feedback',
            array(__CLASS__, 'feedback_page')
        );

        add_submenu_page(
            'survey-dashboard',
            'QR Kodovi',
            'QR Kodovi',
            'manage_options',
            'survey-qr-codes',
            array(__CLASS__, 'qr_codes_page')
        );
    }

    public static function dashboard_page()
    {
        $participants_count = Survey_Database::get_participants_count();
        $feedback_count = Survey_Database::get_feedback_count();
?>
        <div class="wrap">
            <h1>Pregled upitnika</h1>

            <div class="dashboard-widgets-wrap">
                <div class="metabox-holder">
                    <div class="postbox">
                        <h2>Statistike</h2>
                        <div class="inside">
                            <p><strong>Ukupno prijava:</strong> <?php echo $participants_count; ?></p>
                            <p><strong>Ukupno upitnika:</strong> <?php echo $feedback_count; ?></p>
                        </div>
                    </div>

                    <div class="postbox">
                        <h2>Brzi linkovi</h2>
                        <div class="inside">
                            <p><a href="?page=survey-registrations" class="button">Pregled polaznika</a></p>
                            <p><a href="?page=survey-feedback" class="button">Pregled upitnika</a></p>
                            <p><a href="?page=survey-qr-codes" class="button">Generiši QR kodove</a></p>
                        </div>
                    </div>

                    <div class="postbox">
                        <h2>Shortcode-ovi</h2>
                        <div class="inside">
                            <p><strong>Prijava polaznika (srpski):</strong> <code>[registration_form lang="sr"]</code></p>
                            <p><strong>Prijava polaznika (engleski):</strong> <code>[registration_form lang="en"]</code></p>
                            <p><strong>Upitnik standard (srpski):</strong> <code>[feedback_form lang="sr"]</code></p>
                            <p><strong>Upitnik standard (engleski):</strong> <code>[feedback_form lang="en"]</code></p>
                            <p><strong>Upitnik in-house (srpski):</strong> <code>[feedback_form_inhouse lang="sr"]</code></p>
                            <p><strong>Upitnik in-house (engleski):</strong> <code>[feedback_form_inhouse lang="en"]</code></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }

    public static function registrations_page()
    {
        $page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        $participants = Survey_Database::get_participants($per_page, $offset);
        $total = Survey_Database::get_participants_count();
        $total_pages = ceil($total / $per_page);
    ?>
        <div class="wrap">
            <h1>Polaznici kursa
                <a href="?page=survey-registrations&export=csv" class="button button-secondary">Izvezi CSV</a>
            </h1>

            <?php if ($participants): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ime i prezime</th>
                            <th>Kompanija</th>
                            <th>Adresa</th>
                            <th>Radno mesto</th>
                            <th>Telefon</th>
                            <th>Mobilni</th>
                            <th>Email</th>
                            <th>Jezik</th>
                            <th>Datum unosa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($participants as $participant): ?>
                            <tr>
                                <td><?php echo $participant->id; ?></td>
                                <td><?php echo esc_html($participant->participant_name); ?></td>
                                <td><?php echo esc_html($participant->company); ?></td>
                                <td><?php echo esc_html($participant->address); ?></td>
                                <td><?php echo esc_html($participant->position); ?></td>
                                <td><?php echo esc_html($participant->phone); ?></td>
                                <td><?php echo esc_html($participant->mobile); ?></td>
                                <td><?php echo esc_html($participant->email); ?></td>
                                <td><?php echo strtoupper($participant->language); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($participant->submitted_at)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php
                // Paginacija
                if ($total_pages > 1) {
                    echo '<div class="tablenav"><div class="tablenav-pages">';
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $page
                    ));
                    echo '</div></div>';
                }
                ?>

            <?php else: ?>
                <p>Nema prijavljenih polaznika.</p>
            <?php endif; ?>
        </div>
    <?php
    }

    public static function feedback_page()
    {
        $page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        $feedback = Survey_Database::get_feedback($per_page, $offset);
        $total = Survey_Database::get_feedback_count();
        $total_pages = ceil($total / $per_page);
    ?>
        <div class="wrap">
            <h1>Upitnici o kursu
                <a href="?page=survey-feedback&export=csv" class="button button-secondary">Izvezi CSV</a>
            </h1>

            <?php if ($feedback): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tip</th>
                            <th>Očekivanja</th>
                            <th>Nivo</th>
                            <th>Predavanja</th>
                            <th>Predavač</th>
                            <th>Primenjivost</th>
                            <th>Literatura</th>
                            <th>Prostorije</th>
                            <th>Ishrana</th>
                            <th>Saradnja</th>
                            <th>Jezik</th>
                            <th>Datum</th>
                            <th>Akcije</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feedback as $fb): ?>
                            <tr>
                                <td><?php echo $fb->id; ?></td>
                                <td>
                                    <span class="feedback-type <?php echo $fb->feedback_type; ?>">
                                        <?php echo $fb->feedback_type === 'inhouse' ? 'In-house' : 'Standard'; ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($fb->expectations_met); ?></td>
                                <td><?php echo $fb->expectations_level; ?>/5</td>
                                <td><?php echo $fb->lecture_quality; ?>/5</td>
                                <td><?php echo $fb->lecturer_quality; ?>/5</td>
                                <td><?php echo $fb->practical_application; ?>/5</td>
                                <td><?php echo $fb->literature; ?>/5</td>
                                <td><?php echo $fb->premises ? $fb->premises . '/5' : '-'; ?></td>
                                <td><?php echo $fb->food ? $fb->food . '/5' : '-'; ?></td>
                                <td><?php echo $fb->cooperation ? $fb->cooperation . '/5' : '-'; ?></td>
                                <td><?php echo strtoupper($fb->language); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($fb->submitted_at)); ?></td>
                                <td>
                                    <button class="button button-small view-details" data-id="<?php echo $fb->id; ?>">Detalji</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php
                // Paginacija
                if ($total_pages > 1) {
                    echo '<div class="tablenav"><div class="tablenav-pages">';
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $page
                    ));
                    echo '</div></div>';
                }
                ?>

            <?php else: ?>
                <p>Nema popunjenih upitnika.</p>
            <?php endif; ?>

            <!-- Modal za detalje -->
            <div id="feedback-modal" style="display: none;">
                <div class="feedback-modal-content">
                    <span class="close">&times;</span>
                    <div id="feedback-details"></div>
                </div>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                $('.view-details').on('click', function() {
                    var id = $(this).data('id');
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'get_feedback_details',
                            id: id
                        },
                        success: function(response) {
                            $('#feedback-details').html(response);
                            $('#feedback-modal').show();
                        }
                    });
                });

                $('.close').on('click', function() {
                    $('#feedback-modal').hide();
                });
            });
        </script>

        <style>
            #feedback-modal {
                position: fixed;
                z-index: 1000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
            }

            .feedback-modal-content {
                background-color: #fff;
                margin: 5% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 80%;
                max-width: 600px;
                position: relative;
            }

            .close {
                position: absolute;
                right: 10px;
                top: 10px;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
            }

            .feedback-type {
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: bold;
            }

            .feedback-type.standard {
                background: #e7f3ff;
                color: #0073aa;
            }

            .feedback-type.inhouse {
                background: #fff2e7;
                color: #d54e21;
            }
        </style>
    <?php
    }

    public static function qr_codes_page()
    {
        $base_url = home_url();
    ?>
        <div class="wrap">
            <h1>QR Kodovi za upitnik</h1>

            <div class="qr-codes-container">
                <div class="qr-code-section">
                    <h3>Prijava polaznika - Srpski</h3>
                    <p><strong>URL:</strong> <?php echo $base_url; ?>/prijava-sr</p>
                    <div class="qr-code">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($base_url . '/prijava-sr'); ?>" alt="QR kod prijava SR">
                    </div>
                    <p><a href="https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=<?php echo urlencode($base_url . '/prijava-sr'); ?>" target="_blank" class="button">Preuzmi veliki QR (500x500)</a></p>
                </div>

                <div class="qr-code-section">
                    <h3>Prijava polaznika - Engleski</h3>
                    <p><strong>URL:</strong> <?php echo $base_url; ?>/prijava-en</p>
                    <div class="qr-code">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($base_url . '/prijava-en'); ?>" alt="QR kod prijava EN">
                    </div>
                    <p><a href="https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=<?php echo urlencode($base_url . '/prijava-en'); ?>" target="_blank" class="button">Preuzmi veliki QR (500x500)</a></p>
                </div>

                <div class="qr-code-section">
                    <h3>Upitnik standard - Srpski</h3>
                    <p><strong>URL:</strong> <?php echo $base_url; ?>/upitnik-sr</p>
                    <div class="qr-code">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($base_url . '/upitnik-sr'); ?>" alt="QR kod upitnik SR">
                    </div>
                    <p><a href="https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=<?php echo urlencode($base_url . '/upitnik-sr'); ?>" target="_blank" class="button">Preuzmi veliki QR (500x500)</a></p>
                </div>

                <div class="qr-code-section">
                    <h3>Upitnik standard - Engleski</h3>
                    <p><strong>URL:</strong> <?php echo $base_url; ?>/upitnik-en</p>
                    <div class="qr-code">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($base_url . '/upitnik-en'); ?>" alt="QR kod upitnik EN">
                    </div>
                    <p><a href="https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=<?php echo urlencode($base_url . '/upitnik-en'); ?>" target="_blank" class="button">Preuzmi veliki QR (500x500)</a></p>
                </div>

                <div class="qr-code-section">
                    <h3>Upitnik in-house - Srpski</h3>
                    <p><strong>URL:</strong> <?php echo $base_url; ?>/upitnik-inhouse-sr</p>
                    <div class="qr-code">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($base_url . '/upitnik-inhouse-sr'); ?>" alt="QR kod upitnik in-house SR">
                    </div>
                    <p><a href="https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=<?php echo urlencode($base_url . '/upitnik-inhouse-sr'); ?>" target="_blank" class="button">Preuzmi veliki QR (500x500)</a></p>
                </div>

                <div class="qr-code-section">
                    <h3>Upitnik in-house - Engleski</h3>
                    <p><strong>URL:</strong> <?php echo $base_url; ?>/upitnik-inhouse-en</p>
                    <div class="qr-code">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($base_url . '/upitnik-inhouse-en'); ?>" alt="QR kod upitnik in-house EN">
                    </div>
                    <p><a href="https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=<?php echo urlencode($base_url . '/upitnik-inhouse-en'); ?>" target="_blank" class="button">Preuzmi veliki QR (500x500)</a></p>
                </div>
            </div>

            <div class="instructions">
                <h3>Uputstva:</h3>
                <ol>
                    <li>Kreiraj stranice sa sledećim slug-ovima:
                        <ul>
                            <li><code>/prijava-sr</code> - dodaj shortcode <code>[registration_form lang="sr"]</code></li>
                            <li><code>/prijava-en</code> - dodaj shortcode <code>[registration_form lang="en"]</code></li>
                            <li><code>/upitnik-sr</code> - dodaj shortcode <code>[feedback_form lang="sr"]</code></li>
                            <li><code>/upitnik-en</code> - dodaj shortcode <code>[feedback_form lang="en"]</code></li>
                            <li><code>/upitnik-inhouse-sr</code> - dodaj shortcode <code>[feedback_form_inhouse lang="sr"]</code></li>
                            <li><code>/upitnik-inhouse-en</code> - dodaj shortcode <code>[feedback_form_inhouse lang="en"]</code></li>
                        </ul>
                    </li>
                    <li>Koristi "Preuzmi veliki QR" dugmad za QR kodove koji se mogu štampati</li>
                    <li>QR kodovi mogu da se odštampaju i postave na lokaciji kursa</li>
                </ol>
            </div>
        </div>

        <style>
            .qr-codes-container {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin: 20px 0;
            }

            .qr-code-section {
                border: 1px solid #ccc;
                padding: 20px;
                text-align: center;
                background: #f9f9f9;
            }

            .qr-code img {
                border: 1px solid #ddd;
                margin: 10px 0;
            }

            .instructions {
                margin-top: 30px;
                padding: 20px;
                background: #fff;
                border: 1px solid #ccc;
            }
        </style>
<?php
    }

    public static function handle_csv_export()
    {
        if (!isset($_GET['export']) || $_GET['export'] !== 'csv') {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die('Nemate dozvolu za ovu akciju');
        }

        if (isset($_GET['page'])) {
            if ($_GET['page'] === 'survey-registrations') {
                self::export_registrations_csv();
            } elseif ($_GET['page'] === 'survey-feedback') {
                self::export_feedback_csv();
            }
        }
    }

    private static function export_registrations_csv()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'course_participants';
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY submitted_at DESC");

        $filename = 'registracije_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');

        // Header
        fputcsv($output, array(
            'ID',
            'Ime i prezime',
            'Kompanija',
            'Adresa',
            'Radno mesto',
            'Telefon',
            'Mobilni',
            'Email',
            'Jezik',
            'Datum unosa'
        ));

        // Data
        foreach ($results as $row) {
            fputcsv($output, array(
                $row->id,
                $row->participant_name,
                $row->company,
                $row->address,
                $row->position,
                $row->phone,
                $row->mobile,
                $row->email,
                $row->language,
                $row->submitted_at
            ));
        }

        fclose($output);
        exit;
    }

    private static function export_feedback_csv()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'course_feedback';
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY submitted_at DESC");

        $filename = 'feedback_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');

        // Header
        fputcsv($output, array(
            'ID',
            'Tip',
            'Očekivanja ispunjena',
            'Nivo očekivanja',
            'Kvalitet predavanja',
            'Kvalitet predavača',
            'Primenjivost',
            'Literatura',
            'Prostorije',
            'Ishrana',
            'Saradnja',
            'Napredni STEP7',
            'Drugi kursevi',
            'Poboljšanja',
            'Dodatni komentari',
            'Jezik',
            'Datum'
        ));

        // Data
        foreach ($results as $row) {
            fputcsv($output, array(
                $row->id,
                $row->feedback_type,
                $row->expectations_met,
                $row->expectations_level,
                $row->lecture_quality,
                $row->lecturer_quality,
                $row->practical_application,
                $row->literature,
                $row->premises,
                $row->food,
                $row->cooperation,
                $row->advanced_step7,
                $row->other_courses,
                $row->improvements,
                $row->additional_comments,
                $row->language,
                $row->submitted_at
            ));
        }

        fclose($output);
        exit;
    }
}
