(function($) {
    $(document).ready(function() {
        $('body').on('change', '.field--name-field-icon-type select', function() {
            val = $(this).val();
            context = $(this).parents('.paragraphs-subform');

            $('.field--name-field-icon-svg', context).css('display', 'none');
            $('.field--name-field-image', context).css('display', 'none');

            if (val == 'svg') {
                $('.field--name-field-icon-svg', context).css('display', 'block');
            }
            if (val == 'image') {
                $('.field--name-field-image', context).css('display', 'block');
            }
        });

        $('.field--name-field-icon-type select').trigger('change');
    });
})(jQuery);
