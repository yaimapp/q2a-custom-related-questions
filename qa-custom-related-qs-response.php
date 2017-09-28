<?php

require_once CUSTOME_RELATED_DIR.'/related-qs-utils.php';

class qa_crq_page {
    
    function match_request($request) {
        $parts = explode('/', $request);
        
        return $parts[0] == 'relatedqs';
    }
    
    function process_request($request) {
        $userid = qa_get('userid');
        $postid = qa_get('postid');

        $related_qs_html = related_qs_utils::get_related_qs_html($userid, $postid);

        header ( 'Content-Type: application/json' );
            
        http_response_code ( 200 );
        
        $ret_val = array ();
        
        $json_object = array ();
        
        $json_object['statuscode'] = '200';
        $json_object['message'] = 'ok';

        $json_object['related_q_list'] = $related_qs_html;
        
        array_push ( $ret_val, $json_object );
        echo json_encode ( $ret_val, JSON_PRETTY_PRINT );
    }
    
}
