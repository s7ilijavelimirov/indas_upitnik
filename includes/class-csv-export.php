<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * CSV Export klasa
 */
class INDAS_Survey_CSV_Export
{

    private $db;

    public function __construct()
    {
        $this->db = INDAS_Survey_Database::get_instance();
    }

    /**
     * Glavni export metod
     */
    public function export($type = 'all')
    {
        switch ($type) {
            case 'personal':
                $this->export_personal_surveys();
                break;
            case 'feedback':
                $this->export_feedback_surveys();
                break;
            case 'all':
            default:
                $this->export_all_surveys();
                break;
        }
    }

    /**
     * Izvezi lične upitnike
     */
    private function export_personal_surveys()
    {
        $surveys = $this->db->get_personal_surveys(999999, 0); // Svi upitnici

        if (empty($surveys)) {
            wp_die('Nema podataka za izvoz.');
        }

        $filename = 'indas-licni-podaci-' . date('Y-m-d-H-i-s') . '.csv';

        $this->set_csv_headers($filename);

        $output = fopen('php://output', 'w');

        // UTF-8 BOM za pravilno prikazivanje u Excel-u
        fputs($output, "\xEF\xBB\xBF");

        // Header red
        $headers = array(
            'ID',
            'Session ID',
            'Ime',
            'Prezime',
            'Naziv firme',
            'Email',
            'Telefon',
            'Jezik',
            'Datum kreiranja',
            'IP adresa',
            'User Agent'
        );

        fputcsv($output, $headers, ';');

        // Podaci
        foreach ($surveys as $survey) {
            $row = array(
                $survey->id,
                $survey->session_id,
                $survey->opsta_ocena ?: '',
                $survey->preporuka_kursa ?: '',
                $survey->ocena_predavaca ?: '',
                $survey->ocena_sadrzaja ?: '',
                $survey->ocena_objekta ?: '',
                $survey->ocena_hrane ?: '',
                $survey->ocena_organizacije ?: '',
                $this->clean_text_for_csv($survey->komentar_predavac),
                $this->clean_text_for_csv($survey->komentar_sadrzaj),
                $this->clean_text_for_csv($survey->komentar_objekat),
                $this->clean_text_for_csv($survey->komentar_hrana),
                $this->clean_text_for_csv($survey->komentar_organizacija),
                $this->clean_text_for_csv($survey->komentar_opsti),
                $this->clean_text_for_csv($survey->predlozi_poboljsanja),
                strtoupper($survey->jezik),
                $survey->vreme_popunjavanja ?: 0,
                date('d.m.Y H:i:s', strtotime($survey->datum_kreiranja)),
                $survey->ip_adresa
            );

            fputcsv($output, $row, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Izvezi sve upitnike u jednom fajlu
     */
    private function export_all_surveys()
    {
        $personal_surveys = $this->db->get_personal_surveys(999999, 0);
        $feedback_surveys = $this->db->get_feedback_surveys(999999, 0);

        if (empty($personal_surveys) && empty($feedback_surveys)) {
            wp_die('Nema podataka za izvoz.');
        }

        $filename = 'indas-svi-upitnici-' . date('Y-m-d-H-i-s') . '.csv';

        $this->set_csv_headers($filename);

        $output = fopen('php://output', 'w');

        // UTF-8 BOM
        fputs($output, "\xEF\xBB\xBF");

        // SEKCIJA 1: LIČNI PODACI
        if (!empty($personal_surveys)) {
            // Header za lične podatke
            $personal_title = array('=== LIČNI PODACI POLAZNIKA ===');
            fputcsv($output, $personal_title, ';');

            $personal_headers = array(
                'ID',
                'Session ID',
                'Ime',
                'Prezime',
                'Naziv firme',
                'Email',
                'Telefon',
                'Jezik',
                'Datum kreiranja'
            );
            fputcsv($output, $personal_headers, ';');

            foreach ($personal_surveys as $survey) {
                $row = array(
                    $survey->id,
                    $survey->session_id,
                    $survey->ime,
                    $survey->prezime,
                    $survey->naziv_firme,
                    $survey->email,
                    $survey->telefon ?: '',
                    strtoupper($survey->jezik),
                    date('d.m.Y H:i:s', strtotime($survey->datum_kreiranja))
                );
                fputcsv($output, $row, ';');
            }

            // Prazan red
            fputcsv($output, array(''), ';');
        }

        // SEKCIJA 2: FEEDBACK
        if (!empty($feedback_surveys)) {
            // Header za feedback
            $feedback_title = array('=== FEEDBACK UPITNICI ===');
            fputcsv($output, $feedback_title, ';');

            $feedback_headers = array(
                'ID',
                'Session ID',
                'Opšta ocena',
                'Preporuka',
                'Predavač',
                'Sadržaj',
                'Objekat',
                'Hrana',
                'Organizacija',
                'Datum kreiranja'
            );
            fputcsv($output, $feedback_headers, ';');

            foreach ($feedback_surveys as $survey) {
                $row = array(
                    $survey->id,
                    $survey->session_id,
                    $this->format_rating($survey->opsta_ocena),
                    $this->format_rating($survey->preporuka_kursa),
                    $this->format_rating($survey->ocena_predavaca),
                    $this->format_rating($survey->ocena_sadrzaja),
                    $this->format_rating($survey->ocena_objekta),
                    $this->format_rating($survey->ocena_hrane),
                    $this->format_rating($survey->ocena_organizacije),
                    date('d.m.Y H:i:s', strtotime($survey->datum_kreiranja))
                );
                fputcsv($output, $row, ';');
            }

            // Prazan red
            fputcsv($output, array(''), ';');

            // DETALJNI KOMENTARI
            $comments_title = array('=== DETALJNI KOMENTARI FEEDBACK UPITNIKA ===');
            fputcsv($output, $comments_title, ';');

            $comments_headers = array(
                'ID',
                'Komentar Predavač',
                'Komentar Sadržaj',
                'Komentar Objekat',
                'Komentar Hrana',
                'Komentar Organizacija',
                'Opšti komentar',
                'Predlozi'
            );
            fputcsv($output, $comments_headers, ';');

            foreach ($feedback_surveys as $survey) {
                $row = array(
                    $survey->id,
                    $this->clean_text_for_csv($survey->komentar_predavac),
                    $this->clean_text_for_csv($survey->komentar_sadrzaj),
                    $this->clean_text_for_csv($survey->komentar_objekat),
                    $this->clean_text_for_csv($survey->komentar_hrana),
                    $this->clean_text_for_csv($survey->komentar_organizacija),
                    $this->clean_text_for_csv($survey->komentar_opsti),
                    $this->clean_text_for_csv($survey->predlozi_poboljsanja)
                );
                fputcsv($output, $row, ';');
            }
        }

        // STATISTIKE NA KRAJU
        fputcsv($output, array(''), ';');
        $stats_title = array('=== STATISTIKE ===');
        fputcsv($output, $stats_title, ';');

        $stats = array(
            array('Ukupno ličnih upitnika:', count($personal_surveys)),
            array('Ukupno feedback upitnika:', count($feedback_surveys)),
            array('Stopa odziva:', count($personal_surveys) > 0 ? round((count($feedback_surveys) / count($personal_surveys)) * 100, 2) . '%' : '0%'),
            array('Datum izvoza:', date('d.m.Y H:i:s'))
        );

        foreach ($stats as $stat) {
            fputcsv($output, $stat, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Postavi CSV headers za download
     */
    private function set_csv_headers($filename)
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Expires: 0');
    }

    /**
     * Očisti tekst za CSV
     */
    private function clean_text_for_csv($text)
    {
        if (empty($text)) return '';

        // Ukloni HTML tagove ako postoje
        $text = strip_tags($text);

        // Zameni novi red sa razmakom
        $text = str_replace(array("\r\n", "\r", "\n"), ' ', $text);

        // Ukloni višestruke razmake
        $text = preg_replace('/\s+/', ' ', $text);

        // Trim
        return trim($text);
    }

    /**
     * Formatiraj ocenu za CSV
     */
    private function format_rating($rating)
    {
        if (empty($rating)) return '';
        return $rating . '/5';
    }

    /**
     * Generiši detaljni statistički izveštaj
     */
    public function export_statistics()
    {
        $personal_surveys = $this->db->get_personal_surveys(999999, 0);
        $feedback_surveys = $this->db->get_feedback_surveys(999999, 0);

        $filename = 'indas-statistike-' . date('Y-m-d-H-i-s') . '.csv';

        $this->set_csv_headers($filename);

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF");

        // OPŠTE STATISTIKE
        $general_title = array('=== OPŠTE STATISTIKE ===');
        fputcsv($output, $general_title, ';');

        $general_stats = array(
            array('Ukupno ličnih upitnika', count($personal_surveys)),
            array('Ukupno feedback upitnika', count($feedback_surveys)),
            array('Stopa odziva', count($personal_surveys) > 0 ? round((count($feedback_surveys) / count($personal_surveys)) * 100, 2) . '%' : '0%'),
            array('', ''),
        );

        foreach ($general_stats as $stat) {
            fputcsv($output, $stat, ';');
        }

        // STATISTIKE FEEDBACK OCENA
        if (!empty($feedback_surveys)) {
            $rating_title = array('=== STATISTIKE OCENA ===');
            fputcsv($output, $rating_title, ';');

            $ratings = array(
                'opsta_ocena' => 'Opšta ocena',
                'preporuka_kursa' => 'Preporuka kursa',
                'ocena_predavaca' => 'Ocena predavača',
                'ocena_sadrzaja' => 'Ocena sadržaja',
                'ocena_objekta' => 'Ocena objekta',
                'ocena_hrane' => 'Ocena hrane',
                'ocena_organizacije' => 'Ocena organizacije'
            );

            foreach ($ratings as $field => $label) {
                $values = array_filter(array_column($feedback_surveys, $field));

                if (!empty($values)) {
                    $avg = round(array_sum($values) / count($values), 2);
                    $min = min($values);
                    $max = max($values);

                    $rating_stats = array(
                        array($label . ' - Prosek', $avg),
                        array($label . ' - Minimum', $min),
                        array($label . ' - Maksimum', $max),
                        array('', '')
                    );

                    foreach ($rating_stats as $stat) {
                        fputcsv($output, $stat, ';');
                    }
                }
            }
        }

        // DISTRIBUCIJA PO JEZICIMA
        $lang_title = array('=== DISTRIBUCIJA PO JEZICIMA ===');
        fputcsv($output, $lang_title, ';');

        $lang_distribution = array();

        // Lični upitnici
        foreach ($personal_surveys as $survey) {
            $lang = strtoupper($survey->jezik);
            $lang_distribution[$lang] = ($lang_distribution[$lang] ?? 0) + 1;
        }

        fputcsv($output, array('Lični upitnici:'), ';');
        foreach ($lang_distribution as $lang => $count) {
            fputcsv($output, array($lang, $count), ';');
        }

        fputcsv($output, array(''), ';');

        // Feedback upitnici
        $feedback_lang_distribution = array();
        foreach ($feedback_surveys as $survey) {
            $lang = strtoupper($survey->jezik);
            $feedback_lang_distribution[$lang] = ($feedback_lang_distribution[$lang] ?? 0) + 1;
        }

        fputcsv($output, array('Feedback upitnici:'), ';');
        foreach ($feedback_lang_distribution as $lang => $count) {
            fputcsv($output, array($lang, $count), ';');
        }

        // DATUM ANALIZA
        fputcsv($output, array(''), ';');
        $date_title = array('=== ANALIZA PO DATUMIMA ===');
        fputcsv($output, $date_title, ';');

        $daily_stats = array();

        foreach ($personal_surveys as $survey) {
            $date = date('Y-m-d', strtotime($survey->datum_kreiranja));
            $daily_stats[$date]['personal'] = ($daily_stats[$date]['personal'] ?? 0) + 1;
        }

        foreach ($feedback_surveys as $survey) {
            $date = date('Y-m-d', strtotime($survey->datum_kreiranja));
            $daily_stats[$date]['feedback'] = ($daily_stats[$date]['feedback'] ?? 0) + 1;
        }

        ksort($daily_stats);

        fputcsv($output, array('Datum', 'Lični upitnici', 'Feedback upitnici'), ';');
        foreach ($daily_stats as $date => $stats) {
            fputcsv($output, array(
                date('d.m.Y', strtotime($date)),
                $stats['personal'] ?? 0,
                $stats['feedback'] ?? 0
            ), ';');
        }

        fputcsv($output, array(''), ';');
        fputcsv($output, array('Izveštaj generisan:', date('d.m.Y H:i:s')), ';');

        fclose($output);
        exit;
    }

    /**
     * Generiši Excel-kompatibilan CSV
     */
    public function export_excel_compatible($type = 'all')
    {
        // Isto kao export ali sa različitim separatorima i enkodiranjem za Excel
        switch ($type) {
            case 'personal':
                $this->export_personal_surveys_excel();
                break;
            case 'feedback':
                $this->export_feedback_surveys_excel();
                break;
            default:
                $this->export_all_surveys_excel();
                break;
        }
    }

    private function export_personal_surveys_excel()
    {
        $surveys = $this->db->get_personal_surveys(999999, 0);

        if (empty($surveys)) {
            wp_die('Nema podataka za izvoz.');
        }

        $filename = 'indas-licni-podaci-excel-' . date('Y-m-d-H-i-s') . '.csv';

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

        $output = fopen('php://output', 'w');

        // Excel-specific BOM
        fputs($output, "\xEF\xBB\xBF");

        $headers = array(
            'ID',
            'Session ID',
            'Ime',
            'Prezime',
            'Naziv firme',
            'Email',
            'Telefon',
            'Jezik',
            'Datum kreiranja'
        );

        fputcsv($output, $headers, ';');

        foreach ($surveys as $survey) {
            $row = array(
                $survey->id,
                $survey->session_id,
                $survey->ime,
                $survey->prezime,
                $survey->naziv_firme,
                $survey->email,
                $survey->telefon ?: '',
                strtoupper($survey->jezik),
                date('d.m.Y H:i:s', strtotime($survey->datum_kreiranja))
            );

            fputcsv($output, $row, ';');
        }

        fclose($output);
        exit;
    }

    private function export_feedback_surveys_excel()
    {
        // Slično kao export_feedback_surveys ali za Excel
        $this->export_feedback_surveys();
    }

    private function export_all_surveys_excel()
    {
        // Slično kao export_all_surveys ali za Excel
        $this->export_all_surveys();
    }
}
