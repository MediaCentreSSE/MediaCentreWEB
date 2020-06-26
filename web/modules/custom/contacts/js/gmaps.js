/* Contacts google maps */
(function($) {

    $(document).ready(function(){
        // first check if we even need to load it, since we won't be needing it on mobile view
        var gmaps_loaded = false;

        $(window).on("resize", function(){
            checkIfGmapsIsLoaded();
        });

        checkIfGmapsIsLoaded();

        function checkIfGmapsIsLoaded() {
            if ( !gmaps_loaded ) {
                gmaps_loaded = true;
                loadGmaps();
            }
        }

        function loadGmaps() {
            var script = document.createElement("script");
            script.setAttribute("src", "https://maps.googleapis.com/maps/api/js?key=AIzaSyBGnnDPhpT65BBGod7Po9TMVcmSZpWrbaM&callback=initMap");
            script.setAttribute("defer", "defer");
            script.setAttribute("async", "");
            document.head.appendChild(script);
        }
    });

})(jQuery);

var map;
var markers;

function initMap() {
    // setup brains
    map = new google.maps.Map(jQuery(".map-block .map-holder .map")[0], {
        zoom: 14
    });

    // trigger resize because it was under display: none;
    google.maps.event.trigger(map, 'resize');

    generateMarkers();

    // if ( jQuery(window).width() >= 768 ) {
    //     map.panBy(155, 0);
    // }

    setTimeout(function(){
        //new google.maps.event.trigger(markers[0], 'click');
        setTimeout(function(){
            jQuery(".map-block .map-holder .map").removeClass("loading");
        }, 300);
    }, 100);
}

function generateMarkers() {
    // first remove all the old listeners by removing the old markers
    if ( markers ) {
        for ( var i=0; i < markers.length; i++ ) {
            markers[i].setMap(null);
        }
    }

    markers = [];

    var marker_data_holder = jQuery(".map-holder .markers .marker");
    var bounds = new google.maps.LatLngBounds();

    // iterate through all markers and add them to the map
    for ( var i=0; i < marker_data_holder.length; i++ ) {
        // create marker and its listeners
        var marker = createMarker(marker_data_holder[i]);
        markers.push(marker);

        var marker_lat = jQuery(marker_data_holder[i]).data("lat");
        var marker_lng = jQuery(marker_data_holder[i]).data("lng");

        // add marker location in bounds
        bounds.extend(new google.maps.LatLng(marker_lat, marker_lng));
    }

    // adjust zoom if there was only 1 item
    if ( marker_data_holder.length <= 1 ) {
        map.setCenter(new google.maps.LatLng(marker_lat, marker_lng));
    } else {
        map.fitBounds(bounds);
    }
}

function createMarker(marker_data_holder) {
    // set markers infowindow and bind it to the marker
    var contentString = jQuery(".map-block .map-holder .map-content[data-marker='" + jQuery(marker_data_holder).data('marker') + "']").html();

    var infowindow = new google.maps.InfoWindow({
        content: contentString,
        pixelOffset: new google.maps.Size(180, 170)
    });

    var marker_lat = jQuery(marker_data_holder).data("lat");
    var marker_lng = jQuery(marker_data_holder).data("lng");

    var icon = {
        url: '/themes/custom/sseriga/assets/images/ic_marker_fill_gold.svg',
        size: new google.maps.Size(50, 70),
        origin: new google.maps.Point(0, 0),
        anchor: new google.maps.Point(0, 70)
    };

    var marker = new google.maps.Marker({
        position: {
            lat: marker_lat,
            lng: marker_lng
        },
        map: map,
        icon: icon,
        infowindow: infowindow
    });

    // set marker click listener
    google.maps.event.addListener(marker, 'click', function() {
        marker.infowindow.open(map, marker);
    });

    // set infowindow close listener
    google.maps.event.addListener(marker.infowindow, 'closeclick',function(){

    });

    return marker;
}
