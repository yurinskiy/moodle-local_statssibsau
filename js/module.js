$(document).ready(function () {

    $('button.ajaxLoadData').on('click', function (e) {
        let $context = $(e.target);
        $.ajax({
            type: 'POST',
            url: $context.data('url'),
            beforeSend: function () {
                $context.html('Загрузка...');
            },
            success: function (data) {
                $context.html(data.count);
                $context.attr('disabled', true);
            },
            error: function () {
                $context.html('Что-то пошло не так...')
            },
        });
    });

});
