(function($) {
    $(document).ready(function() {
        var time_from = $('#edit-field-time-from-0-value');
        var time_to = $('#edit-field-time-to-0-value');

        $('#edit-submit').click(function(event) {
            if (!validTimeField(time_from) || !validTimeField(time_to)) {
                event.preventDefault();
            }
        });
    });

    function validTimeField($field) {
        $field.removeClass('error');
        var value = $field.val();

        if (!value) {
            return true;
        }

        var regex = new RegExp(/^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/);
        if (!regex.test(value)) {
            $field.addClass('error');

            var top = $field.position().top;
            $(window).scrollTop(top - 200);

            return false;
        }

        return true;
    }
})(jQuery);
