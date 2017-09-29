$(document).ready(function() {

    function ajax_get_related_qs() {
        console.log('get_related_qs');
        $.ajax({
            url: './relatedqs/',
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

    setTimeout(function() {
        ajax_get_related_qs();
    }, 3000);
});