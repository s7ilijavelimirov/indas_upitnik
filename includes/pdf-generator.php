<?php
if (!defined('ABSPATH')) {
    exit;
}

class Survey_PDF_Generator
{
    public static function generate_qr_pdf($qr_type, $lang)
    {
        $base_url = home_url();

        // Define QR code data
        $qr_configs = array(
            'registration-sr' => array(
                'url' => $base_url . '/prijava-sr',
                'title' => 'Prijava polaznika kursa',
                'subtitle' => 'Skeniraj QR kod za registraciju'
            ),
            'registration-en' => array(
                'url' => $base_url . '/prijava-en',
                'title' => 'Course Registration',
                'subtitle' => 'Scan QR code to register'
            ),
            'feedback-sr' => array(
                'url' => $base_url . '/upitnik-sr',
                'title' => 'Upitnik o kursu',
                'subtitle' => 'Skeniraj QR kod za popunjavanje upitnika'
            ),
            'feedback-en' => array(
                'url' => $base_url . '/upitnik-en',
                'title' => 'Course Questionnaire',
                'subtitle' => 'Scan QR code to fill questionnaire'
            ),
            'feedback-inhouse-sr' => array(
                'url' => $base_url . '/upitnik-inhouse-sr',
                'title' => 'In-house upitnik o kursu',
                'subtitle' => 'Skeniraj QR kod za popunjavanje upitnika'
            ),
            'feedback-inhouse-en' => array(
                'url' => $base_url . '/upitnik-inhouse-en',
                'title' => 'In-house Course Questionnaire',
                'subtitle' => 'Scan QR code to fill questionnaire'
            )
        );

        $config_key = $qr_type . '-' . $lang;

        if (!isset($qr_configs[$config_key])) {
            wp_die('Neispravni parametri');
        }

        $config = $qr_configs[$config_key];
        self::generate_browser_pdf($config, $lang);
    }

    private static function generate_browser_pdf($config, $lang)
    {
        // Set PDF headers
        header('Content-Type: text/html; charset=utf-8');

        $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($config['url']);

        $instructions = $lang === 'sr'
            ? "1. Skeniraj QR kod pomoću telefona<br>2. Otvoriće se stranica sa upitnicima<br>3. Popuni sva obavezna polja<br>4. Klikni 'Pošalji'"
            : "1. Scan QR code with your phone<br>2. The questionnaire page will open<br>3. Fill all required fields<br>4. Click 'Submit'";

?>
        <!DOCTYPE html>
        <html>

        <head>
            <meta charset="UTF-8">
            <title><?php echo esc_html($config['title']); ?></title>
            <style>
                @page {
                    margin: 1.5cm;
                    size: A4 portrait;
                }

                body {
                    font-family: Arial, sans-serif;
                    text-align: center;
                    margin: 0;
                    padding: 20px;
                    line-height: 1.4;
                }

                .header {
                    margin-bottom: 30px;
                }

                .title {
                    font-size: 28px;
                    font-weight: bold;
                    color: #333;
                    margin-bottom: 10px;
                }

                .subtitle {
                    font-size: 16px;
                    color: #666;
                    margin-bottom: 30px;
                }

                .qr-container {
                    margin: 30px 0;
                    padding: 20px;
                    border: 2px solid #ddd;
                    display: inline-block;
                    background: #fff;
                }

                .qr-container img {
                    width: 300px;
                    height: 300px;
                    display: block;
                }

                .url-section {
                    margin: 20px 0;
                    font-size: 14px;
                    word-break: break-all;
                }

                .instructions {
                    background: #f5f5f5;
                    border: 1px solid #ddd;
                    padding: 20px;
                    margin: 30px auto;
                    max-width: 400px;
                    text-align: left;
                    border-radius: 5px;
                }

                .instructions h3 {
                    margin-top: 0;
                    margin-bottom: 15px;
                    color: #333;
                    text-align: center;
                }

                .instructions p {
                    margin: 0;
                    line-height: 1.6;
                }

                .footer {
                    margin-top: 40px;
                    font-size: 12px;
                    color: #999;
                    border-top: 1px solid #eee;
                    padding-top: 20px;
                }

                .no-print {
                    margin-top: 30px;
                    text-align: center;
                }

                .btn {
                    display: inline-block;
                    padding: 12px 24px;
                    margin: 0 10px;
                    font-size: 16px;
                    text-decoration: none;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    font-weight: bold;
                }

                .btn-print {
                    background: #EE3524;
                    color: white;
                }

                .btn-back {
                    background: #666;
                    color: white;
                }

                @media print {
                    .no-print {
                        display: none !important;
                    }

                    body {
                        background: white;
                    }

                    .qr-container {
                        border: 2px solid #000;
                    }
                }
            </style>
        </head>

        <body>
            <div class="header">
                <div class="title"><?php echo esc_html($config['title']); ?></div>
                <div class="subtitle"><?php echo esc_html($config['subtitle']); ?></div>
            </div>

            <div class="qr-container">
                <img src="<?php echo esc_url($qr_url); ?>" alt="QR Code">
            </div>
            <div class="instructions">
                <h3><?php echo $lang === 'sr' ? 'Uputstvo za korišćenje' : 'Usage Instructions'; ?></h3>
                <p><?php echo $instructions; ?></p>
            </div>

            <div class="no-print">
                <button onclick="window.print()" class="btn btn-print">
                    <?php echo $lang === 'sr' ? '🖨️ Štampaj' : '🖨️ Print'; ?>
                </button>
                <button onclick="window.close()" class="btn btn-back">
                    <?php echo $lang === 'sr' ? '✕ Zatvori' : '✕ Close'; ?>
                </button>
            </div>

            <script>
                // Keyboard shortcuts
                document.addEventListener('keydown', function(e) {
                    if (e.ctrlKey && e.key === 'p') {
                        e.preventDefault();
                        window.print();
                    }
                    if (e.key === 'Escape') {
                        window.close();
                    }
                });
            </script>
        </body>

        </html>
<?php
        exit;
    }
}
