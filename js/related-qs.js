$(document).ready(function() {

    function ajax_get_recent_qs() {
        $.ajax({
            url: './relatedqs',
            type: 'GET',
            dataType: 'json',
            cache : false,
            data: { postid: related_qs_postid, userid: related_qs_userid }
        })
        .done(function(res, status, xhr) {
            if (xhr.status === 204) {
                console.log(xhr.statusText);
            } else {
                if (res[0] !== null && res[0] !== undefined) {
                    if (res[0].related_q_list) {
                        $('#related-q-list').html(res[0].related_q_list);
                    }
                }
            }
        })
        .fail(function(res) {
            console.log(res);
        });
    }

    setTimeout(function() {
        ajax_get_recent_qs();
    }, 1000);
});