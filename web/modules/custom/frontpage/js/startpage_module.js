(function($) {
    $(document).ready(function(){

        var slider = $(".slider-block.notices-slider .slider-holder");
        if ( slider.length > 0 ) {

            function checkSliderWidth() {
                var window_width = $(window).width();
                if ( window_width < 1180 ) {
                    slider.width(window_width);
                } else {
                    slider.width("auto");
                }
            }

            $(window).on("resize", function(){
                checkSliderWidth();
            });

            checkSliderWidth();

            slider.on('init', function(slick) {
                setTimeout(function(){
                    slider.parents(".slider-block").removeClass("loading");
                    $(window).trigger("resize"); // trigger slick reponsive changes if any
                }, 500);
            });

            slider.slick({
                arrows: false,
                dots: false,
                vertical: false,
                slidesToShow: 3,
                slidesToScroll: 1,
                speed: 200,
                fade: false,
                cssEase: 'ease-in-out',
                adaptiveHeight: true,
                autoplay: true,
                autoplaySpeed: 4000,
				responsive: [
					{
						breakpoint: 1180,
						settings: {
							slidesToShow: 2
						}
					},
					{
						breakpoint: 768,
						settings: {
							slidesToShow: 1
						}
					}
				]
            });
        }

        var slider_headlines = $(".slider-block.headlines-slider .slider-holder");
        if ( slider_headlines.length > 0 ) {

            function checkSliderHeadlinesWidth() {
                var ratio = 650/1920;
                var window_width = $(window).width();
                slider_headlines.width(window_width);
                slider_headlines.parents(".headline-block").height(window_width*ratio);
            }

            $(window).on("resize", function(){
                checkSliderHeadlinesWidth();
            });

            checkSliderHeadlinesWidth();

            slider_headlines.on('init', function(slick) {
                setTimeout(function(){
                    slider_headlines.parents(".slider-block").removeClass("loading");
                    $(window).trigger("resize"); // trigger slick reponsive changes if any
                }, 500);
            });

            slider_headlines.slick({
                arrows: true,
                dots: false,
                vertical: false,
                slidesToShow: 1,
                slidesToScroll: 1,
                speed: 200,
                fade: true,
                cssEase: 'ease-in-out',
                adaptiveHeight: false,
                autoplay: true,
                autoplaySpeed: 4000,
                responsive: [
                    {
                        breakpoint: 768,
                        settings: {
                            adaptiveHeight: true
                        }
                    }
                ]
            });
        }

    });
})(jQuery);
