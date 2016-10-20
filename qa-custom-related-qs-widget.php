<?php

class qa_custom_related_qs
{
    public function allow_template($template)
    {
        return $template == 'question';
    }

    public function allow_region($region)
    {
        return in_array($region, array('side', 'main', 'full'));
    }

    public function output_widget($region, $place, $themeobject, $template, $request, $qa_content)
    {
        require_once QA_INCLUDE_DIR.'db/selects.php';

        if (!isset($qa_content['q_view']['raw']['type']) || $qa_content['q_view']['raw']['type'] != 'Q') // question might not be visible, etc...
            return;

        $questionid = $qa_content['q_view']['raw']['postid'];

        $userid = qa_get_logged_in_userid();
        $cookieid = qa_cookie_get();
                
        // 関連する質問
        $rquestions = $this->get_related_questions($userid, $questionid);
        $titlehtml = qa_lang_html(count($rquestions) ? 'main/related_qs_title' : 'main/no_related_qs_title');
        $this->output_questions_widget($region, $place, $themeobject, $userid, $cookieid, $titlehtml,  $rquestions);
        
        // おなじ季節の質問
        $squestions = $this->get_seasonal_questions($userid);
        $titlehtml = '同じ季節の質問';
        $this->output_questions_widget($region, $place, $themeobject, $userid, $cookieid, $titlehtml,  $squestions);
        
        // 最近の質問
        $questions = $this->get_recent_questions($userid);
        $titlehtml = qa_lang_html('main/recent_qs_title');
        if (infinite_scroll_available()) {
            // 無限スクロールが使用できる場合
            $themeobject->output('<div class="qa-qlist-recent">');
            $this->output_questions_widget($region, $place, $themeobject, $userid, $cookieid, $titlehtml,  $questions);
            $themeobject->output('</div>');
            $this->output_pagelinks($themeobject);
            if (strpos(qa_opt('site_theme'), 'q2a-material-lite') !== false) {
                $themeobject->output('<div class="ias-spinner" style="align:center;"><span class="mdl-spinner mdl-js-spinner is-active" style="height:20px;width:20px;"></span></div>');
            }
        }
    }
    
    function get_related_questions($userid, $questionid)
    {
        $questions = qa_db_single_select(qa_db_related_qs_selectspec($userid, $questionid, 15));

        $minscore = qa_match_to_min_score(qa_opt('match_related_qs'));
        $minacount = 2;
        
        foreach ($questions as $key => $question) {
            if ($question['score'] < $minscore || $question['acount'] < $minacount) {
                unset($questions[$key]);
            }
        }
        return array_slice($questions, 0, 5);
    }
    
    
    function get_seasonal_questions($userid = null)
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
        $selectspec['source'] .= " AND ^posts.created like '" . $date . "' ORDER BY RAND() LIMIT 5";
        $questions=qa_db_single_select($selectspec);
        return $questions;
    }
    
    function get_recent_questions($userid = null)
    {
        require_once QA_INCLUDE_DIR.'db/selects.php';
        
        $selectsort='created';
        $start=qa_get_start();
        
        $selectspec = qa_db_qs_selectspec($userid, $selectsort, $start, null, null, false, false, 5);
        
        $questions = qa_db_single_select($selectspec);
        return $questions;
    }
    
    function output_questions_widget($region, $place, $themeobject, $userid, $cookieid, $titlehtml, $questions)
    {
        if ($region == 'side') {
            $themeobject->output(
                '<div class="qa-related-qs">',
                '<h2 style="margin-top:0; padding-top:0;">',
                $titlehtml,
                '</h2>'
            );

            $themeobject->output('<ul class="qa-related-q-list">');

            foreach ($questions as $question) {
                $themeobject->output(
                        '<li class="qa-related-q-item">' .
                        '<a href="' . qa_q_path_html($question['postid'], $question['title']) . '">' .
                        qa_html($question['title']) .
                        '</a>' .
                        '</li>'
                );
            }

            $themeobject->output(
                '</ul>',
                '</div>'
            );
        } else {
            $themeobject->output(
                '<h2>',
                $titlehtml,
                '</h2>'
            );

            $q_list = array(
                'form' => array(
                    'tags' => 'method="post" action="' . qa_self_html() . '"',
                    'hidden' => array(
                        'code' => qa_get_form_security_code('vote'),
                    ),
                ),
                'qs' => array(),
            );

            $defaults = qa_post_html_defaults('Q');
            $usershtml = qa_userids_handles_html($questions);

            foreach ($questions as $question)
                $q_list['qs'][] = qa_post_html_fields($question, $userid, $cookieid, $usershtml, null, qa_post_html_options($question, $defaults));

            $themeobject->q_list_and_form($q_list);
        }
    }
    
    function output_pagelinks($themeobject)
    {
        $start = qa_get_start();
        $page_links = qa_html_page_links(qa_request(), $start, 5, qa_opt('cache_qcount'), qa_opt('pages_prev_next'), array());
        $themeobject->output('<div class="qa-page-links">');

        $themeobject->page_links_label($page_links['label']);
        $themeobject->page_links_list($page_links['items']);
        $themeobject->page_links_clear();

        $themeobject->output('</div>');
    }
}
