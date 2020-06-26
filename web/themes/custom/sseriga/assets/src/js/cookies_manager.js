(function($) {

	var user_gave_consent = Cookies.get("user_gave_consent");

	// show cookie notification
	if ( !user_gave_consent ) {
		$(".cookie-notification-block").addClass("active");

		// globally block cookie creation
		if(!document.__defineGetter__) {
			Object.defineProperty(document, 'cookie', {
				get: function(){return ''},
				set: function(){return true},
			});
		} else {
			document.__defineGetter__("cookie", function() { return '';} );
			document.__defineSetter__("cookie", function() {} );
		}
	}

	$(document).ready(function(){

		// cookie notification close listener
		$(".cookie-notification-block .button-close").on("click", function(){
			$(".cookie-notification-block").removeClass("active");
		});

		$(".cookie-notification-block .button-give-consent").on("click", function(){
			$(".cookie-notification-block").removeClass("active");
			delete document.cookie; // this resets native getter/setter, allowing cookies to be created
			Cookies.set("user_gave_consent", 1, { expires: 90 });
			// reload the page, so cookies can load now
			window.location.reload();
		});


		// blocks all iframes from loading, if user hasn't accepted cookie consent
		if ( user_gave_consent != 1 ) {
			var iframe_tags = $('iframe');
			for ( var i=0; i < iframe_tags.length; i++ ) {
				$(iframe_tags[i]).remove(); // works on all
				//iframe_tags[i].src = "about:blank"; // ff, edge, ie
				//iframe_tags[i].contentWindow.stop(); // works only on ff
			}
		}

		// get & load script tags only if user consent is given
		if ( user_gave_consent == 1 ) {
			var script_tags = $('script[data-src]');
			for ( var i=0; i < script_tags.length; i++ ) {
				var src_data = $(script_tags[i]).attr("data-src");
				// load script
				// use cachedScript instead of $.ajax({ url: src_data, dataType: "script"});
				$.cachedScript(src_data).done(function( script, textStatus ) {

				});
			}
		}

	});

	$.cachedScript = function( url, options ) {
	  // Allow user to set any option except for dataType, cache, and url
	  options = $.extend( options || {}, {
		dataType: "script",
		cache: true,
		url: url
	  });
	  // Use $.ajax() since it is more flexible than $.getScript
	  // Return the jqXHR object so we can chain callbacks
	  return $.ajax( options );
	};

})(jQuery);
