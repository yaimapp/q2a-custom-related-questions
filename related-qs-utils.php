<?php

require_once QA_INCLUDE_DIR.'db/selects.php';

class related_qs_utils {
    
    const CACHE_EXPIRES = 60 * 60;      // キャッシュの保存期間
    const MIN_ACOUNT = 2;               // 最小の回答数
    const LIST_COUNT = 10;              // 表示件数

    /*
     * 関連する質問
     */
    public static function get_related_questions($userid, $questionid)
    {
        global $qa_cache;
        $key = 'q-related-'.$questionid;
        if($qa_cache->has($key)) {
            $questions = $qa_cache->get($key);
        } else {
            $selectspec = qa_db_related_qs_selectspec($userid, $questionid);
            $minscore = qa_match_to_min_score(qa_opt('match_related_qs'));
            $selectspec['source'] .= ' WHERE ^posts.acount >= # AND y.score >= # LIMIT #';
            $selectspec['arguments'][] = self::MIN_ACOUNT;
            $selectspec['arguments'][] = $minscore;
            $selectspec['arguments'][] = self::LIST_COUNT;
            $questions = qa_db_single_select($selectspec);
            $qa_cache->set($key, $questions, self::CACHE_EXPIRES);
        }
        return $questions;
        // return array_slice($questions, 0, 5);
    }

    /*
     * 季節の質問
     */
    public static function get_seasonal_questions($userid = null)
    {
        $month = date("m");
        $day= date("j");
        $day = floor($day/10);
        if($day == 3) {
            $day  = 2;
        }
        $date = '%-' . $month . '-' . $day . '%';

        // $userid = '1';
        $selectspec=qa_db_posts_basic_selectspec($userid);
        $selectspec['source'] .=" WHERE type='Q'";
        $selectspec['source'] .= " AND ^posts.created like '" . $date . "' ORDER BY RAND() LIMIT 10";
        $questions=qa_db_single_select($selectspec);
        return $questions;
    }

    /*
     * 最近の質問
     */
    public static function get_recent_questions($userid = null)
    {
        $selectsort='created';
        $start=qa_get_start();

        $selectspec = qa_db_qs_selectspec($userid, $selectsort, $start, null, null, false, false, 5);

        $questions = qa_db_single_select($selectspec);
        return $questions;
    }

    /*
     * q_list を返す
     */
    public static function get_q_list($questions, $userid, $sendEvent = false) {

        $q_list = array(
            'form' => array(
                'tags' => 'method="post" action="' . qa_self_html() . '"',
                'hidden' => array(
                    'code' => qa_get_form_security_code('vote'),
                ),
            ),
            'qs' => array(),
        );

        $cookieid = qa_cookie_get();
        $defaults = qa_post_html_defaults('Q');
        $usershtml = qa_userids_handles_html($questions);
        $idx = 1;
        foreach ($questions as $question) {
            if ($sendEvent) {
                $onclick = '" onclick="optSendEvent('.$idx.');';
            } else {
                $onclick = '';
            }
            $fields = qa_post_html_fields($question, $userid, $cookieid, $usershtml, null, qa_post_html_options($question, $defaults));
            $fields['url'] .= $onclick;
            $q_list['qs'][] = $fields;
            $idx++;
        }
        return $q_list;
    }

    /*
     * 関連する質問のHTMLを返す
     */
    public static function get_related_qs_html($userid, $questionid, $themeobject)
    {
        $questions = self::get_related_questions($userid, $questionid);
        if (count($questions) > 0) {
            $titlehtml = qa_lang('main/related_qs_title');
            $html = '<h2 style="margin-top:0; padding-top:0;">'.$titlehtml.'</h2>';
        } else {
            $titlehtml = qa_lang('no_related_qs_title');
            return '<h2 style="margin-top:0; padding-top:0;">'.$titlehtml.'</h2>';
        }

        $q_list = self::get_q_list($questions, $userid);
        
        ob_start();
        $themeobject->q_list_and_form($q_list);
        $html .= ob_get_clean();

        return $html;
    }

    /*
     * 季節の質問のHTMLを返す
     */
    public static function get_seasonal_qs_html($userid, $themeobject)
    {
        $questions = self::get_seasonal_questions($userid);
        $titlehtml = qa_lang_html('custom_related_qs/title_seasons');
        $html = '<h2 style="margin-top:0; padding-top:0;">'.$titlehtml.'</h2>';

        $q_list = self::get_q_list($questions, $userid);

        ob_start();
        $themeobject->q_list_and_form($q_list);
        $html .= ob_get_clean();

        return $html;
    }
}