
<?php

class qa_custom_related_qs_admin
{

    public function option_default($option)
    {
        switch ($option) {
            case 'related_qs_min_acount_img':
                return 2;
            case 'related_qs_min_acount':
                return 3;
            default:
                return;
        }
    }

    public function allow_template($template)
    {
        return $template !== 'admin';
    }

    public function admin_form(&$qa_content)
    {
        // process the admin form if admin hit Save-Changes-button
        $ok = null;
        if (qa_clicked('qa_custom_related_qs_save')) {
            qa_opt('related_qs_min_acount_img', qa_post('related_qs_min_acount_img'));
            qa_opt('related_qs_min_acount', qa_post('related_qs_min_acount'));

            $ok = qa_lang('admin/options_saved');
        }
        $fields[] = array(
            'label' => qa_lang('custom_related_qs/min_acount_img'),
            'tags' => 'NAME="related_qs_min_acount_img"',
            'value' => qa_opt('related_qs_min_acount_img'),
            'type' => 'text',
        );

        $fields[] = array(
            'label' => qa_lang('custom_related_qs/min_acount'),
            'tags' => 'NAME="related_qs_min_acount"',
            'value' => qa_opt('related_qs_min_acount'),
            'type' => 'text',
        );
        return array(
            'ok' => ($ok && !isset($error)) ? $ok : null,
            'fields' => $fields,
            'buttons' => array(
                array(
                    'label' => qa_lang_html('main/save_button'),
                    'tags' => 'name="qa_custom_related_qs_save"',
                ),
            ),
        );
    }
}