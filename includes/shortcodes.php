<?php
// includes/shortcodes.php

if (!defined('ABSPATH')) {
    exit;
}

class Survey_Shortcodes
{

    public static function init()
    {
        add_shortcode('registration_form', array(__CLASS__, 'registration_form'));
        add_shortcode('feedback_form', array(__CLASS__, 'feedback_form'));
        add_shortcode('feedback_form_inhouse', array(__CLASS__, 'feedback_form_inhouse'));
        add_action('wp_ajax_submit_registration', array(__CLASS__, 'handle_registration'));
        add_action('wp_ajax_nopriv_submit_registration', array(__CLASS__, 'handle_registration'));
        add_action('wp_ajax_submit_feedback', array(__CLASS__, 'handle_feedback'));
        add_action('wp_ajax_nopriv_submit_feedback', array(__CLASS__, 'handle_feedback'));
        add_action('wp_ajax_get_feedback_details', array(__CLASS__, 'get_feedback_details'));
        add_action('wp_ajax_nopriv_get_feedback_details', array(__CLASS__, 'get_feedback_details'));
    }

    public static function registration_form($atts)
    {
        $atts = shortcode_atts(array(
            'lang' => 'sr'
        ), $atts);

        $lang = $atts['lang'];

        // Tekstovi za oba jezika
        $texts = array(
            'sr' => array(
                'title' => 'Registracija polaznika kursa',
                'participant_name' => 'Ime i prezime polaznika:',
                'company' => 'Kompanija:',
                'address' => 'Adresa:',
                'position' => 'Radno mesto:',
                'phone' => 'Telefon:',
                'mobile' => 'Mobilni:',
                'email' => 'E-mail:',
                'submit' => 'Pošalji',
                'field_required' => 'Ovo polje je obavezno, molimo vas popunite',
                'choose_option' => 'Molimo izaberite jednu opciju',
                'fill_required_fields' => 'Molimo popunite sva obavezna polja!'
            ),
            'en' => array(
                'title' => 'Course Participant Registration',
                'participant_name' => 'Participant Name:',
                'company' => 'Company:',
                'address' => 'Address:',
                'position' => 'Position:',
                'phone' => 'Phone:',
                'mobile' => 'Mobile:',
                'email' => 'E-mail:',
                'submit' => 'Submit',
                'field_required' => 'This field is required, please fill it out',
                'choose_option' => 'Please choose one option',
                'fill_required_fields' => 'Please fill out all required fields!'
            )
        );

        $t = $texts[$lang];

        // Proveravamo da li je korisnik već popunio formu
        $user_hash = self::get_user_hash();
        $already_submitted = self::check_if_already_submitted('registration', $user_hash);

        ob_start();
?>
        <div class="indas-survey-form indas-registration-form">
            <h3><?php echo $t['title']; ?></h3>

            <?php if ($already_submitted): ?>
                <div class="form-message">
                    <div class="success-message">
                        <?php echo $lang === 'sr' ? 'Već ste se registrovali. Hvala!' : 'You have already registered. Thank you!'; ?>
                    </div>
                </div>
            <?php else: ?>
                <form id="indas-participant-form" method="post"
                    data-field-required="<?php echo esc_attr($t['field_required']); ?>"
                    data-choose-option="<?php echo esc_attr($t['choose_option']); ?>"
                    data-fill-required="<?php echo esc_attr($t['fill_required_fields']); ?>">
                    <?php wp_nonce_field('registration_nonce', 'registration_nonce'); ?>
                    <input type="hidden" name="language" value="<?php echo $lang; ?>">
                    <input type="hidden" name="user_hash" value="<?php echo $user_hash; ?>">

                    <div class="indas-form-row">
                        <label><?php echo $t['participant_name']; ?> *</label>
                        <input type="text" name="participant_name" required>
                    </div>

                    <div class="indas-form-row">
                        <label><?php echo $t['company']; ?> *</label>
                        <input type="text" name="company" required>
                    </div>

                    <div class="indas-form-row">
                        <label><?php echo $t['address']; ?> *</label>
                        <input type="text" name="address" required>
                    </div>

                    <div class="indas-form-row">
                        <label><?php echo $t['position']; ?> *</label>
                        <input type="text" name="position" required>
                    </div>

                    <div class="indas-form-row">
                        <label><?php echo $t['phone']; ?> *</label>
                        <input type="tel" name="phone" required>
                    </div>

                    <div class="indas-form-row">
                        <label><?php echo $t['mobile']; ?> *</label>
                        <input type="tel" name="mobile" required>
                    </div>

                    <div class="indas-form-row">
                        <label><?php echo $t['email']; ?> *</label>
                        <input type="email" name="email" required>
                    </div>

                    <div class="indas-form-row">
                        <button type="submit"><?php echo $t['submit']; ?></button>
                    </div>

                    <div class="form-message"></div>
                </form>
            <?php endif; ?>
        </div>
    <?php
        return ob_get_clean();
    }

    public static function feedback_form($atts)
    {
        $atts = shortcode_atts(array(
            'lang' => 'sr'
        ), $atts);

        $lang = $atts['lang'];

        // Tekstovi za oba jezika
        $texts = array(
            'sr' => array(
                'title' => 'Upitnik za polaznike kursa',
                'rate_following' => 'Molimo Vas da ocenite:',
                'expectations_met' => 'Da li je kurs ispunio Vaša očekivanja?',
                'expectations_level' => 'U kojoj meri je ispunio Vaša očekivanja?',
                'lecture_quality' => 'Kvalitet predavanja:',
                'lecturer_quality' => 'Kvalitet predavača:',
                'practical_application' => 'Primenjivost naučenog u praksi:',
                'literature' => 'Literatura:',
                'rate_organization' => 'Molimo Vas da ocenite organizaciju kursa:',
                'premises' => 'Prostorije:',
                'food' => 'Ishrana:',
                'cooperation' => 'Saradnja sa zaposlenima u Indas-u:',
                'future_courses' => 'Da li ste zainteresovani za dalje usavršavanje na budućim kursevima?',
                'advanced_step7' => 'Napredni kursevi STEP7:',
                'other_courses' => 'Kursevi iz drugih oblasti:',
                'improvements' => 'Šta bi po Vašem mišljenju unapredilo kvalitet kursa i omogućilo Vam da bolje savladate gradivo?',
                'additional_comments' => 'Dodao bih:',
                'yes' => 'Da',
                'no' => 'Ne',
                'submit' => 'Pošalji',
                'field_required' => 'Ovo polje je obavezno, molimo vas popunite',
                'choose_option' => 'Molimo izaberite jednu opciju',
                'fill_required_fields' => 'Molimo popunite sva obavezna polja!'
            ),
            'en' => array(
                'title' => 'Course Participant Questionnaire',
                'rate_following' => 'Please rate the following:',
                'expectations_met' => 'Did the course meet your expectations?',
                'expectations_level' => 'To what extent did it meet your expectations?',
                'lecture_quality' => 'Quality of lectures:',
                'lecturer_quality' => 'Quality of the lecturer:',
                'practical_application' => 'Applicability of what has been learned in practice:',
                'literature' => 'Literature:',
                'rate_organization' => 'Please rate the organization of the course:',
                'premises' => 'Premises:',
                'food' => 'Food:',
                'cooperation' => 'Cooperation with Indas employees:',
                'future_courses' => 'Are you interested in further development in future courses?',
                'advanced_step7' => 'Advanced courses STEP7:',
                'other_courses' => 'Courses in other fields:',
                'improvements' => 'In your opinion, what would improve the quality of the course and enable you to master the material better?',
                'additional_comments' => 'I would add:',
                'yes' => 'Yes',
                'no' => 'No',
                'submit' => 'Submit',
                'field_required' => 'This field is required, please fill it out',
                'choose_option' => 'Please choose one option',
                'fill_required_fields' => 'Please fill out all required fields!'
            )
        );

        $t = $texts[$lang];

        return self::render_feedback_form($t, 'standard', $lang);
    }

    public static function feedback_form_inhouse($atts)
    {
        $atts = shortcode_atts(array(
            'lang' => 'sr'
        ), $atts);

        $lang = $atts['lang'];

        // Tekstovi za in-house verziju (bez prostorija, hrane, saradnje)
        $texts = array(
            'sr' => array(
                'title' => 'Upitnik za polaznike kursa',
                'rate_following' => 'Molimo Vas da ocenite:',
                'expectations_met' => 'Da li je kurs ispunio Vaša očekivanja?',
                'expectations_level' => 'U kojoj meri je ispunio Vaša očekivanja?',
                'lecture_quality' => 'Kvalitet predavanja:',
                'lecturer_quality' => 'Kvalitet predavača:',
                'practical_application' => 'Primenjivost naučenog u praksi:',
                'literature' => 'Literatura:',
                'rate_organization' => 'Molimo Vas da ocenite organizaciju kursa:',
                'organization_rating' => 'U kojoj meri ste zadovoljni organizacijom:',
                'future_courses' => 'Da li ste zainteresovani za dalje usavršavanje na budućim kursevima?',
                'advanced_step7' => 'Napredni kursevi STEP7:',
                'other_courses' => 'Kursevi iz drugih oblasti:',
                'improvements' => 'Šta bi po Vašem mišljenju unapredilo kvalitet kursa i omogućilo Vam da bolje savladate gradivo?',
                'additional_comments' => 'Dodao/la bih:',
                'yes' => 'Da',
                'no' => 'Ne',
                'submit' => 'Pošalji'
            ),
            'en' => array(
                'title' => 'Course Participant Questionnaire',
                'rate_following' => 'Please rate the following:',
                'expectations_met' => 'Did the course meet your expectations?',
                'expectations_level' => 'To what extent did it meet your expectations?',
                'lecture_quality' => 'Quality of lectures:',
                'lecturer_quality' => 'Quality of the lecturer:',
                'practical_application' => 'Applicability of what has been learned in practice:',
                'literature' => 'Literature:',
                'rate_organization' => 'Please rate the organization of the course:',
                'organization_rating' => 'To what extent are you satisfied with the organization:',
                'future_courses' => 'Are you interested in further development in future courses?',
                'advanced_step7' => 'Advanced courses STEP7:',
                'other_courses' => 'Courses in other fields:',
                'improvements' => 'In your opinion, what would improve the quality of the course and enable you to master the material better?',
                'additional_comments' => 'I would add:',
                'yes' => 'Yes',
                'no' => 'No',
                'submit' => 'Submit'
            )
        );

        $t = $texts[$lang];

        return self::render_feedback_form($t, 'inhouse', $lang);
    }

    private static function render_feedback_form($t, $type, $lang)
    {
        // Proveravamo da li je korisnik već popunio feedback
        $user_hash = self::get_user_hash();
        $already_submitted = self::check_if_already_submitted('feedback', $user_hash);

        ob_start();
    ?>
        <div class="indas-survey-form indas-feedback-form">
            <h3><?php echo $t['title']; ?></h3>

            <?php if ($already_submitted): ?>
                <div class="form-message">
                    <div class="success-message">
                        <?php echo $lang === 'sr' ? 'Već ste popunili ovaj upitnik. Hvala!' : 'You have already completed this questionnaire. Thank you!'; ?>
                    </div>
                </div>
            <?php else: ?>
                <form id="indas-feedback-form" method="post"
                    data-field-required="<?php echo esc_attr($t['field_required']); ?>"
                    data-choose-option="<?php echo esc_attr($t['choose_option']); ?>"
                    data-fill-required="<?php echo esc_attr($t['fill_required_fields']); ?>">
                    <?php wp_nonce_field('feedback_nonce', 'feedback_nonce'); ?>
                    <input type="hidden" name="language" value="<?php echo $lang; ?>">
                    <input type="hidden" name="feedback_type" value="<?php echo $type; ?>">
                    <input type="hidden" name="user_hash" value="<?php echo $user_hash; ?>">

                    <h4><?php echo $t['rate_following']; ?></h4>

                    <div class="indas-form-row">
                        <label><?php echo $t['expectations_met']; ?></label>
                        <div class="radio-group">
                            <label><input type="radio" name="expectations_met" value="da" required> <?php echo $t['yes']; ?></label>
                            <label><input type="radio" name="expectations_met" value="ne" required> <?php echo $t['no']; ?></label>
                        </div>
                    </div>

                    <div class="indas-form-row">
                        <label><?php echo $t['expectations_level']; ?></label>
                        <div class="rating-group">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label><input type="radio" name="expectations_level" value="<?php echo $i; ?>" required> <?php echo $i; ?></label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="indas-form-row">
                        <label><?php echo $t['lecture_quality']; ?></label>
                        <div class="rating-group">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label><input type="radio" name="lecture_quality" value="<?php echo $i; ?>" required> <?php echo $i; ?></label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="indas-form-row">
                        <label><?php echo $t['lecturer_quality']; ?></label>
                        <div class="rating-group">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label><input type="radio" name="lecturer_quality" value="<?php echo $i; ?>" required> <?php echo $i; ?></label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="indas-form-row">
                        <label><?php echo $t['practical_application']; ?></label>
                        <div class="rating-group">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label><input type="radio" name="practical_application" value="<?php echo $i; ?>" required> <?php echo $i; ?></label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="indas-form-row">
                        <label><?php echo $t['literature']; ?></label>
                        <div class="rating-group">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label><input type="radio" name="literature" value="<?php echo $i; ?>" required> <?php echo $i; ?></label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <?php if ($type === 'standard'): ?>
                        <h4><?php echo $t['rate_organization']; ?></h4>

                        <div class="indas-form-row">
                            <label><?php echo $t['premises']; ?></label>
                            <div class="rating-group">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <label><input type="radio" name="premises" value="<?php echo $i; ?>" required> <?php echo $i; ?></label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="indas-form-row">
                            <label><?php echo $t['food']; ?></label>
                            <div class="rating-group">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <label><input type="radio" name="food" value="<?php echo $i; ?>" required> <?php echo $i; ?></label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="indas-form-row">
                            <label><?php echo $t['cooperation']; ?></label>
                            <div class="rating-group">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <label><input type="radio" name="cooperation" value="<?php echo $i; ?>" required> <?php echo $i; ?></label>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <h4><?php echo $t['rate_organization']; ?></h4>

                        <div class="indas-form-row">
                            <label><?php echo $t['organization_rating']; ?></label>
                            <div class="rating-group">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <label><input type="radio" name="premises" value="<?php echo $i; ?>" required> <?php echo $i; ?></label>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <h4><?php echo $t['future_courses']; ?></h4>

                    <div class="indas-form-row">
                        <label><?php echo $t['advanced_step7']; ?></label>
                        <textarea name="advanced_step7" rows="2"></textarea>
                    </div>

                    <div class="indas-form-row">
                        <label><?php echo $t['other_courses']; ?></label>
                        <textarea name="other_courses" rows="2"></textarea>
                    </div>

                    <div class="indas-form-row">
                        <label><?php echo $t['improvements']; ?></label>
                        <textarea name="improvements" rows="4"></textarea>
                    </div>

                    <div class="indas-form-row">
                        <label><?php echo $t['additional_comments']; ?></label>
                        <textarea name="additional_comments" rows="4"></textarea>
                    </div>

                    <div class="indas-form-row">
                        <button type="submit"><?php echo $t['submit']; ?></button>
                    </div>

                    <div class="form-message"></div>
                </form>
            <?php endif; ?>
        </div>
<?php
        return ob_get_clean();
    }

    // Helper funkcije za praćenje submitovanja
    private static function get_user_hash()
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        return md5($ip . $user_agent); // Jedinstveni hash per IP/browser (bez datuma!)
    }
    private static function generate_registration_email_template($data)
    {
        $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
            .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { background: #EE3524; color: white; padding: 20px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; }
            .content { padding: 30px; }
            .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .info-table td { padding: 12px; border-bottom: 1px solid #eee; }
            .info-table .label { font-weight: bold; color: #333; width: 30%; background: #f9f9f9; }
            .info-table .value { color: #666; }
            .footer { background: #f9f9f9; padding: 20px; text-align: center; color: #666; font-size: 12px; }
            .badge { background: #EE3524; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>📝 Nova registracija polaznika</h1>
                <p style="margin: 5px 0 0 0;">Indas Survey System</p>
            </div>
            
            <div class="content">
                <p>Zdravo!</p>
                <p>Upravo je stigla <strong>nova registracija polaznika kursa</strong>. Evo detalja:</p>
                
                <table class="info-table">
                    <tr>
                        <td class="label">👤 Ime i prezime:</td>
                        <td class="value">' . esc_html($data['participant_name']) . '</td>
                    </tr>
                    <tr>
                        <td class="label">🏢 Kompanija:</td>
                        <td class="value">' . esc_html($data['company']) . '</td>
                    </tr>
                    <tr>
                        <td class="label">📍 Adresa:</td>
                        <td class="value">' . esc_html($data['address']) . '</td>
                    </tr>
                    <tr>
                        <td class="label">💼 Radno mesto:</td>
                        <td class="value">' . esc_html($data['position']) . '</td>
                    </tr>
                    <tr>
                        <td class="label">📞 Telefon:</td>
                        <td class="value">' . esc_html($data['phone']) . '</td>
                    </tr>
                    <tr>
                        <td class="label">📱 Mobilni:</td>
                        <td class="value">' . esc_html($data['mobile']) . '</td>
                    </tr>
                    <tr>
                        <td class="label">✉️ Email:</td>
                        <td class="value">' . esc_html($data['email']) . '</td>
                    </tr>
                    <tr>
                        <td class="label">🌐 Jezik:</td>
                        <td class="value"><span class="badge">' . strtoupper($data['language']) . '</span></td>
                    </tr>
                </table>
                
                <p style="margin-top: 30px;">
                    <a href="' . admin_url('admin.php?page=survey-registrations') . '" 
                       style="background: #EE3524; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">
                       📊 Pogledaj sve registracije
                    </a>
                </p>
            </div>
            
            <div class="footer">
                Vreme registracije: ' . date('d.m.Y H:i:s') . '<br>
                Indas Survey System - Automated Email
            </div>
        </div>
    </body>
    </html>';

        return $html;
    }
    private static function generate_feedback_email_template($data)
    {
        $rating_stars = function ($rating) {
            $stars = '';
            for ($i = 1; $i <= 5; $i++) {
                $stars .= $i <= $rating ? '⭐' : '☆';
            }
            return $stars . " ($rating/5)";
        };

        $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
            .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { background: #EE3524; color: white; padding: 20px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; }
            .content { padding: 30px; }
            .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .info-table td { padding: 12px; border-bottom: 1px solid #eee; }
            .info-table .label { font-weight: bold; color: #333; width: 40%; background: #f9f9f9; }
            .info-table .value { color: #666; }
            .footer { background: #f9f9f9; padding: 20px; text-align: center; color: #666; font-size: 12px; }
            .badge { background: #EE3524; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
            .comments { background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #EE3524; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>📋 Novi feedback o kursu</h1>
                <p style="margin: 5px 0 0 0;">Indas Survey System</p>
            </div>
            
            <div class="content">
                <p>Upravo je stigao <strong>novi feedback o kursu</strong>!</p>
                
                <table class="info-table">
                    <tr>
                        <td class="label">📝 Tip feedback-a:</td>
                        <td class="value"><span class="badge">' . ucfirst(sanitize_text_field($data['feedback_type'])) . '</span></td>
                    </tr>
                    <tr>
                        <td class="label">🌐 Jezik:</td>
                        <td class="value"><span class="badge">' . strtoupper(sanitize_text_field($data['language'])) . '</span></td>
                    </tr>
                    <tr>
                        <td class="label">✅ Očekivanja ispunjena:</td>
                        <td class="value">' . (sanitize_text_field($data['expectations_met']) === 'da' ? '✅ DA' : '❌ NE') . '</td>
                    </tr>
                    <tr>
                        <td class="label">📊 Nivo očekivanja:</td>
                        <td class="value">' . $rating_stars(intval($data['expectations_level'])) . '</td>
                    </tr>
                    <tr>
                        <td class="label">🎓 Kvalitet predavanja:</td>
                        <td class="value">' . $rating_stars(intval($data['lecture_quality'])) . '</td>
                    </tr>
                    <tr>
                        <td class="label">👨‍🏫 Kvalitet predavača:</td>
                        <td class="value">' . $rating_stars(intval($data['lecturer_quality'])) . '</td>
                    </tr>
                    <tr>
                        <td class="label">💼 Primenjivost u praksi:</td>
                        <td class="value">' . $rating_stars(intval($data['practical_application'])) . '</td>
                    </tr>
                    <tr>
                        <td class="label">📚 Literatura:</td>
                        <td class="value">' . $rating_stars(intval($data['literature'])) . '</td>
                    </tr>';

        if (isset($data['premises']) && $data['premises']) {
            $html .= '<tr>
                        <td class="label">🏢 Prostorije:</td>
                        <td class="value">' . $rating_stars(intval($data['premises'])) . '</td>
                    </tr>';
        }

        if (isset($data['food']) && $data['food']) {
            $html .= '<tr>
                        <td class="label">🍽️ Ishrana:</td>
                        <td class="value">' . $rating_stars(intval($data['food'])) . '</td>
                    </tr>';
        }

        if (isset($data['cooperation']) && $data['cooperation']) {
            $html .= '<tr>
                        <td class="label">🤝 Saradnja:</td>
                        <td class="value">' . $rating_stars(intval($data['cooperation'])) . '</td>
                    </tr>';
        }

        $html .= '</table>';

        // Comments section
        $has_comments = false;
        $comments_html = '<h3 style="color: #EE3524; margin-top: 30px;">💬 Komentari:</h3>';

        if (!empty($data['advanced_step7'])) {
            $has_comments = true;
            $comments_html .= '<div class="comments"><strong>Napredni STEP7 kursevi:</strong><br>' . esc_html(sanitize_textarea_field($data['advanced_step7'])) . '</div>';
        }

        if (!empty($data['other_courses'])) {
            $has_comments = true;
            $comments_html .= '<div class="comments"><strong>Drugi kursevi:</strong><br>' . esc_html(sanitize_textarea_field($data['other_courses'])) . '</div>';
        }

        if (!empty($data['improvements'])) {
            $has_comments = true;
            $comments_html .= '<div class="comments"><strong>Predlozi za poboljšanje:</strong><br>' . esc_html(sanitize_textarea_field($data['improvements'])) . '</div>';
        }

        if (!empty($data['additional_comments'])) {
            $has_comments = true;
            $comments_html .= '<div class="comments"><strong>Dodatni komentari:</strong><br>' . esc_html(sanitize_textarea_field($data['additional_comments'])) . '</div>';
        }

        if ($has_comments) {
            $html .= $comments_html;
        }

        $html .= '
                <p style="margin-top: 30px;">
                    <a href="' . admin_url('admin.php?page=survey-feedback') . '" 
                       style="background: #EE3524; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">
                       📈 Pogledaj sve feedback-ove
                    </a>
                </p>
            </div>
            
            <div class="footer">
                Vreme slanja: ' . date('d.m.Y H:i:s') . '<br>
                Indas Survey System - Automated Email
            </div>
        </div>
    </body>
    </html>';

        return $html;
    }
    private static function check_if_already_submitted($type, $user_hash)
    {
        global $wpdb;
        if (get_option('survey_test_mode', false)) {
            return false;
        }
        if ($type === 'registration') {
            $table_name = $wpdb->prefix . 'course_participants';
            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE user_hash = %s",
                    $user_hash
                )
            );
        } else {
            $table_name = $wpdb->prefix . 'course_feedback';
            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE user_hash = %s",
                    $user_hash
                )
            );
        }

        return $count > 0;
    }

    public static function handle_registration()
    {
        // Proveravamo nonce
        if (!wp_verify_nonce($_POST['registration_nonce'], 'registration_nonce')) {
            wp_send_json_error('Neispravna sigurnosna provera');
            return;
        }

        // Proveravamo da li je već submitovano
        $user_hash = sanitize_text_field($_POST['user_hash']);
        if (self::check_if_already_submitted('registration', $user_hash)) {
            wp_send_json_error('Već ste se registrovali.');
            return;
        }

        $data = $_POST;
        $data['user_hash'] = $user_hash;

        $result = Survey_Database::save_participant($data);

        if ($result) {
            // Koristimo custom email ako je postavljen, inače fallback na admin_email
            $notification_email = get_option('survey_notification_email', get_option('admin_email'));

            $subject = 'Nova registracija polaznika kursa';
            $message = self::generate_registration_email_template($data);
            $headers = array('Content-Type: text/html; charset=UTF-8');

            wp_mail($notification_email, $subject, $message, $headers);

            wp_send_json_success('Uspešno ste se registrovali!');
        } else {
            wp_send_json_error('Greška prilikom registracije. Pokušajte ponovo.');
        }
    }

    public static function handle_feedback()
    {
        // Proveravamo nonce
        if (!wp_verify_nonce($_POST['feedback_nonce'], 'feedback_nonce')) {
            wp_send_json_error('Neispravna sigurnosna provera');
            return;
        }

        // Proveravamo da li je već submitovano
        $user_hash = sanitize_text_field($_POST['user_hash']);
        if (self::check_if_already_submitted('feedback', $user_hash)) {
            wp_send_json_error('Već ste popunili ovaj upitnik.');
            return;
        }

        $data = $_POST;
        $data['user_hash'] = $user_hash;

        $result = Survey_Database::save_feedback($data);

        if ($result) {
            // Koristimo custom email ako je postavljen, inače fallback na admin_email
            $notification_email = get_option('survey_notification_email', get_option('admin_email'));

            $subject = 'Novi feedback o kursu';
            $message = self::generate_feedback_email_template($data);
            $headers = array('Content-Type: text/html; charset=UTF-8');

            wp_mail($notification_email, $subject, $message, $headers);

            wp_send_json_success('Hvala na povratnim informacijama!');
        } else {
            wp_send_json_error('Greška prilikom slanja. Pokušajte ponovo.');
        }
    }

    public static function get_feedback_details()
    {
        if (!isset($_POST['id'])) {
            wp_die('Nedostaje ID');
        }

        $id = intval($_POST['id']);
        $feedback = Survey_Database::get_feedback_details($id);

        if ($feedback) {
            echo '<h3>Feedback detalji #' . $feedback->id . '</h3>';
            echo '<p><strong>Datum:</strong> ' . date('d.m.Y H:i', strtotime($feedback->submitted_at)) . '</p>';
            echo '<p><strong>Tip:</strong> ' . ucfirst($feedback->feedback_type) . '</p>';
            echo '<p><strong>Jezik:</strong> ' . strtoupper($feedback->language) . '</p>';
            echo '<p><strong>Očekivanja ispunjena:</strong> ' . $feedback->expectations_met . '</p>';
            echo '<p><strong>Nivo očekivanja:</strong> ' . $feedback->expectations_level . '/5</p>';
            echo '<p><strong>Kvalitet predavanja:</strong> ' . $feedback->lecture_quality . '/5</p>';
            echo '<p><strong>Kvalitet predavača:</strong> ' . $feedback->lecturer_quality . '/5</p>';
            echo '<p><strong>Primenjivost:</strong> ' . $feedback->practical_application . '/5</p>';
            echo '<p><strong>Literatura:</strong> ' . $feedback->literature . '/5</p>';

            if ($feedback->premises) {
                echo '<p><strong>Prostorije:</strong> ' . $feedback->premises . '/5</p>';
            }
            if ($feedback->food) {
                echo '<p><strong>Ishrana:</strong> ' . $feedback->food . '/5</p>';
            }
            if ($feedback->cooperation) {
                echo '<p><strong>Saradnja:</strong> ' . $feedback->cooperation . '/5</p>';
            }

            if ($feedback->advanced_step7) {
                echo '<p><strong>Napredni STEP7:</strong> ' . esc_html($feedback->advanced_step7) . '</p>';
            }
            if ($feedback->other_courses) {
                echo '<p><strong>Drugi kursevi:</strong> ' . esc_html($feedback->other_courses) . '</p>';
            }
            if ($feedback->improvements) {
                echo '<p><strong>Poboljšanja:</strong> ' . esc_html($feedback->improvements) . '</p>';
            }
            if ($feedback->additional_comments) {
                echo '<p><strong>Dodatni komentari:</strong> ' . esc_html($feedback->additional_comments) . '</p>';
            }
        }

        wp_die();
    }
}
