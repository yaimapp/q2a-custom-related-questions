<?php

require_once QA_INCLUDE_DIR.'db/selects.php';

class related_qs_utils {

    const CACHE_EXPIRES = 60 * 10;      // キャッシュの保存期間
    const MIN_ACOUNT_IMG = 2;           // 最小の回答数(画像あり)
    const MIN_ACOUNT = 3;               // 最小の回答数
    const LIST_COUNT_IMG = 2;           // 表示件数(画像あり)
    const LIST_COUNT_FAME = 10;         // 表示件数(人気の記事)
    const LIST_COUNT = 15;              // 表示件数
    const LIST_COUNT_NO_ANSWER = 2;     // 表示件数（回答のない質問）
    const LIST_COUNT_RELATED = 5;     // 表示件数（関連する質問）

    /*
     * 関連する質問
     */
    public static function get_related_questions($userid, $questionid, $list_count)
    {
        global $qa_cache;
        $key = 'q-r-question-'.$questionid;
        if($qa_cache->has($key)) {
            $questions = $qa_cache->get($key);
        } else {
            $selectspec = qa_db_related_qs_selectspec($userid, $questionid);
            $minscore = qa_match_to_min_score(qa_opt('match_related_qs'));
            $selectspec['columns']['content'] = '^posts.content ';
            $selectspec['columns']['format'] = '^posts.format ';
            $where = ' WHERE ^posts.acount >= # AND y.score >= #';
            $where.= ' AND ^posts.categoryid = (SELECT categoryid FROM ^posts WHERE postid=#)';
            $where.= ' LIMIT #';
            $selectspec['source'] .= $where;
            $selectspec['arguments'][] = self::MIN_ACOUNT;
            $selectspec['arguments'][] = $minscore;
            $selectspec['arguments'][] = $questionid;
            $selectspec['arguments'][] = $list_count;
            $questions = qa_db_single_select($selectspec);
            $qa_cache->set($key, $questions, self::CACHE_EXPIRES);
        }
        return $questions;
    }

    /*
     * 関連する質問（画像付き投稿を優先）
     */
    public static function get_related_questions_imagepost($userid, $questionid)
    {
        global $qa_cache;
        $key = 'q-related-'.$questionid;
        if($qa_cache->has($key)) {
            $questions = $qa_cache->get($key);
        } else {
            $orgselspec = qa_db_related_qs_selectspec($userid, $questionid);
            $minscore = qa_match_to_min_score(qa_opt('match_related_qs'));
            $orgselspec['columns']['content'] = '^posts.content ';
            $orgselspec['columns']['format'] = '^posts.format ';
            $imgselspec = $orgselspec;
            $where = " WHERE  (^posts.content like '%[image=%'";
            $where.= " OR ^posts.content like '%<img%'";
            $where.= " OR ^posts.content like '%[uploaded-video=%'";
            $where.= " OR ^posts.content like '%plain_url%')";
            $where.= ' AND ^posts.acount >= # AND y.score >= #';
            $where.= ' AND ^posts.categoryid = (SELECT categoryid FROM ^posts WHERE postid=#)';
            $where.= ' LIMIT #';
            $imgselspec['source'] .= $where;
            $imgselspec['arguments'][] = self::MIN_ACOUNT_IMG;
            $imgselspec['arguments'][] = $minscore;
            $imgselspec['arguments'][] = $questionid;
            $imgselspec['arguments'][] = self::LIST_COUNT_IMG;

            $otherselspec = $orgselspec;
            $where2 = ' WHERE ^posts.acount >= # AND y.score >= #';
            $where2.= ' AND ^posts.categoryid = (SELECT categoryid FROM ^posts WHERE postid=#)';
            $where2.= ' LIMIT #';
            $otherselspec['source'] .= $where2;
            $otherselspec['arguments'][] = self::MIN_ACOUNT;
            $otherselspec['arguments'][] = $minscore;
            $otherselspec['arguments'][] = $questionid;
            $otherselspec['arguments'][] = self::LIST_COUNT;


            list($imgquestions, $otherquestions) = qa_db_select_with_pending(
                $imgselspec, $otherselspec
            );
            $questions = array_slice(array_replace($imgquestions, $otherquestions), 0, self::LIST_COUNT_RELATED);
            $qa_cache->set($key, $questions, self::CACHE_EXPIRES);
        }
        return $questions;
        // return array_slice($questions, 0, 5);
    }

    /*
     * 人気の記事(タグ「殿堂入り」が付いた記事)をランダムで
     */
    public static function get_related_questions_hall($userid, $questionid)
    {
        global $qa_cache;
        $key = 'q-related-hall-'.$questionid;
        if($qa_cache->has($key)) {
            $questions = $qa_cache->get($key);
        } else {
            $hall_tag = qa_lang('custom_related_qs/hall_of_fame');
            $hallselspec = qa_db_posts_basic_selectspec($userid, true);
            $hallselspec['columns']['content'] = '^posts.content ';
            $hallselspec['columns']['format'] = '^posts.format ';
            $source = " JOIN (SELECT postid FROM ^posttags ";
            $source.= " WHERE wordid=(SELECT wordid FROM ^words ";
            $source.= " WHERE word=$ AND word=$ COLLATE utf8_bin LIMIT 1) ";
            $source.= " ORDER BY RAND() LIMIT #,#) y ON ^posts.postid=y.postid";
            $source.= " WHERE ^posts.categoryid = (SELECT categoryid FROM ^posts WHERE postid=#)";
            $hallselspec['source'].=$source;
            array_push($hallselspec['arguments'], $hall_tag, qa_strtolower($hall_tag), 0, self::LIST_COUNT_FAME, $questionid);

            $questions = qa_db_select_with_pending($hallselspec);
            $qa_cache->set($key, $questions, self::CACHE_EXPIRES);
        }
        return $questions;
    }

    /*
     * 関連する質問（画像付きのみ）
     */
    public static function get_related_questions_imagepost_only($userid, $questionid)
    {
        global $qa_cache;
        $key = 'q-related-img-'.$questionid;
        if($qa_cache->has($key)) {
            $questions = $qa_cache->get($key);
        } else {
            $orgselspec = qa_db_related_qs_selectspec($userid, $questionid);
            $minscore = qa_match_to_min_score(qa_opt('match_related_qs'));
            $orgselspec['columns']['content'] = '^posts.content ';
            $orgselspec['columns']['format'] = '^posts.format ';
            $imgselspec = $orgselspec;
            $where = " WHERE  (^posts.content like '%[image=%'";
            $where.= " OR ^posts.content like '%<img%'";
            $where.= " OR ^posts.content like '%[uploaded-video=%'";
            $where.= " OR ^posts.content like '%plain_url%')";
            $where.= ' AND ^posts.acount >= # AND y.score >= #';
            $where.= ' AND ^posts.categoryid = (SELECT categoryid FROM ^posts WHERE postid=#)';
            $where.= ' LIMIT #';
            $imgselspec['source'] .= $where;
            $imgselspec['arguments'][] = self::MIN_ACOUNT_IMG;
            $imgselspec['arguments'][] = $minscore;
            $imgselspec['arguments'][] = $questionid;
            $imgselspec['arguments'][] = self::LIST_COUNT_IMG;

            $questions = qa_db_select_with_pending($imgselspec);

            $qa_cache->set($key, $questions, self::CACHE_EXPIRES);
        }
        return $questions;
    }

    /*
     * 一週間以内の回答がまだついていない質問
     */
    public static function get_no_answer_questions($userid, $questionid)
    {
        global $qa_cache;
        $key = 'q-no-answer-'.$questionid;
        if ($qa_cache->has($key)) {
            $questions = $qa_cache->get($key);
        } else {
            $orgselspec = qa_db_posts_basic_selectspec($userid, true);
            $where = " WHERE type = 'Q'";
            $where.= " AND ^posts.acount = 0";
            $where.= " AND ^posts.postid != #";
            $where.= " AND ^posts.created >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $where.= " ORDER BY ^posts.created DESC";
            $where.= " LIMIT #";
            $orgselspec['source'] .= $where;
            $orgselspec['arguments'][] = $questionid;
            $orgselspec['arguments'][] = self::LIST_COUNT_NO_ANSWER;
            $questions = qa_db_select_with_pending($orgselspec);
            $qa_cache->set($key, $questions, self::CACHE_EXPIRES);
        }
        return $questions;

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
        $selectspec['columns']['content'] = '^posts.content ';
        $selectspec['columns']['format'] = '^posts.format ';
        $selectspec['source'] .=" WHERE type='Q'";
        $selectspec['source'] .= " AND ^posts.created like $ ORDER BY RAND() LIMIT #";
        $selectspec['arguments'][] = $date;
        $selectspec['arguments'][] = self::LIST_COUNT;
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
        $selectspec['columns']['content'] = '^posts.content ';
        $selectspec['columns']['format'] = '^posts.format ';

        $questions = qa_db_single_select($selectspec);
        return $questions;
    }

    /*
     * q_list を返す
     */
    public static function get_q_list($questions, $userid, $sendEvent = false, $start_index = 1) {

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
        $defaults['contentview'] = true;
        $usershtml = qa_userids_handles_html($questions);
        $idx = $start_index;
        foreach ($questions as $question) {
            if ($sendEvent) {
                $onclick = '" onclick="optSendEvent('.$idx.');';
            } else {
                $onclick = '';
            }
            $fields = qa_post_html_fields($question, $userid, $cookieid, $usershtml, null, qa_post_html_options($question, $defaults));
            $fields['url'] .= $onclick;
            if (function_exists('qme_remove_anchor')) {
                $fields['content'] = qme_remove_anchor($fields['content']);
            }
            $fields['list_index'] = $idx;
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
        $questions = self::get_related_questions_imagepost($userid, $questionid);
        if (count($questions) > 0) {
            $titlehtml = qa_lang('main/related_qs_title');
            $html = '<h2 style="margin-top:0; padding-top:0;">'.$titlehtml.'</h2>';
        } else {
            $titlehtml = qa_lang('main/no_related_qs_title');
            return '<h2 style="margin-top:0; padding-top:0;">'.$titlehtml.'</h2>';
        }

        $q_list = self::get_q_list($questions, $userid);

        ob_start();
        $themeobject->q_list_and_form($q_list);
        $html .= ob_get_clean();

        return $html;
    }

    /*
     * 関連する質問と人気の記事のHTMLを返す
     */
    public static function get_related_qs_html_hall($userid, $questionid, $themeobject)
    {
        $html = '';

        // 他サイト（獣害Q&A）の投稿
        if (qa_opt('material_lite_option_show_others')) {
            $other_q_list_html = self::get_other_q_list_html($themeobject);
            if (!empty($other_q_list_html)) {
                $html .= $other_q_list_html;
            }
        }

        $questions = self::get_related_questions_imagepost($userid, $questionid);
        if (count($questions) > 0) {
            $titlehtml = qa_lang('main/related_qs_title');
            $html .= '<h2 style="margin-top:0; padding-top:0;">'.$titlehtml.'</h2>';
            $q_list = self::get_q_list($questions, $userid);

            ob_start();
            $themeobject->q_list_and_form($q_list);
            $html .= ob_get_clean();
        } else {
            $titlehtml = qa_lang('main/no_related_qs_title');
            $html .= '<h2 style="margin-top:0; padding-top:0;">'.$titlehtml.'</h2>';
        }
        $no_answer_questions = self::get_no_answer_questions($userid, $questionid);
        if (count($no_answer_questions) > 0) {
            $titlehtml = qa_lang('custom_related_qs/no_answer_title');
            $html .= '<h2 style="margin-top:0; padding-top:0;">'.$titlehtml.'</h2>';
            $q_list = self::get_q_list($no_answer_questions, $userid, false, 6);

            ob_start();
            $themeobject->q_list_and_form($q_list);
            $html .= ob_get_clean();
        }

        $html .= self::get_events_html($userid, $themeobject);

        $themeobject->template = 'ajax-rlated-qs';
        $questions2 = self::get_related_questions_hall($userid, $questionid);
        if (count($questions2) > 0) {
            $titlehtml = qa_lang('custom_related_qs/fame_title');
            $html .= '<h2 style="margin-top:0; padding-top:0;">'.$titlehtml.'</h2>';
            $q_list = self::get_q_list($questions2, $userid, false, 6 + count($no_answer_questions));

            ob_start();
            $themeobject->q_list_and_form($q_list);
            $html .= ob_get_clean();
        } else {
            $titlehtml = qa_lang('custom_related_qs/no_fame_title');
            $html .= '<h2 style="margin-top:0; padding-top:0;">'.$titlehtml.'</h2>';
        }

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

    /*
     * イベントのお知らせのHTML
     */
    public static function get_events_html($userid, $themeobj)
    {
        // return qa_theme_utils::get_side_events_html();
        $html = qas_blog_get_event_notices_html($userid, 0, self::CACHE_EXPIRES, $themeobj);
        $html = '<div id="events-list" class="two-columns">'.$html.'</div>';
        return $html;
    }

    private static function get_other_q_list_html($themeobject)
    {
        $html = '';
        $other_q_list = qa_theme_utils::get_other_recent_q_list_items(1, true, 'jugai');
        if (count($other_q_list)) {
            $jugai_tmpl = file_get_contents(CUSTOME_RELATED_DIR . '/html/chojugai.html');
            ob_start();
            foreach ($other_q_list as $q_item) {
                $themeobject->q_list_item($q_item);
            }
            $other_q_list_html = ob_get_clean();
            $other_q_list_html = str_replace('../', 'https://chojugai-qa.com/', $other_q_list_html);

            $html  .= strtr($jugai_tmpl, array('^q_list_html' => $other_q_list_html));
        }

        $other_q_list = qa_theme_utils::get_other_recent_q_list_items(1, true, 'tsurinowa');
        if (count($other_q_list)) {
            $jugai_tmpl = file_get_contents(CUSTOME_RELATED_DIR . '/html/trurinowa.html');
            ob_start();
            foreach ($other_q_list as $q_item) {
                $themeobject->q_list_item($q_item);
            }
            $other_q_list_html = ob_get_clean();
            $other_q_list_html = str_replace('../', 'https://tsurinowa.com/', $other_q_list_html);

            $html .= strtr($jugai_tmpl, array('^q_list_html' => $other_q_list_html));
        }



        return $html;
    }
}
