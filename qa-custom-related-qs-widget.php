<?php

require_once CUSTOME_RELATED_DIR.'/related-qs-utils.php';

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

        if (!isset($qa_content['q_view']['raw']['type']) || $qa_content['q_view']['raw']['type'] != 'Q') // question might not be visible, etc...
            return;

        $questionid = $qa_content['q_view']['raw']['postid'];

        $userid = qa_get_logged_in_userid();
        $cookieid = qa_cookie_get();

        // 関連する質問
        if ($region === 'side') {
            $rquestions = related_qs_utils::get_related_questions($userid, $questionid);
            $titlehtml = qa_lang_html(count($rquestions) ? 'main/related_qs_title' : 'main/no_related_qs_title');
            $this->output_questions_widget_side($themeobject, $titlehtml, $rquestions, 'related-q-list');
        } else {
            $this->output_questions_widget_main($themeobject, 'related-q-list');
        }

        // おなじ季節の質問
        if ($region === 'side') {
            $squestions = related_qs_utils::get_seasonal_questions($userid);
            $titlehtml = qa_lang_html('custom_related_qs/title_seasons');
            $this->output_questions_widget_side($themeobject, $titlehtml, $squestions, 'season-q-list');
        } else {
            $this->output_questions_widget_main($themeobject, 'season-q-list');
        }
    }


    function output_questions_widget_side($themeobject, $titlehtml, $questions, $class, $sendEvent = false)
    {
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
    }

    function output_questions_widget_main($themeobject, $class)
    {
        $themeobject->output('<div class="' . $class . '" id="'.$class.'"></div>');
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
