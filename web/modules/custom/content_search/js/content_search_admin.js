(function($) {
    $(document).ready(function() {
        $('#edit-reindex').click(function(e) {
            e.preventDefault();
            $(this).trigger('send');

            if (!$(this).hasClass('indexing')) {
                $(this).addClass('indexing');
                $('#currently-indexed').html('0');
                $('[data-field="progress"]').removeClass('hidden');
            }
        });

        $('#edit-reindex').bind('continue', function(e) {
            $('#edit-reindex').trigger('disable');
            setTimeout(function() {
                $('#edit-reindex').trigger('click');
            }, 100);
        });

        $('#edit-reindex').bind('disable', function(e) {
            $('#edit-reindex').attr('disabled', 'disabled');
        });
    });
})(jQuery);
