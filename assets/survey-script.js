jQuery(document).ready(function ($) {

    // Handling registration form submission
    $('#registration-form').on('submit', function (e) {
        e.preventDefault();

        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var $message = $form.find('.form-message');

        // Disable submit button and show loading
        $submitBtn.prop('disabled', true).text('Šalje se...');
        $form.addClass('loading');
        $message.html('');

        var formData = new FormData(this);
        formData.append('action', 'submit_registration');

        $.ajax({
            url: survey_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                $form.removeClass('loading');
                $submitBtn.prop('disabled', false);

                if (response.success) {
                    $message.html('<div class="success-message">' + response.data + '</div>');
                    $form[0].reset();
                    $submitBtn.text('Poslano uspešno!');

                    // Reset button text after 3 seconds
                    setTimeout(function () {
                        $submitBtn.text($submitBtn.data('original-text') || 'Pošalji');
                    }, 3000);

                    // Scroll to success message
                    scrollToMessage($message);

                } else {
                    $message.html('<div class="error-message">' + response.data + '</div>');
                    $submitBtn.text('Pošalji');
                    scrollToMessage($message);
                }
            },
            error: function () {
                $form.removeClass('loading');
                $submitBtn.prop('disabled', false).text('Pošalji');
                $message.html('<div class="error-message">Greška prilikom slanja. Molimo pokušajte ponovo.</div>');
                scrollToMessage($message);
            }
        });
    });

    // Handling feedback form submission
    $('#feedback-form').on('submit', function (e) {
        e.preventDefault();

        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var $message = $form.find('.form-message');

        // Disable submit button and show loading
        $submitBtn.prop('disabled', true).text('Šalje se...');
        $form.addClass('loading');
        $message.html('');

        var formData = new FormData(this);
        formData.append('action', 'submit_feedback');

        $.ajax({
            url: survey_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                $form.removeClass('loading');
                $submitBtn.prop('disabled', false);

                if (response.success) {
                    $message.html('<div class="success-message">' + response.data + '</div>');
                    $form[0].reset();
                    $submitBtn.text('Hvala na feedback-u!');

                    // Reset button text after 3 seconds
                    setTimeout(function () {
                        $submitBtn.text($submitBtn.data('original-text') || 'Pošalji');
                    }, 3000);

                    // Scroll to success message
                    scrollToMessage($message);

                } else {
                    $message.html('<div class="error-message">' + response.data + '</div>');
                    $submitBtn.text('Pošalji');
                    scrollToMessage($message);
                }
            },
            error: function () {
                $form.removeClass('loading');
                $submitBtn.prop('disabled', false).text('Pošalji');
                $message.html('<div class="error-message">Greška prilikom slanja. Molimo pokušajte ponovo.</div>');
                scrollToMessage($message);
            }
        });
    });

    // Store original button text
    $('button[type="submit"]').each(function () {
        $(this).data('original-text', $(this).text());
    });

    // Rating interaction enhancement
    $('.rating-group input[type="radio"]').on('change', function () {
        var $group = $(this).closest('.rating-group');
        $group.find('label').removeClass('selected');
        $(this).closest('label').addClass('selected');
    });

    // Form validation enhancement
    $('input[required], textarea[required]').on('blur', function () {
        if ($(this).val().trim() === '') {
            $(this).addClass('error');
        } else {
            $(this).removeClass('error');
        }
    });

    $('input[required], textarea[required]').on('input', function () {
        if ($(this).val().trim() !== '') {
            $(this).removeClass('error');
            $(this).siblings('.error-text').remove();
        }
    });

    // Email validation
    $('input[type="email"]').on('blur', function () {
        var email = $(this).val();
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        $(this).siblings('.error-text').remove();

        if (email && !emailRegex.test(email)) {
            $(this).addClass('error');
            $(this).after('<span class="error-text">Molimo unesite ispravnu email adresu</span>');
        } else if (email) {
            $(this).removeClass('error');
        }
    });

    // Phone validation
    $('input[type="tel"]').on('input', function () {
        var phone = $(this).val();
        // Remove all non-digit characters for validation
        var cleanPhone = phone.replace(/\D/g, '');

        if (cleanPhone.length > 0 && cleanPhone.length < 6) {
            $(this).addClass('error');
        } else {
            $(this).removeClass('error');
        }
    });

    // Auto-resize textareas
    $('textarea').on('input', function () {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    // Smooth scrolling to form messages
    function scrollToMessage($message) {
        if ($message.length && $message.html().trim() !== '') {
            $('html, body').animate({
                scrollTop: $message.offset().top - 100
            }, 500);
        }
    }

    // Enhanced form completion tracking
    var formData = {};

    $('input, textarea, select').on('change input', function () {
        var $form = $(this).closest('form');
        var formId = $form.attr('id');

        if (!formData[formId]) formData[formId] = {};
        formData[formId][$(this).attr('name')] = $(this).val();

        updateProgressIndicator($form);
    });

    function updateProgressIndicator($form) {
        var totalFields = $form.find('input[required], textarea[required], select[required]').length;
        var filledFields = 0;

        $form.find('input[required], textarea[required], select[required]').each(function () {
            if ($(this).attr('type') === 'radio') {
                var name = $(this).attr('name');
                if ($form.find('input[name="' + name + '"]:checked').length > 0) {
                    filledFields++;
                }
                // Don't count each radio button individually
                $form.find('input[name="' + name + '"]').not(this).attr('data-counted', 'true');
                if ($(this).attr('data-counted') === 'true') {
                    return;
                }
            } else if ($(this).val().trim() !== '') {
                filledFields++;
            }
        });

        var progress = totalFields > 0 ? Math.round((filledFields / totalFields) * 100) : 0;

        // Update or create progress bar
        var $progressBar = $form.find('.progress-container');
        if ($progressBar.length === 0) {
            $form.prepend('<div class="progress-container"><div class="progress-bar"><div class="progress-fill"></div></div><span class="progress-text">Popunjeno: 0%</span></div>');
            $progressBar = $form.find('.progress-container');
        }

        $form.find('.progress-fill').css('width', progress + '%');
        $form.find('.progress-text').text('Popunjeno: ' + progress + '%');

        if (progress === 100) {
            $progressBar.addClass('complete');
        } else {
            $progressBar.removeClass('complete');
        }
    }

    // Initialize progress indicators
    $('form').each(function () {
        updateProgressIndicator($(this));
    });

    // Prevent double submission
    var submittedForms = [];

    $('form').on('submit', function () {
        var formId = $(this).attr('id');
        if (submittedForms.includes(formId)) {
            return false;
        }
        submittedForms.push(formId);

        // Remove from array after 5 seconds to allow resubmission if needed
        setTimeout(function () {
            var index = submittedForms.indexOf(formId);
            if (index > -1) {
                submittedForms.splice(index, 1);
            }
        }, 5000);
    });

    // Handle rating group clicks better
    $('.rating-group label').on('click', function(e) {
        var $input = $(this).find('input[type="radio"]');
        if ($input.length) {
            $input.prop('checked', true).trigger('change');
            
            // Visual feedback
            $(this).closest('.rating-group').find('label').removeClass('selected');
            $(this).addClass('selected');
        }
    });

    // Better form reset handling
    $('form').on('reset', function() {
        var $form = $(this);
        setTimeout(function() {
            $form.find('.error').removeClass('error');
            $form.find('.error-text').remove();
            $form.find('.selected').removeClass('selected');
            updateProgressIndicator($form);
        }, 10);
    });

    // Form field focus improvements
    $('input, textarea').on('focus', function() {
        $(this).closest('.form-row').addClass('focused');
    }).on('blur', function() {
        $(this).closest('.form-row').removeClass('focused');
    });

});