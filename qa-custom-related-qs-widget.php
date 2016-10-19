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
        
        $this->output_related_questions($userid, $questionid, $cookieid, $region, $place, $themeobject);
    }
    
    function output_related_questions($userid, $questionid, $cookieid, $region, $place, $themeobject)
    {
        
        $questions = qa_db_single_select(qa_db_related_qs_selectspec($userid, $questionid, 15));

        $minscore = qa_match_to_min_score(qa_opt('match_related_qs'));
        $minacount = 2;
        
        foreach ($questions as $key => $question) {
            if ($question['score'] < $minscore || $question['acount'] < $minacount) {
                unset($questions[$key]);
            }
        }
        $questions = array_slice($questions, 0, 5);
        $titlehtml = qa_lang_html(count($questions) ? 'main/related_qs_title' : 'main/no_related_qs_title');
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
}
