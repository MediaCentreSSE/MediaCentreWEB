(function($) {
    $(document).ready(function(){
        var context = '#custom-contact-form';

        $('[data-field="submit"] input', context).on('click', function(e){
            $(this).parent().addClass('loading');
        });

        $('.datefield', context).datepicker({
            dateFormat: 'dd.mm.yy'
        });
    });
})(jQuery);
