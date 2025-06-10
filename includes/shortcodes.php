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
        add_action('wp_ajax_submit_registration', array(__CLASS__, 'handle_registration'));
        add_action('wp_ajax_nopriv_submit_registration', array(__CLASS__, 'handle_registration'));
        add_action('wp_ajax_submit_feedback', array(__CLASS__, 'handle_feedback'));
        add_action('wp_ajax_nopriv_submit_feedback', array(__CLASS__, 'handle_feedback'));
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
                'course_name' => 'Naziv kursa:',
                'course_date' => 'Datum kursa:',
                'location' => 'Mesto održavanja:',
                'first_name' => 'Ime:',
                'last_name' => 'Prezime:',
                'company' => 'Kompanija:',
                'address' => 'Adresa:',
                'position' => 'Radno mesto:',
                'phone' => 'Telefon:',
                'mobile' => 'Mobilni:',
                'email' => 'E-mail:',
                'submit' => 'Pošalji',
                'required' => 'Obavezno polje'
            ),
            'en' => array(
                'title' => 'Course Participant Registration',
                'course_name' => 'Course Name:',
                'course_date' => 'Course Date:',
                'location' => 'Location:',
                'first_name' => 'First Name:',
                'last_name' => 'Last Name:',
                'company' => 'Company:',
                'address' => 'Address:',
                'position' => 'Position:',
                'phone' => 'Phone:',
                'mobile' => 'Mobile:',
                'email' => 'E-mail:',
                'submit' => 'Submit',
                'required' => 'Required field'
            )
        );

        $t = $texts[$lang];

        ob_start();
?>
        <div class="survey-form registration-form">
            <h3><?php echo $t['title']; ?></h3>
            <form id="registration-form" method="post">
                <?php wp_nonce_field('registration_nonce', 'registration_nonce'); ?>
                <input type="hidden" name="language" value="<?php echo $lang; ?>">

                <div class="form-row">
                    <label><?php echo $t['course_name']; ?> *</label>
                    <input type="text" name="course_name" required>
                </div>

                <div class="form-row">
                    <label><?php echo $t['course_date']; ?> *</label>
                    <input type="text" name="course_date" required>
                </div>

                <div class="form-row">
                    <label><?php echo $t['location']; ?> *</label>
                    <input type="text" name="location" required>
                </div>

                <div class="form-row">
                    <label><?php echo $t['first_name']; ?> *</label>
                    <input type="text" name="first_name" required>
                </div>

                <div class="form-row">
                    <label><?php echo $t['last_name']; ?> *</label>
                    <input type="text" name="last_name" required>
                </div>

                <div class="form-row">
                    <label><?php echo $t['company']; ?> *</label>
                    <input type="text" name="company" required>
                </div>

                <div class="form-row">
                    <label><?php echo $t['address']; ?> *</label>
                    <input type="text" name="address" required>
                </div>

                <div class="form-row">
                    <label><?php echo $t['position']; ?> *</label>
                    <input type="text" name="position" required>
                </div>

                <div class="form-row">
                    <label><?php echo $t['phone']; ?> *</label>
                    <input type="tel" name="phone" required>
                </div>

                <div class="form-row">
                    <label><?php echo $t['mobile']; ?> *</label>
                    <input type="tel" name="mobile" required>
                </div>

                <div class="form-row">
                    <label><?php echo $t['email']; ?> *</label>
                    <input type="email" name="email" required>
                </div>

                <div class="form-row">
                    <button type="submit"><?php echo $t['submit']; ?></button>
                </div>

                <div class="form-message"></div>
            </form>
        </div>

        <script>
            jQuery(document).ready(function($) {
                $('#registration-form').on('submit', function(e) {
                    e.preventDefault();

                    var formData = new FormData(this);
                    formData.append('action', 'submit_registration');

                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                $('.form-message').html('<p style="color: green;">' + response.data + '</p>');
                                $('#registration-form')[0].reset();
                            } else {
                                $('.form-message').html('<p style="color: red;">' + response.data + '</p>');
                            }
                        }
                    });
                });
            });
        </script>
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
                'submit' => 'Submit'
            )
        );

        $t = $texts[$lang];

        ob_start();
    ?>
        <div class="survey-form feedback-form">
            <h3><?php echo $t['title']; ?></h3>
            <form id="feedback-form" method="post">
                <?php wp_nonce_field('feedback_nonce', 'feedback_nonce'); ?>
                <input type="hidden" name="language" value="<?php echo $lang; ?>">

                <h4><?php echo $t['rate_following']; ?></h4>

                <div class="form-row">
                    <label><?php echo $t['expectations_met']; ?></label>
                    <div class="radio-group">
                        <label><input type="radio" name="expectations_met" value="da" required> <?php echo $t['yes']; ?></label>
                        <label><input type="radio" name="expectations_met" value="ne" required> <?php echo $t['no']; ?></label>
                    </div>
                </div>

                <div class="form-row">
                    <label><?php echo $t['expectations_level']; ?></label>
                    <div class="rating-group">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label><input type="radio" name="expectations_level" value="<?php echo $i; ?>" required> <?php echo $i; ?></label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="form-row">
                    <label><?php echo $t['lecture_quality']; ?></label>
                    <div class="rating-group">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label><input type="radio" name="lecture_quality" value="<?php echo $i; ?>" required> <?php echo $i; ?></label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="form-row">
                    <label><?php echo $t['lecturer_quality']; ?></label>
                    <div class="rating-group">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label><input type="radio" name="lecturer_quality" value="<?php echo $i; ?>" required> <?php echo $i; ?></label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="form-row">
                    <label><?php echo $t['practical_application']; ?></label>
                    <div class="rating-group">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label><input type="radio" name="practical_application" value="<?php echo $i; ?>" required> <?php echo $i; ?></label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="form-row">
                    <label><?php echo $t['literature']; ?></label>
                    <div class="rating-group">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label><input type="radio" name="literature" value="<?php echo $i; ?>" required> <?php echo $i; ?></label>
                        <?php endfor; ?>
                    </div>
                </div>

                <h4><?php echo $t['rate_organization']; ?></h4>

                <div class="form-row">
                    <label><?php echo $t['premises']; ?></label>
                    <div class="rating-group">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label><input type="radio" name="premises" value="<?php echo $i; ?>" required> <?php echo $i; ?></label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="form-row">
                    <label><?php echo $t['food']; ?></label>
                    <div class="rating-group">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label><input type="radio" name="food" value="<?php echo $i; ?>" required> <?php echo $i; ?></label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="form-row">
                    <label><?php echo $t['cooperation']; ?></label>
                    <div class="rating-group">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label><input type="radio" name="cooperation" value="<?php echo $i; ?>" required> <?php echo $i; ?></label>
                        <?php endfor; ?>
                    </div>
                </div>

                <h4><?php echo $t['future_courses']; ?></h4>

                <div class="form-row">
                    <label><?php echo $t['advanced_step7']; ?></label>
                    <textarea name="advanced_step7" rows="2"></textarea>
                </div>

                <div class="form-row">
                    <label><?php echo $t['other_courses']; ?></label>
                    <textarea name="other_courses" rows="2"></textarea>
                </div>

                <div class="form-row">
                    <label><?php echo $t['improvements']; ?></label>
                    <textarea name="improvements" rows="4"></textarea>
                </div>

                <div class="form-row">
                    <label><?php echo $t['additional_comments']; ?></label>
                    <textarea name="additional_comments" rows="4"></textarea>
                </div>

                <div class="form-row">
                    <button type="submit"><?php echo $t['submit']; ?></button>
                </div>

                <div class="form-message"></div>
            </form>
        </div>

        <script>
            jQuery(document).ready(function($) {
                $('#feedback-form').on('submit', function(e) {
                    e.preventDefault();

                    var formData = new FormData(this);
                    formData.append('action', 'submit_feedback');

                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                $('.form-message').html('<p style="color: green;">' + response.data + '</p>');
                                $('#feedback-form')[0].reset();
                            } else {
                                $('.form-message').html('<p style="color: red;">' + response.data + '</p>');
                            }
                        }
                    });
                });
            });
        </script>
<?php
        return ob_get_clean();
    }

    public static function handle_registration()
    {
        if (!wp_verify_nonce($_POST['registration_nonce'], 'registration_nonce')) {
            wp_die('Neispravna sigurnosna provera');
        }

        $data = $_POST;

        $result = Survey_Database::save_participant($data);

        if ($result) {
            // Pošalji email
            $admin_email = get_option('admin_email');
            $subject = 'Nova registracija polaznika kursa';
            $message = "Nova registracija polaznika:\n\n";
            $message .= "Ime: " . $data['first_name'] . " " . $data['last_name'] . "\n";
            $message .= "Kompanija: " . $data['company'] . "\n";
            $message .= "Email: " . $data['email'] . "\n";
            $message .= "Kurs: " . $data['course_name'] . "\n";

            wp_mail($admin_email, $subject, $message);

            wp_send_json_success('Uspešno ste se registrovali!');
        } else {
            wp_send_json_error('Greška prilikom registracije. Pokušajte ponovo.');
        }
    }

    public static function handle_feedback()
    {
        if (!wp_verify_nonce($_POST['feedback_nonce'], 'feedback_nonce')) {
            wp_die('Neispravna sigurnosna provera');
        }

        $data = $_POST;

        $result = Survey_Database::save_feedback($data);

        if ($result) {
            // Pošalji email
            $admin_email = get_option('admin_email');
            $subject = 'Novi feedback o kursu';
            $message = "Novi feedback o kursu:\n\n";
            $message .= "Ocena kvaliteta predavanja: " . $data['lecture_quality'] . "/5\n";
            $message .= "Ocena kvaliteta predavača: " . $data['lecturer_quality'] . "/5\n";
            $message .= "Kurs ispunio očekivanja: " . $data['expectations_met'] . "\n";

            wp_mail($admin_email, $subject, $message);

            wp_send_json_success('Hvala na povratnim informacijama!');
        } else {
            wp_send_json_error('Greška prilikom slanja. Pokušajte ponovo.');
        }
    }
}
