(function($) {
    $(document).ready(function(){
        $('#edit-submit-event-applicants').click(function(event) {
            event.preventDefault();

            var form = $(this).parents('form');
            form.find('input[name="action"]').val('');
            form.removeAttr('target');

            form.submit();
        });


        $('#edit-reset-event-applicants').click(function(event) {
            event.preventDefault();

            var form = $(this).parents('form');
            form.find('input[type="text"]').val('');
            form.find('select').val('All');
            form.find('input[name="action"]').val('');
            form.removeAttr('target');
            form.submit();
        });

        $('#edit-export-event-applicants').click(function(event) {
            event.preventDefault();

            var form = $(this).parents('form');
            form.attr('target', '_blank');
            form.find('input[name="action"]').val('export');
            form.submit();
        });
    });
})(jQuery);
