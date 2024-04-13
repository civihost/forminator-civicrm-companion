<?php

namespace Forminator\CiviCRMCompanion;

use Forminator\CiviCRMCompanion\CiviCRM\Contacts;
use Symfony\Component\Yaml\Parser;

class Newsletter
{
    protected $settings = [];

    /**
     * Create a new class instance.
     *
     * @return void
     */
    public function __construct()
    {
        $yaml = new Parser();
        $this->settings = $yaml->parse(file_get_contents(FORMINATOR_CIVICRM_PLUGIN_PATH . '/settings.yaml'));
        add_action('forminator_form_after_save_entry', [$this, 'formAfterSave'], 10, 2);
    }

    /**
     *
     * @param int $form_id Form ID being saved.
     *
     * @return void
     */
    public function formAfterSave($form_id, $response)
    {
        if (!isset($this->settings['forminator'][$form_id]) || !$response['success']) {
            return;
        }

        $config = $this->settings['forminator'][$form_id];

        $post = $_POST;
        //\Civi::log()->debug(FORMINATOR_CIVICRM_PLUGIN_BASENAME . ' form_id' . $form_id . ' - ' .  print_r($post, true));

        $contact_id = Contacts::create($_POST, $config);
        //\Civi::log()->debug(FORMINATOR_CIVICRM_PLUGIN_BASENAME . ' contact_id' . $contact_id);
        //throw new \Exception(FORMINATOR_CIVICRM_PLUGIN_BASENAME);
    }
}

new Newsletter;
