(function($) {
    $(document).ready(function() {
        var $filter = $('#faculty-filter');
        var $department = $('#faculty-department');
        var $list = $('#faculty-list');

        $filter.bind('input', function() {
            clearFilter($list, "");
            applyFilter($list, $(this).val(), "");
        });
        $department.bind('change', function() {
            applyDepartment($list, $(this).val());
            $filter.trigger('input');
        });
    });

    function clearFilter($list, department) {
        $list.find('.accordeon-item').each(function() {
            $(this).removeClass('active');

            if (!department || (department && $(this).data('department') == department)) {
                $(this).show();
            }

            $(this).find('.rowed-info-block a').show();

            // $(this).removeClass('active').stop().animate({opacity: 1}, 100);
            // $(this).find('.row').stop().animate({opacity: 1}, 100);
        });
    }

    function applyFilter($list, value, department) {
        value = $.trim(value);
        if (value === '' && !department) {
            return;
        }

        $list.find('.accordeon-item').each(function() {
            // Apply department filter.
            if (department && $(this).data('department') != department) {
                return;
            }

            var department_matches = false;

            $(this).find('.rowed-info-block a').each(function() {
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
                        department_matches = true;
                        return;
                    }
                }

                // Hide row, if value was not found in any phrase.
                $(this).hide();

                // $(this).stop().animate({opacity: 0.3}, 100);
            });

            // If any phrase matched, expand department.
            if (department_matches) {
                $(this).addClass('active');
            } else {
                // If department filter active but no match found, keep department expanded.
                if (department) {
                    $(this).addClass('active');
                } else {
                    $(this).hide();
                    $(this).find('.rowed-info-block a').show();
                }

                // $(this).find('.row').stop().animate({opacity: 1}, 100);
                // $(this).stop().animate({opacity: 0.3}, 100);
            }
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

    function applyDepartment($list, department) {
        $list.find('.accordeon-item').each(function() {
            $(this).show();

            if (department && $(this).data('department') != department) {
                $(this).hide();
            }
        });
    }
})(jQuery);
