(function($) {
    $(document).ready(function(){

        // submit form listener
        $(".events-list-wrapper .submit-form").on("click", function(e){
            e.preventDefault();
            var inputs = $(".events-list-wrapper .inputblock.required:not(.hidden)").find("input[type='text'], input[type='password'], input[type='email'], select, textarea");

            // reset invalid state
            inputs.parents(".inputblock").removeClass("invalid");
            $(".inputblock-message").remove();

            // validate inputs
            var form_valid = areTheseInputsValid(inputs);
            if ( form_valid ) {
                $(this).parents("form").submit();
            }
        });

    });
})(jQuery);
