(function ($) {
    var fixHelper = function (e, ui) {
        ui.children().each(function () {
            $(this).width($(this).width());
        });
        return ui;
    };

    $.fn.SortableGridView = function (action) {
        var widget = this;
        var grid = $('tbody', this);

        var initialIndex = [];
        $('tr', grid).each(function () {
            initialIndex.push($(this).find('.item-key').data('key'));
        });

        grid.sortable({
            items: 'tr',
            axis: 'y',
            update: function () {
                var items = {};
                var i = 0;
                $('tr', grid).each(function () {
                    var currentKey = $(this).find('.item-key').data('key');
                    if (initialIndex[i] != currentKey) {
                        items[currentKey] = initialIndex[i];
                        initialIndex[i] = currentKey;
                    }
                    ++i;
                });

                $.ajax({
                    'url': action,
                    'type': 'post',
                    'data': {'items': JSON.stringify(items)},
                    'success': function () {
                        widget.trigger('sortableSuccess');
                    },
                    'error': function (request, status, error) {
                        alert(status + ' ' + error);
                    }
                });
            },
            helper: fixHelper
        }).disableSelection();
    };
})(jQuery);
