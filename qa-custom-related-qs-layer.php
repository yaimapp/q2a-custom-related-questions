<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../../');
    exit;
}

class qa_html_theme_layer extends qa_html_theme_base
{
    private $pluginurl = '';

    function qa_html_theme_layer($template, $content, $rooturl, $request)
    {
        qa_html_theme_base::qa_html_theme_base($template, $content, $rooturl, $request);
        $this->pluginurl = qa_opt('site_url').'qa-plugin/q2a-custom-related-questions/';
    }

    function body_footer()
    {
        qa_html_theme_base::body_footer();
        if($this->template === 'question') {
            $userid = qa_get_logged_in_userid();
            $postid = @$this->content['q_view']['raw']['postid'];
            $script = <<<EOS
<script>
var related_qs_userid = '{$userid}';
var related_qs_postid = '{$postid}';
</script>
EOS;
            $this->output($script);
            $src = $this->pluginurl.'js/related-qs.js';
            $this->output('<script src="'.$src.'"></script>');
        }
    }
}
