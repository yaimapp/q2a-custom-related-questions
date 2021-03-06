<?php

require_once CUSTOME_RELATED_DIR.'/related-qs-utils.php';

class qa_custom_related_qs
{
    
    public function allow_template($template)
    {
        return ($template === 'question' || $template === 'amp');
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
            $rquestions = related_qs_utils::get_related_questions($userid, $questionid, 5);
            $titlehtml = qa_lang_html(count($rquestions) ? 'main/related_qs_title' : 'main/no_related_qs_title');
            $this->output_questions_widget_side($themeobject, $titlehtml, $rquestions, 'related-q-list');
        } else {
            if ($template === 'question') {
                $rquestions = related_qs_utils::get_related_questions_imagepost($userid, $questionid);
                $titlehtml = qa_lang_html(count($rquestions) ? 'main/related_qs_title' : 'main/no_related_qs_title');

                $this->output_questions_widget_main($themeobject, $titlehtml, 'related-q-list');
            } elseif ($template === 'amp') {
                $this->output_questions_widget_amp($themeobject, 'related-q-list', $userid, $questionid);
            }
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
        $idx = 0;
        foreach ($questions as $question) {
            if ($sendEvent) {
                $onclick = 'onclick="optSendEvent('.$idx.');"';
            } else {
                $onclick = '';
            }
            $themeobject->output(
                    '<li class="qa-related-q-item">' .
                    '<a id="side-related-q-'.$idx.'" href="' . qa_q_path_html($question['postid'], $question['title']) . '" '.$onclick.'>' .
                    $this->truncate_text(qa_html($question['title']), 71) .
                    '</a>' .
                    '</li>'
            );
            $idx++;
        }

        $themeobject->output(
            '</ul>',
            '</div>'
        );
        global $qa_layers;
        $plugin_url = $qa_layers['Custom Related Questions Layer']['urltoroot'];
        $themeobject->output(
            '<div id="side-ask-banner">',
            '<a href="/ask">',
            '<img src="/'.$plugin_url.'images/side_banner.png">',
            '</a>',
            '</div>'
        );
    }

    function output_questions_widget_main($themeobject, $titlehtml, $class)
    {
        $themeobject->output('<span id="related-qs-ajax" data-url="relatedqs/hall"></span>');
        $themeobject->output('<div class="' . $class . '" id="'.$class.'">');
        $themeobject->output('<h2 style="margin-top:0; padding-top:0;">'.$titlehtml.'</h2>');
        $themeobject->output('<div class="ias-spinner" style="text-align:center;"><span class="mdl-spinner mdl-js-spinner is-active" style="height:20px;width:20px;"></span></div>');
        $themeobject->output('</div>');
    }

    function output_questions_widget_amp($themeobject, $class, $userid, $questionid)
    {
        $themeobject->output('<div class="'.$class.'" id="'.$class.'">');
        $questions = related_qs_utils::get_related_questions_imagepost_only($userid, $questionid);
        if (count($questions) > 0) {
            $titlehtml = qa_lang('main/related_qs_title');
            $themeobject->output('<h2 class="widget-title">'.$titlehtml.'</h2>');
            $q_list = related_qs_utils::get_q_list($questions, $userid);
            
            $themeobject->q_list_and_form($q_list);
        } else {
            $titlehtml = qa_lang('main/no_related_qs_title');
            $themeobject->output('<h2 class="widget-title">'.$titlehtml.'</h2>');
        }
        $questions2 = related_qs_utils::get_related_questions_hall($userid, $questionid);
        if (count($questions2) > 0) {
            $titlehtml = qa_lang('custom_related_qs/fame_title');
            $themeobject->output('<h2 class="widget-title">'.$titlehtml.'</h2>');
            $q_list = related_qs_utils::get_q_list($questions2, $userid, false, 6);
            
            $themeobject->q_list_and_form($q_list);
        } else {
            $titlehtml = qa_lang('custom_related_qs/no_fame_title');
            $themeobject->output('<h2 class="widget-title">'.$titlehtml.'</h2>');
        }
        $themeobject->output('</div>');
    }

    private function truncate_text($content, $length)
    {
        if (mb_strlen($content, "UTF-8") >= $length) {
            $tmp = mb_substr($content, 0, $length - 1, "UTF-8");
            $tmp .= '&#8230;';
        } else {
            $tmp = $content;
        }
        return $tmp;
    }

}
