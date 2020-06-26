(function($) {
    $(document).ready(function() {
        var context = $('#archive-form');

        $('select', context).change(function() {
            if ($(this).attr('name') == 'year') {
                $('select[name="month"]', context).val('');
            }

            $(this).parents('form').submit();
        });
    });
})(jQuery);
