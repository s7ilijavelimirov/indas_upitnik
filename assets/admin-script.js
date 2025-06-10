jQuery(document).ready(function ($) {

    // Handle feedback details modal
    $('.view-details').on('click', function () {
        var id = $(this).data('id');

        // Show loading
        $('#feedback-details').html('<div class="loading-indicator"></div><p>Učitava detalje...</p>');
        $('#feedback-modal').show();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_feedback_details',
                id: id
            },
            success: function (response) {
                $('#feedback-details').html(response);
            },
            error: function () {
                $('#feedback-details').html('<p>Greška pri učitavanju detalja.</p>');
            }
        });
    });

    // Close modal
    $('.close, #feedback-modal').on('click', function (e) {
        if (e.target === this) {
            $('#feedback-modal').hide();
        }
    });

    // Prevent modal content clicks from closing modal
    $('.feedback-modal-content').on('click', function (e) {
        e.stopPropagation();
    });

    // ESC key to close modal
    $(document).on('keydown', function (e) {
        if (e.keyCode === 27) { // ESC key
            $('#feedback-modal').hide();
        }
    });

    // Confirm before CSV export
    $('a[href*="export=csv"]').on('click', function (e) {
        if (!confirm('Da li ste sigurni da želite da izvezete podatke u CSV format?')) {
            e.preventDefault();
        }
    });

    // Auto-refresh dashboard statistics every 30 seconds
    if ($('.dashboard-widgets-wrap').length > 0) {
        setInterval(function () {
            // Only refresh if user is active (to save resources)
            if (document.hasFocus()) {
                location.reload();
            }
        }, 60000); // Changed to 60 seconds
    }

    // Statistics page enhancements
    if ($('.statistics-container').length > 0) {
        // Animate rating bars on scroll
        $(window).on('scroll', function () {
            $('.rating-fill, .rating-dist-fill, .timeline-bar').each(function () {
                var elementTop = $(this).offset().top;
                var elementBottom = elementTop + $(this).outerHeight();
                var viewportTop = $(window).scrollTop();
                var viewportBottom = viewportTop + $(window).height();

                if (elementBottom > viewportTop && elementTop < viewportBottom) {
                    $(this).addClass('animate');
                }
            });
        });

        // Trigger initial animation
        $(window).trigger('scroll');
    }

    // Highlight new entries (if any)
    $('.wp-list-table tbody tr').each(function (index) {
        if (index < 3) { // Highlight first 3 entries as "recent"
            $(this).addClass('recent-entry');
        }
    });

    // Add some visual improvements
    $('.wp-list-table').addClass('survey-table-enhanced');

});