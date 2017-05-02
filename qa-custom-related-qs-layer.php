<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../../');
    exit;
}

class qa_html_theme_layer extends qa_html_theme_base
{
    private $infscr = null;
    private $pluginurl = '';
    
    function qa_html_theme_layer($template, $content, $rooturl, $request)
    {
        qa_html_theme_base::qa_html_theme_base($template, $content, $rooturl, $request);
        if(infinite_scroll_available()) {
            require_once QA_PLUGIN_DIR . 'q2a-infinite-scroll/qa-infinite-scroll.php';
            $this->infscr = new qa_infinite_scroll();
        }
        $this->pluginurl = qa_opt('site_url').'qa-plugin/q2a-custom-related-questions/';
    }

    function head_script()
    {
        qa_html_theme_base::head_script();
        if ($this->template === 'question' && isset($this->infscr)) {
            if (strpos(qa_opt('site_theme'), 'q2a-material-lite') !== false) {
                $this->output('<script>var material_lite = true;</script>');
            } else {
                $this->output('<script>var material_lite = false;</script>');
            }
            $this->output('<SCRIPT async TYPE="text/javascript" SRC="'.$this->infscr->pluginjsurl.'jquery-ias.min.js"></SCRIPT>');
            $this->output('<SCRIPT async TYPE="text/javascript" SRC="'.$this->pluginurl.'js/infinite-scroll-recent.js"></SCRIPT>');
            $this->output('<SCRIPT async TYPE="text/javascript" SRC="'.$this->pluginurl.'js/optimizely.js"></SCRIPT>');
        }
    }
    function head_css()
    {
        qa_html_theme_base::head_css();
        if ($this->template === 'question' && isset($this->infscr)) {
            $this->output('<LINK REL="stylesheet" TYPE="text/css" HREF="'.$this->infscr->plugincssurl.'jquery.ias.css"/>');
        }
    }
}
