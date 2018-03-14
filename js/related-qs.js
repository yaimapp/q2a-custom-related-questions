$(document).ready(function() {

    function ajax_get_related_qs() {
        var related_ajax_url = base_url + $('#related-qs-ajax').data('url');
        $.ajax({
            url: related_ajax_url,
            type: 'GET',
            dataType: 'json',
            cache : false,
            data: { postid: related_qs_postid }
        })
        .done(function(res, status, xhr) {
            if (xhr.status === 200) {
                if (res !== null && res !== undefined) {
                    if (res.related_q_list) {
                        $('#related-q-list').html(res.related_q_list);
                    }
                    if (res.season_q_list) {
                        $('#season-q-list').html(res.season_q_list);
                    }
                    $('.lazyload').lazyload();
                }
            } else {
                console.log(xhr.status);
                console.log(res);
            }
        })
    }

    var widget_top = $('#related-q-list').offset().top;
    var window_height = window.innerHeight;
    var read_question_list = false;
    var offset = 100;

    $(window).scroll(function(){

        var scr_count = $(document).scrollTop();
        // widget の位置に来たらリストを読み込む
        if(scr_count + window_height + offset >= widget_top) {
            if (!read_question_list) {
                ajax_get_related_qs();
                read_question_list = true;
            }
        }
        var is_sidefix = $('#qa-widgets-side.fixed').hasClass('fixed');
        if (is_sidefix && scr_count > 150) {
            $('#qa-widgets-side.fixed').attr('style', 'top: 50px');
        } else if (is_sidefix && scr_count <= 150) {
            $('#qa-widgets-side.fixed').attr('style', 'top: 260px');
        }
    });

});