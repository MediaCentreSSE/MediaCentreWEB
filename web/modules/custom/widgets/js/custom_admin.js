(function($) {
    $(document).ready(function() {
        $('#edit-field-inherit-widgets-value').change(function() {
            $('#field-inherited-widgets-list').css('display', 'none');

            if ($(this).prop('checked')) {
                $('#field-inherited-widgets-list').css('display', 'block');
            }
        }).trigger('change');
    });
})(jQuery);
