<?php

namespace Forminator\CiviCRMCompanion;

use Forminator\CiviCRMCompanion\CiviCRM\Contacts;
use Forminator\CiviCRMCompanion\Config;

class Newsletter
{
    protected static $settings = [];

    /**
     * Create a new class instance.
     *
     * @return void
     */
    public function __construct()
    {
        add_action('forminator_form_after_save_entry', [$this, 'formAfterSave'], 10, 2);
    }

    public static function getSetting($key)
    {
        $keys = explode('.', $key);
        $value = self::$settings;
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return null;
            }
            $value = $value[$k];
        }
        return $value;
    }

    /**
     *
     * @param int $form_id Form ID being saved.
     *
     * @return void
     */
    public function formAfterSave($form_id, $response)
    {
        $forminator = Config::get('forminator');
        if (!isset($forminator[$form_id]) || !$response['success']) {
            return;
        }

        $config = $forminator[$form_id];

        $contact_id = Contacts::create($_POST, $config);
        //\Civi::log()->debug(FORMINATOR_CIVICRM_PLUGIN_BASENAME . ' contact_id' . $contact_id);
        //throw new \Exception(FORMINATOR_CIVICRM_PLUGIN_BASENAME);
    }
}

new Newsletter;
