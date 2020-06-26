// GLOBALY ACCESSIBLE FUNCTIONS ----------------------------------------------------------------------------------
var bindPopupCloseListener;
var unbindPopupCloseListener;
var areTheseInputsValid;
var scrollToElement;
var preloadImage;
// ---------------------------------------------------------------------------------------------------------------
(function($) {
	// popup binders
	bindPopupCloseListener = function (popup_element, clickable_element, callback_function) {
		$("body").on("click.popup-wrapper", function(e){
			if ( e.target !== clickable_element[0] && !clickable_element[0].contains(e.target) ) {
				unbindPopupCloseListener();
				popup_element.removeClass("active");
				if ( typeof callback_function != "undefined" ) {
					callback_function();
				}
			}
		});
	}

	unbindPopupCloseListener = function () {
		$("body").off("click.popup-wrapper");
	}

	areTheseInputsValid = function (inputs) {
		var form_valid = true;

		inputs.each(function(){
			var input = $(this);
			try {
				// check if input is checkbox or radio (we can't get value from it, chrome returns it as "on")
				switch ( input.attr("type") ) {
					case "checkbox":
						if ( !$("#" + input.attr("id") + ":checked").length > 0 ) {
							throw true;
						}
					break;
					case "radio":
						if ( !$("#" + input.attr("id") + ":checked").length > 0 ) {
							throw true;
						}
					break;
					default:
						// check if inputs value exists (select elements might not have any option selected)
						if ( input.val() ) {
							// value exists, check its length
							if ( input.val().length <= 0 ) {
								// length invalid
								throw true;
							}
						} else {
							// value does not exist, throw error
							throw true;
						}
				}
			}
			catch(err) {
				form_valid = false;
				input.parents(".inputblock").addClass("invalid");
			}
		});

		return form_valid;
	}


	scrollToElement = function(element) {
		var offset = 0;
		if ( $(window).width() < 768 ) {
			offset = 40;
		}
		// checkbox has 0 offset, because it is not shown, scroll to next element instead (checkboxes label)
		switch(element.attr("type")) {
			case "checkbox":
				$('html, body').stop().animate({scrollTop: element.next().offset().top - 20 - offset}, 1000);
			break;
			default:
				$('html, body').stop().animate({scrollTop: element.offset().top - 20 - offset}, 1000);
		}
	}

	preloadImage = function(src_path, callback) {
		var tmpImg = new Image();
		tmpImg.src = src_path;
		tmpImg.onload = function() {
			callback();
		};
	};

	$(document).ready(function(){

		// search
		$(".search-wrapper .toggle").on("click", function(){
			$(this).parent().toggleClass("active");
			if ( $(".menu-wrapper").hasClass("moved") ) {
				$(".menu-wrapper").removeClass("timed-void");
				setTimeout(function(){
					$(".menu-wrapper").removeClass("moved");
				}, 100);
			} else {
				$(".menu-wrapper").addClass("moved");
				setTimeout(function(){
					$(".menu-wrapper").addClass("timed-void");
				}, 200);
			}
		});

		// mobile menu button
		$(".menu-wrapper .menu-mobile-button").on("click", function(){
			if ( $(this).parent().hasClass("active") ) {
				$(this).parent().removeClass("active");
				$("body").removeClass("overflow-hidden");
			} else {
				$(this).parent().addClass("active");
				$("body").addClass("overflow-hidden");

				// bind popup closer
				var popup_element = $(".menu-inner-wrapper");
				var clickable_element = $(".menu-inner-wrapper .menu-holder");
				// this prevents bind click execution on this button's click
				setTimeout(function(){
					bindPopupCloseListener(popup_element, clickable_element, function(){
						$(".menu-wrapper .menu-mobile-close").trigger("click");
					});
				},1);
			}
		});

		// mobile menu close button
		$(".menu-wrapper .menu-mobile-close").on("click", function(){
			unbindPopupCloseListener();
			$(this).parents(".menu-wrapper").removeClass("active");
			$("body").removeClass("overflow-hidden");
		});

		// mobile menu link listener
		$(".menu-wrapper .menu-item a").on("click", function(e){
			// check if there is submenu
			if ( $(this).next().hasClass("submenu-wrapper") ) {
				if ( $(e.target).hasClass("submenu-toggler") ) {
					e.preventDefault();
					if ( $(this).parent().hasClass("active") ) {
						$(this).parent().removeClass("active");
					} else {
						$(this).parent().addClass("active");
					}
				} else if ( !$(this).attr("href") ) {
					if ( $(this).parent().hasClass("active") ) {
						$(this).parent().removeClass("active");
					} else {
						$(this).parent().addClass("active");
					}
				}
			}
		});

		// accordeon listener
		$(".accordeon-block .accordeon-item .accordeon-title").on("click", function(e){
			e.preventDefault();
			if ( $(this).parent().hasClass("active") ) {
				$(this).parent().removeClass("active");
			} else {
				$(".accordeon-block .accordeon-item").removeClass("active");
				$(this).parent().addClass("active");

				scrollToElement($(this));
			}
		});

		//	make responsive tables/any table in text-style element, work fine in responsive mode
		$(".responsive-table, .text-style table").each(function(){
			var current_table = $(this);
			var wrapped_table = '<div class="responsive-table-wrapper">' + this.outerHTML + '</div>';
			this.outerHTML = wrapped_table;
		});

		function resizeResponsiveTables() {
			var screen_width = $(window).width();
			if ( screen_width < 992 ) {
				$(".responsive-table-wrapper").each(function(){
					$(this).width(screen_width - 40);
				});
			} else if ( screen_width < 1180 ) {
				$(".responsive-table-wrapper").each(function(){
					$(this).width(screen_width - 390);
				});
			} else {
				$(".responsive-table-wrapper").each(function(){
					$(this).width("auto");
				});
			}
		}

		$(window).on("resize", function() {
			resizeResponsiveTables();
		});

		resizeResponsiveTables();

		// make sure content iframes resize correctly in responsive
		$(".text-style iframe").each(function(){
			// set their original width as an attribute so we can access it later
			$(this).attr("original-width", $(this).width());
		});

		function resizeResponsiveIframes() {
			var screen_width = $(window).width();
			if ( screen_width < 992 ) {
				$(".text-style iframe").each(function(){
					// only adjust width if our available space is less than its original width
					var original_width = $(this).attr("original-width");
					var available_space = screen_width - 40;
					if ( original_width > available_space ) {
						$(this).width(available_space)
					} else {
						$(this).width($(this).attr("original-width"));
					}
				});
			} else {
				$(".text-style iframe").each(function(){
					$(this).width($(this).attr("original-width"));
				});
			}
		}

		$(window).on("resize", function() {
			resizeResponsiveIframes();
		});

		resizeResponsiveIframes();

		// update submenu position
		function updateSubmenuPosition() {
			$(".menu-item .submenu").each(function(){
				var submenu_width = $(this).outerWidth();
				var item_left_offset = $(this).parents(".menu-item").offset().left;
				var available_space = $(window).width() - item_left_offset;
				if ( submenu_width > available_space ) {
					var overflow = submenu_width - available_space;
					$(this).parent().css("left", -overflow);
				} else {
					$(this).parent().css("left", 0);
				}
			});
		}

		$(window).on("resize", function() {
			updateSubmenuPosition();
		});

		updateSubmenuPosition();

	});
})(jQuery);
