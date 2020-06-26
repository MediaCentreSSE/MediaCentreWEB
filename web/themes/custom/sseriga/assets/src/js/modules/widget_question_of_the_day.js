(function($) {
    $(document).ready(function(){

        // submit form listener
        $(".question-of-the-day .submit-form").on("click", function(e){
            e.preventDefault();
            var inputs = $(".question-of-the-day .inputblock:not(.hidden)").find("input");
            // validate inputs
            var form_valid = false;
            inputs.each(function(){
                if ( areTheseInputsValid($(this)) ) {
                    form_valid = true;
                }
            });
            if ( form_valid ) {
               $(this).parents("form").submit();
            }
        });

    });
})(jQuery);
