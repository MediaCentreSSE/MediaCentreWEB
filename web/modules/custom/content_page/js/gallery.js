(function($) {
    $(document).ready(function(){

        var title_image_element = $(".article-gallery-block .title-image");
        var gallery_image_element = $(".popup.gallery-popup .popup-content-inner .image-wrapper img");
        var current_active_popup_image_index;
        var total = $(".article-gallery-block .title-image .gallery-items .gallery-item").length;

        // open gallery popup listener
        $(".open-gallery-popup").on("click", function(e){
            e.preventDefault();
            var popup_element = $(".gallery-popup");
            var clickable_element = $(".image-wrapper", popup_element);

            popup_element.addClass("active");
            $("body").addClass("overflow-hidden");

            // this prevents bind click execution on this button's click
            setTimeout(function(){
                bindPopupCloseListener(popup_element, clickable_element, function(){
                    $("body").removeClass("overflow-hidden");
                });
            },1);

            // load first image in popup gallery
            current_active_popup_image_index = 1;
            var image_data_holder = $(".article-gallery-block .title-image .gallery-items .gallery-item:nth-child(" + current_active_popup_image_index + ")");
            loadAndApplyImage(image_data_holder.data("src"), "src", gallery_image_element, gallery_image_element.parent());

            // update index info
            updateIndexInfo();
        });

        // prev button listener
        $(".gallery-popup .gallery-image-prev").on("click", function(){
            var image_data_holder = $(".article-gallery-block .title-image .gallery-items .gallery-item:nth-child(" + (current_active_popup_image_index-1) + ")");
            // if prev item exists
            if ( image_data_holder.length > 0 ) {
                // go to prev item
                current_active_popup_image_index--;
                loadAndApplyImage(image_data_holder.data("src"), "src", gallery_image_element, gallery_image_element.parent());
            } else {
                // go to last item
                var image_data_holder = $(".article-gallery-block .title-image .gallery-items .gallery-item:last-child");
                current_active_popup_image_index = image_data_holder.index() + 1;
                loadAndApplyImage(image_data_holder.data("src"), "src", gallery_image_element, gallery_image_element.parent());
            }
        });

        // next button listener
        $(".gallery-popup .gallery-image-next").on("click", function(){
            var image_data_holder = $(".article-gallery-block .title-image .gallery-items .gallery-item:nth-child(" + (current_active_popup_image_index+1) + ")");
            // if next item exists
            if ( image_data_holder.length > 0 ) {
                // go to next item
                current_active_popup_image_index++;
                loadAndApplyImage(image_data_holder.data("src"), "src", gallery_image_element, gallery_image_element.parent());
            } else {
                // go to 1st item
                var image_data_holder = $(".article-gallery-block .title-image .gallery-items .gallery-item:nth-child(" + 1 + ")");
                current_active_popup_image_index = 1;
                loadAndApplyImage(image_data_holder.data("src"), "src", gallery_image_element, gallery_image_element.parent());
            }
        });

        function applyPopupImageMaxHeight() {
            var max_height = $(window).height() - 57 - 57;
            gallery_image_element.css("max-height", max_height);
        }

        $(window).on("resize", function() {
            applyPopupImageMaxHeight();
        });

        applyPopupImageMaxHeight();

        // load title image
        loadAndApplyImage(title_image_element.data("src"), "style", title_image_element, title_image_element.parent());

        function loadAndApplyImage(src_path, mode, target_element, loading_element) {
            loading_element.addClass("loading");
            preloadImage(src_path, function() {
                if ( mode == "src" ) {
                    target_element.attr("src", src_path);
                } else {
                    target_element.attr("style", "background-image: url('" + src_path + "');");
                }
                loading_element.removeClass("loading");
            });

            // update image index
            updateIndexInfo();
        }

        function updateIndexInfo() {
            $(".gallery-popup .counter .current").html(current_active_popup_image_index);
            $(".gallery-popup .counter .total").html(total);
        }

    });
})(jQuery);
