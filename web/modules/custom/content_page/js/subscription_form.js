(function($) {
    $(document).ready(function(){
        var context = '#subscription-form';

        $("[data-field='subscribe']", context).on("click", function(e){
            e.preventDefault();

            $(this).prop("disabled", true).addClass("loading");
            $("#edit-subscribe", context).trigger('click');
        });
    });
})(jQuery);
