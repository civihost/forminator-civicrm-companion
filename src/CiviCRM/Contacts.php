<?php

namespace Forminator\CiviCRMCompanion\CiviCRM;

use Forminator\CiviCRMCompanion\Traits\CiviCRM;

class Contacts
{
    use CiviCRM;

    /**
     * Create or update CiviCRM contact
     *
     * @param array $post
     * @param array $config
     * @return int|null
     */
    public static function create($post, $config)
    {
        $contact_id = null;
        $params = self::createContactParams($post, $config);

        try {
            $contact = self::civicrm_api3('Contact', 'create', $params);
            $contact_id = $contact['id'];
        } catch (\Exception $e) {
            $error = $e->getMessage();
            self::log('error:' . print_r($error, true));
        }
        return $contact_id;
    }
}
