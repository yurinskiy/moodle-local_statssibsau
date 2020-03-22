$(document).ready(function () {

    $('button.ajaxLoadData').on('click', function (e) {
        let $context = $(e.target);
        $.ajax({
            type: 'POST',
            url: $context.data('url'),
            beforeSend: function () {
                $context.html('Загрузка...');
                $context.attr('disabled', true);
            },
            success: function (data) {
                $context.html(data.count);
            },
            error: function () {
                $context.html('Что-то пошло не так...');
                $context.attr('disabled', false);
            },
        });
    });

});
