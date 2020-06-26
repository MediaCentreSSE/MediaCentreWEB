(function($) {
    $(document).ready(function() {
        var $filter = $('#administration-filter');
        var $list = $('#administration-list');

        $filter.bind('input', function() {
            clearFilter($list);
            applyFilter($list, $(this).val());
        });
    });

    function clearFilter($list) {
        $list.find('a').show();

        // If pretty animations required, replace previous line, with next one.
        // $list.find('.row').stop().animate({opacity: 1}, 100);
    }

    function applyFilter($list, value) {
        value = $.trim(value);
        if (value === '') {
            return;
        }

        $list.find('a').each(function() {
            // Get all words in member's name/position.
            var name_words = $(this).find('[data-id="name"]').text().split(' ');
            var position_words = $(this).find('[data-id="position"]').text().split(' ');

            // Get all searchable phrases.
            var name_phrases = getPhrases(name_words);
            var position_phrases = getPhrases(position_words);
            var phrases = name_phrases.concat(position_phrases);

            // Check if any phrase contains search value.
            for (var i = 0; i < phrases.length; i++) {
                var phrase = phrases[i].toLowerCase();

                if (phrase.startsWith(value)) {
                    return;
                }
            }

            // Hide row, if value was not found in any phrase.
            $(this).hide();

            // If pretty animations required, replace previous line, with next one.
            // $(this).stop().animate({opacity: 0.3}, 100);
        });
    }

    function getPhrases(words) {
        var phrases = [];

        for (var i = 0; i < words.length; i++) {
            var phrase = words[i].replace('(', '');
            for (var j = i + 1; j < words.length; j++) {
                var phrase = phrase + ' ' + words[j];
            }
            phrases.push(phrase);
        }

        return phrases;
    }
})(jQuery);
