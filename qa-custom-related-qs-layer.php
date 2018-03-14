<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../../');
    exit;
}

class qa_html_theme_layer extends qa_html_theme_base
{
    private $pluginurl = '';

    function __construct($template, $content, $rooturl, $request)
    {
        qa_html_theme_base::qa_html_theme_base($template, $content, $rooturl, $request);
        $this->pluginurl = qa_opt('site_url').'qa-plugin/q2a-custom-related-questions/';
    }

    function body_footer()
    {
        qa_html_theme_base::body_footer();
        if($this->template === 'question' && !$this->is_edit()) {
            $postid = @$this->content['q_view']['raw']['postid'];
            $baseurl = qa_opt('site_url');
            $script = <<<EOS
<script>
var related_qs_postid = '{$postid}';
var base_url = '{$baseurl}';
</script>
EOS;
            $this->output($script);
            $src = $this->pluginurl.'js/related-qs.js';
            $this->output('<script src="'.$src.'"></script>');
        }
    }

    function q_list_items($q_items)
    {
        if($this->template === 'ajax-rlated-qs') {
            foreach ($q_items as $q_item) {
              $q_item['classes'] = $q_item['classes'] . ' q-item-' . $q_item['list_index'];
              $this->q_list_item($q_item);
            }
        } else {
            qa_html_theme_base::q_list_items($q_items);
        }
    }

    function is_edit()
    {
        $content = $this->content;
        if(strpos(qa_get_state(),'edit') !== false) {
            return true;
        } else {
            return false;
        }

    }
}
