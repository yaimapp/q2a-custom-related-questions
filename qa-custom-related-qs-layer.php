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

    function head_script()
    {
        qa_html_theme_base::head_script();
    }
    function head_css()
    {
        qa_html_theme_base::head_css();
    }
}
