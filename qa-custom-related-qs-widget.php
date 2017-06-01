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
        $this->output_questions_widget($region, $place, $themeobject, $userid, $cookieid, $titlehtml, $rquestions, 'related-q-list');

        // おなじ季節の質問
        $squestions = $this->get_seasonal_questions($userid);
        $titlehtml = qa_lang_html('custom_related_qs/title_seasons');
        $this->output_questions_widget($region, $place, $themeobject, $userid, $cookieid, $titlehtml, $squestions, 'season-q-list');

        // 最近の質問
        $questions = $this->get_recent_questions($userid);
        $titlehtml = qa_lang_html('main/recent_qs_title');
        if (infinite_scroll_available()) {
            // 無限スクロールが使用できる場合
            $themeobject->output('<div class="qa-qlist-recent">');
            $this->output_questions_widget($region, $place, $themeobject, $userid, $cookieid, $titlehtml,  $questions, 'recent-q-list', false);
            $themeobject->output('</div>');
            $this->output_pagelinks($themeobject);
            if (strpos(qa_opt('site_theme'), 'q2a-material-lite') !== false) {
                $themeobject->output('<div class="ias-spinner" style="align:center;"><span class="mdl-spinner mdl-js-spinner is-active" style="height:20px;width:20px;"></span></div>');
            }
        } else {
            $this->output_questions_widget($region, $place, $themeobject, $userid, $cookieid, $titlehtml,  $questions, 'recent-q-list', false);
        }

        // フッター(A/Bテスト用)。$('#related-widget-footer').show();で表示される
        $footer_tmpl = file_get_contents(CUSTOME_RELATED_DIR . '/html/footer.html');
        $subs = array(
          '^title' => qa_lang_html('custom_related_qs/footer_title'),
          '^read_fame' => qa_lang_html('custom_related_qs/read_fame'),
          '^read_recent' => qa_lang_html('custom_related_qs/read_recent'),
          '^read_blog' => qa_lang_html('custom_related_qs/read_blog'),
          '^about' => qa_lang_html('custom_related_qs/about'),
        );
        $footer = strtr($footer_tmpl, $subs);
        $themeobject->output($footer);
    }

    function get_related_questions($userid, $questionid)
    {
        $selectspec = qa_db_related_qs_selectspec($userid, $questionid);
        $minscore = qa_match_to_min_score(qa_opt('match_related_qs'));
        $minacount = 2;
        $listcount = 10;
        $selectspec['source'] .= ' WHERE ^posts.acount >= # AND y.score >= # LIMIT #';
        $selectspec['arguments'][] = $minacount;
        $selectspec['arguments'][] = $minscore;
        $selectspec['arguments'][] = $listcount;
        $questions = qa_db_single_select($selectspec);

        return $questions;
        // return array_slice($questions, 0, 5);
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
        $selectspec['source'] .= " AND ^posts.created like '" . $date . "' ORDER BY RAND() LIMIT 10";
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

    function output_questions_widget($region, $place, $themeobject, $userid, $cookieid, $titlehtml, $questions, $class, $sendEvent = false)
    {
        if ($region == 'side') {
            $themeobject->output(
                '<div class="qa-related-qs">',
                '<h2 style="margin-top:0; padding-top:0;">',
                $titlehtml,
                '</h2>'
            );

            $themeobject->output('<ul class="qa-related-q-list">');
            $idx = 1;
            foreach ($questions as $question) {
                if ($sendEvent) {
                    $onclick = 'onclick="optSendEvent('.$idx.');"';
                } else {
                    $onclick = '';
                }
                $themeobject->output(
                        '<li class="qa-related-q-item">' .
                        '<a href="' . qa_q_path_html($question['postid'], $question['title']) . '" '.$onclick.'>' .
                        qa_html($question['title']) .
                        '</a>' .
                        '</li>'
                );
                $idx++;
            }

            $themeobject->output(
                '</ul>',
                '</div>'
            );
        } else {
            $themeobject->output('<div class="' . $class . '">');
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
            $themeobject->q_list_and_form($q_list);
            $themeobject->output('</div>');
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
