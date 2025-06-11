jQuery(document).ready(function ($) {

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

    // Radio group interaction enhancement
    $('.radio-group input[type="radio"]').on('change', function () {
        var $group = $(this).closest('.radio-group');
        $group.find('label').removeClass('selected');
        $(this).closest('label').addClass('selected');
    });

    // Form validation enhancement
    $('input[required], textarea[required]').on('blur', function () {
        validateField($(this));
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

        $(this).siblings('.error-text').remove();

        if (cleanPhone.length > 0 && cleanPhone.length < 6) {
            $(this).addClass('error');
            $(this).after('<span class="error-text">Telefon mora imati najmanje 6 cifara</span>');
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

    // Field validation function
    function validateField($field) {
        var isValid = true;
        var value = $field.val().trim();
        var $form = $field.closest('form');

        $field.siblings('.error-text').remove();
        $field.removeClass('error');

        // Required field validation
        if ($field.prop('required') && value === '') {
            $field.addClass('error');
            var errorText = $form.data('field-required') || 'Ovo polje je obavezno, molimo vas popunite';
            $field.after('<span class="error-text">' + errorText + '</span>');
            isValid = false;
        }

        // Email validation
        if ($field.attr('type') === 'email' && value !== '') {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                $field.addClass('error');
                $field.after('<span class="error-text">Molimo unesite ispravnu email adresu</span>');
                isValid = false;
            }
        }

        // Phone validation
        if ($field.attr('type') === 'tel' && value !== '') {
            var cleanPhone = value.replace(/\D/g, '');
            if (cleanPhone.length < 6) {
                $field.addClass('error');
                $field.after('<span class="error-text">Telefon mora imati najmanje 6 cifara</span>');
                isValid = false;
            }
        }

        // Name validation
        if ($field.attr('name') === 'participant_name' && value !== '') {
            if (value.length < 2) {
                $field.addClass('error');
                $field.after('<span class="error-text">Ime mora imati najmanje 2 karaktera</span>');
                isValid = false;
            }
        }

        return isValid;
    }

    // Form validation function
    function validateForm($form) {
        var isValid = true;
        var firstInvalidField = null;

        // Validate all required fields
        $form.find('input[required], textarea[required]').each(function () {
            if (!validateField($(this))) {
                isValid = false;
                if (!firstInvalidField) {
                    firstInvalidField = $(this);
                }
            }
        });

        // Validate required radio groups
        var radioGroups = {};
        $form.find('input[type="radio"][required]').each(function () {
            var name = $(this).attr('name');
            if (!radioGroups[name]) {
                radioGroups[name] = $(this).closest('.form-row');
            }
        });

        $.each(radioGroups, function (name, $row) {
            if ($form.find('input[name="' + name + '"]:checked').length === 0) {
                $row.find('.error-text').remove();
                var errorText = $form.data('choose-option') || 'Molimo izaberite jednu opciju';
                $row.append('<span class="error-text">' + errorText + '</span>');
                isValid = false;
                if (!firstInvalidField) {
                    firstInvalidField = $row.find('input').first();
                }
            } else {
                $row.find('.error-text').remove();
            }
        });

        // Show general error message if form is invalid
        if (!isValid) {
            var $message = $form.find('.form-message');
            var generalError = $form.data('fill-required') || 'Molimo popunite sva obavezna polja!';
            $message.html('<div class="error-message">' + generalError + '</div>');
            scrollToMessage($message);

            // Scroll to first invalid field
            if (firstInvalidField) {
                setTimeout(function () {
                    $('html, body').animate({
                        scrollTop: firstInvalidField.offset().top - 150
                    }, 500);
                }, 100);
            }
        }

        return isValid;
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
        var countedRadioGroups = [];

        $form.find('input[required], textarea[required], select[required]').each(function () {
            if ($(this).attr('type') === 'radio') {
                var name = $(this).attr('name');
                if (countedRadioGroups.indexOf(name) === -1) {
                    if ($form.find('input[name="' + name + '"]:checked').length > 0) {
                        filledFields++;
                    }
                    countedRadioGroups.push(name);
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

    $('form').on('submit', function (e) {
        e.preventDefault();

        var formId = $(this).attr('id');
        if (submittedForms.includes(formId)) {
            return false;
        }

        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var $message = $form.find('.form-message');

        // Validate form before submission
        if (!validateForm($form)) {
            return false;
        }

        // Add to submitted forms to prevent double submission
        submittedForms.push(formId);

        // Disable submit button and show loading
        $submitBtn.prop('disabled', true);
        $form.addClass('loading');
        $message.html('');

        var formData = new FormData(this);

        // Determine which action to use
        var action = '';
        if (formId === 'registration-form') {
            action = 'submit_registration';
        } else if (formId === 'feedback-form') {
            action = 'submit_feedback';
        }

        formData.append('action', action);

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
                    // Sakrij sve osim naslova
                    $form.addClass('success-state');

                    // Određi ispravnu poruku na osnovu jezika
                    var $langInput = $form.find('input[name="language"]');
                    var lang = $langInput.length ? $langInput.val() : 'sr';

                    var successMessage, successSubtext;

                    if (formId === 'registration-form') {
                        successMessage = lang === 'sr' ? 'Registracija uspešna!' : 'Registration successful!';
                        successSubtext = lang === 'sr' ? 'Uspešno ste se registrovali!' : 'You have successfully registered!';
                    } else {
                        successMessage = lang === 'sr' ? 'Hvala na feedback-u!' : 'Thank you for feedback!';
                        successSubtext = lang === 'sr' ? 'Hvala na povratnim informacijama!' : 'Thank you for your feedback!';
                    }

                    var successHtml = `
        <div class="success-animation">
            <div class="success-checkmark"></div>
            <div class="success-text">${successMessage}</div>
            <div class="success-subtext">${successSubtext}</div>
        </div>
    `;

                    $form.append(successHtml);

                    // Prikaži animaciju
                    setTimeout(function () {
                        $form.find('.success-animation').addClass('show');
                    }, 200);

                    // Test mode reset
                    if (typeof survey_test_mode !== 'undefined' && survey_test_mode) {
                        setTimeout(function () {
                            $form.removeClass('success-state');
                            $form.find('.success-animation').remove();
                            $form[0].reset();
                            $form.find('.selected').removeClass('selected');
                            $form.find('.error').removeClass('error');
                            $form.find('.error-text').remove();
                            updateProgressIndicator($form);
                        }, 6000);
                    }

                } else {
                    $message.html('<div class="error-message">' + response.data + '</div>');
                    scrollToMessage($message);
                }
            },
            error: function (xhr, status, error) {
                $form.removeClass('loading');
                $submitBtn.prop('disabled', false);

                var errorMessage = 'Greška prilikom slanja. Molimo pokušajte ponovo.';
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                }

                $message.html('<div class="error-message">' + errorMessage + '</div>');
                scrollToMessage($message);
            },
            complete: function () {
                // Remove from submitted forms array after 5 seconds to allow resubmission if needed
                setTimeout(function () {
                    var index = submittedForms.indexOf(formId);
                    if (index > -1) {
                        submittedForms.splice(index, 1);
                    }
                }, 5000);
            }
        });
    });

    // Handle rating group clicks better
    $('.rating-group label').on('click', function (e) {
        var $input = $(this).find('input[type="radio"]');
        if ($input.length) {
            $input.prop('checked', true).trigger('change');

            // Visual feedback
            $(this).closest('.rating-group').find('label').removeClass('selected');
            $(this).addClass('selected');

            // Remove any validation errors
            $(this).closest('.form-row').find('.error-text').remove();
        }
    });

    // Handle radio group clicks better
    $('.radio-group label').on('click', function (e) {
        var $input = $(this).find('input[type="radio"]');
        if ($input.length) {
            $input.prop('checked', true).trigger('change');

            // Visual feedback
            $(this).closest('.radio-group').find('label').removeClass('selected');
            $(this).addClass('selected');

            // Remove any validation errors
            $(this).closest('.form-row').find('.error-text').remove();
        }
    });

    // Better form reset handling
    $('form').on('reset', function () {
        var $form = $(this);
        setTimeout(function () {
            $form.find('.error').removeClass('error');
            $form.find('.error-text').remove();
            $form.find('.selected').removeClass('selected');
            $form.find('.success-animation').remove();
            $form.removeClass('loading');
            updateProgressIndicator($form);
        }, 10);
    });

    // Form field focus improvements
    $('input, textarea').on('focus', function () {
        $(this).closest('.form-row').addClass('focused');
    }).on('blur', function () {
        $(this).closest('.form-row').removeClass('focused');
    });

    // Keyboard navigation improvements
    $(document).on('keydown', function (e) {
        // Enter key on radio/rating groups
        if (e.keyCode === 13) {
            var $focused = $(':focus');
            if ($focused.is('input[type="radio"]')) {
                $focused.prop('checked', true).trigger('change');
                e.preventDefault();
            }
        }

        // Arrow key navigation for radio groups
        if (e.keyCode === 37 || e.keyCode === 39) { // Left/Right arrows
            var $focused = $(':focus');
            if ($focused.is('input[type="radio"]')) {
                var $group = $focused.closest('.rating-group, .radio-group');
                var $radios = $group.find('input[type="radio"]');
                var currentIndex = $radios.index($focused);
                var nextIndex;

                if (e.keyCode === 37) { // Left arrow
                    nextIndex = currentIndex > 0 ? currentIndex - 1 : $radios.length - 1;
                } else { // Right arrow
                    nextIndex = currentIndex < $radios.length - 1 ? currentIndex + 1 : 0;
                }

                $radios.eq(nextIndex).focus().prop('checked', true).trigger('change');
                e.preventDefault();
            }
        }
    });

    // Auto-save functionality (optional)
    var autoSaveTimer;
    $('input, textarea').on('input change', function () {
        var $form = $(this).closest('form');
        clearTimeout(autoSaveTimer);

        autoSaveTimer = setTimeout(function () {
            // Save form data to sessionStorage
            var formData = {};
            $form.find('input, textarea').each(function () {
                var $field = $(this);
                if ($field.attr('type') === 'radio') {
                    if ($field.is(':checked')) {
                        formData[$field.attr('name')] = $field.val();
                    }
                } else if ($field.attr('type') !== 'hidden') {
                    formData[$field.attr('name')] = $field.val();
                }
            });

            sessionStorage.setItem('survey_' + $form.attr('id'), JSON.stringify(formData));
        }, 1000);
    });

    // Restore form data on page load
    $('form').each(function () {
        var $form = $(this);
        var savedData = sessionStorage.getItem('survey_' + $form.attr('id'));

        if (savedData) {
            try {
                var data = JSON.parse(savedData);
                $.each(data, function (name, value) {
                    var $field = $form.find('[name="' + name + '"]');
                    if ($field.attr('type') === 'radio') {
                        $field.filter('[value="' + value + '"]').prop('checked', true).trigger('change');
                    } else {
                        $field.val(value);
                    }
                });
                updateProgressIndicator($form);
            } catch (e) {
                // Invalid saved data, ignore
            }
        }
    });

    // Clear saved data after successful submission
    $(document).on('survey_success', function (e, formId) {
        sessionStorage.removeItem('survey_' + formId);
    });

});