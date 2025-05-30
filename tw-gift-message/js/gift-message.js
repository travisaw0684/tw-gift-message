jQuery(document).ready(function($) {
    $('#gift_message').on('input', function() {
        let count = $(this).val().length;
        $('#gift-message-count').text(count);
    });
});