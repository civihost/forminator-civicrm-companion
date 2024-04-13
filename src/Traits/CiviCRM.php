<?php

namespace Forminator\CiviCRMCompanion\Traits;

trait CiviCRM
{
    /**
     * Get the CiviCRM contact ID from email
     *
     * @param string $email
     * @return int
     */
    public static function getContactIdFromEmail($email)
    {
        try {
            $contacts = self::civicrm_api4('Contact', 'get', [
                'select' => [
                    'id',
                ],
                'join' => [
                    ['Email AS email', 'LEFT', ['email.contact_id', '=', 'id']],
                ],
                'where' => [
                    ['email.is_primary', '=', TRUE],
                    ['email.email', '=', $email],
                ],
                'checkPermissions' => FALSE,
            ]);


            foreach ($contacts as $contact) {
                return $contact['id'];
            }
        } catch (\Exception $e) {
            self::log($e->getMessage());
        }
        return 0;
    }

    /**
     * Get the array to create a CiviCRM contact from a submission form
     *
     * @param array $post Gravity Form entry object
     * @param array $config Form configuration of this plugin
     * @return array
     */
    public static function createContactParams($post, $config)
    {
        $email = $post[$config['email']['entries']['email']];
        $contact_id = self::getContactIdFromEmail($email);

        $params = [
            'id' => $contact_id,
            'contact_type' => 'Individual',
        ];
        foreach ($config['contact']['entries'] as $name => $e) {
            $params[$name] = $post[$e];
        }

        if (!$contact_id) {
            $params['api.Email.create'] = [
                'email' => $email,
                'contact_id' => '$value.id',
                'is_primary' => 1,
            ];
        }

        if (isset($config['tags'])) {
            $params['api.EntityTag.create'] = [
                'tag_id' => [],
                'contact_id' => '$value.id',
            ];
            if (isset($config['tags']['entries'])) {
                $tags = [];
                foreach ($config['tags']['entries'] as $name => $e) {
                    if ($post[$e]) {
                        $tags[] = $post[$e];
                    }
                }
                $params['api.EntityTag.create']['tag_id'] = array_merge($params['api.EntityTag.create']['tag_id'], $tags);
            }
            if (isset($config['tags']['values'])) {
                $params['api.EntityTag.create']['tag_id'] = array_merge($params['api.EntityTag.create']['tag_id'], $config['tags']['values']);
            }
        }

        if (isset($config['groups'])) {
            $params['api.GroupContact.create'] = [
                'group_id' => [],
                'contact_id' => '$value.id',
            ];
            if (isset($config['groups']['entries'])) {
                $groups = [];
                foreach ($config['groups']['entries'] as $name => $e) {
                    if ($post[$e]) {
                        $groups[] = $post[$e];
                    }
                }
                $params['api.GroupContact.create']['group_id'] = array_merge($params['api.GroupContact.create']['group_id'], $groups);
            }
            if (isset($config['groups']['values'])) {
                $params['api.GroupContact.create']['group_id'] = array_merge($params['api.GroupContact.create']['group_id'], $config['groups']['values']);
            }
        }

        // MailingEventSubscribe.create API - only one group id
        if (isset($config['subscribe'])) {
            $params['api.MailingEventSubscribe.create'] = [
                'group_id' => [],
                'contact_id' => '$value.id',
                'email' => $email,
            ];
            if (isset($config['subscribe']['entry'])) {
                if ($post[$config['groups']['entry']]) {
                    $params['api.MailingEventSubscribe.create']['group_id'] = $post[$config['groups']['entry']];
                }
            }
            if (isset($config['subscribe']['value'])) {
                $params['api.MailingEventSubscribe.create']['group_id'] = $config['subscribe']['value'];
            }
        }

        if (isset($config['activity'])) {
            $activity = [
                'source_contact_id' => '$value.id',
                'activity_type_id' => $config['activity']['type'],
                'target_id' => '$value.id',
            ];
            if (isset($config['activity']['entries'])) {
                foreach ($config['activity']['entries'] as $name => $e) {
                    $activity[$name] = $post[$e];
                }
            }
            if (isset($config['activity']['subject'])) {
                $activity['subject'] = $config['activity']['subject'];
            }
            if (isset($config['activity']['assignee'])) {
                $activity['assignee_id'] = $config['activity']['assignee'];
            }
            if (isset($config['activity']['campaign'])) {
                $activity['campaign_id'] = $config['activity']['campaign'];
            }
            if (isset($config['activity']['scheduled'])) {
                $s = $config['activity']['scheduled'];
                $scheduled_activity = [
                    'source_contact_id' => '[ID]',
                    'activity_type_id' => $s['type'],
                    'target_id' => '[ID]',
                    'activity_date_time' => date('Y-m-d', strtotime($s['date'])),
                    'status_id' => 'Scheduled',
                ];
                if (isset($s['subject'])) {
                    $scheduled_activity['subject'] = $config['activity']['subject'];
                }
                if (isset($s['assignee'])) {
                    $scheduled_activity['assignee_id'] = $s['assignee'];
                }
                if (isset($s['campaign'])) {
                    $scheduled_activity['campaign_id'] = $s['campaign'];
                }
                if (isset($s['entries'])) {
                    foreach ($s['entries'] as $name => $e) {
                        $scheduled_activity[$name] = $post[$e];
                    }
                }
            }
            $params['api.activity.create'] = $activity;
        }

        return $params;
    }

    /**
     * Updates (or creates) an entity related to a contact (phone, address, email, note).
     * @param string $entity
     * @param array $params
     *
     * @return mixed
     * @throws \CiviCRM_API3_Exception
     */
    public static function updateContactRelatedEntity($entity, $params)
    {
        $params['check_permissions'] = $params['check_permissions'] ?? FALSE;
        $result = null;
        if (empty($params['id'])) {
            $values = self::getContactRelatedEntity($entity, $params);

            if ($values) {
                foreach ($values as $r) {
                    if (isset($r['id'])) {
                        $params['id'] = $r['id'];
                        $result = self::civicrm_api3($entity, 'create', $params);
                    }
                }
            }
        } else {
            $result = self::civicrm_api3($entity, 'create', $params);
        }
        if ($result) {
            return $result['id'];
        }
    }

    /**
     * Given a set of params relevant for the given entity (ex: location_type_id,
     * phone_type_id, etc)
     *
     * Works for: Phone, Email, Address, Note.
     * @param string $entity
     * @param array $params
     *
     * @return array|null
     * @throws \CiviCRM_API3_Exception
     */
    public static function getContactRelatedEntity($entity, $params)
    {
        $params['sequential'] = 1;
        $result = NULL;

        if ($entity == 'Address') {
            // Address sometimes won't match if we 'get' with all the params.
            $result = self::civicrm_api3($entity, 'get', [
                'contact_id' => $params['contact_id'],
                'location_type_id' => $params['location_type_id'],
                'sequential' => 1,
            ]);
        } elseif ($entity == 'Phone') {
            $result = self::civicrm_api3($entity, 'get', [
                'contact_id' => $params['contact_id'],
                'location_type_id' => $params['location_type_id'],
                'phone_type_id' => $params['phone_type_id'] ?? null,
                'sequential' => 1,
            ]);
        } elseif ($entity == 'Email') {
            $result = self::civicrm_api3($entity, 'get', [
                'contact_id' => $params['contact_id'],
                'location_type_id' => $params['location_type_id'],
                'sequential' => 1,
            ]);
        } elseif ($entity == 'Note') {
            $result = self::civicrm_api3($entity, 'get', [
                'contact_id' => $params['contact_id'],
                'sequential' => 1,
            ]);
        } else {
            $result = self::civicrm_api3($entity, 'get', $params);
        }

        if ($result['count'] >= 0) {
            return $result['values'];
        }

        return NULL;
    }

    /**
     * Version 3 wrapper for civicrm_api.
     *
     * @param string $entity Type of entities to deal with.
     * @param string $action Create, get, delete or some special action name.
     * @param array $params Array to be passed to function.
     *
     * @throws CRM_Core_Exception
     *
     * @return array|int
     */
    public static function civicrm_api3(string $entity, string $action, array $params = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, CIVICRM_API3_REST_URL);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Requested-With: XMLHttpRequest"));
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        $postFields = "entity={$entity}&action={$action}&api_key=" . CIVICRM_API_KEY . "&key=" . CIVICRM_SITE_KEY . "&json=" . (json_encode($params));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        $data = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch));
        } else {
            $result = json_decode($data, true);
        }
        curl_close($ch);

        if ($result['is_error']) {
            throw new \Exception(print_r($result, true));
        }
        return $result;
    }

    /**
     * Version 4 wrapper for civicrm_api.
     *
     * @param string $entity Type of entities to deal with.
     * @param string $action Create, get, delete or some special action name.
     * @param array $params Array to be passed to function.
     *
     * @throws CRM_Core_Exception
     *
     * @return array|int
     */
    public static function civicrm_api4(string $entity, string $action, array $params = [])
    {
        $url = CIVICRM_API4_REST_URL . $entity . '/' . $action;
        $request = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'X-Civi-Auth: Bearer ' . CIVICRM_API_KEY,
                ],
                'content' => http_build_query(['params' => json_encode($params)]),
            ]
        ]);
        $result = json_decode(file_get_contents($url, FALSE, $request), TRUE);

        if (isset($result['values'])) {
            return $result['values'];
        } else {
            throw new \Exception(print_r($result, true));
        }

        /*
    $client = new \GuzzleHttp\Client([
      'base_uri' => CIVICRM_API4_REST_URL,
      'headers' => [
        //'Authorization' => 'Basic ' . CIVICRM_API_KEY,
        'X-Requested-With' => 'XMLHttpRequest',
        'X-Civi-Auth' => 'Bearer ' . CIVICRM_API_KEY,
      ],
    ]);


    $response = $client->get($entity . '/' . $action, [
      'form_params' => ['params' => json_encode($params)],
    ]);
    $result = json_decode((string) $response->getBody(), TRUE);
*/
        /*
    $ch = curl_init();
    $url = CIVICRM_API4_REST_URL . '/' . $entity . '/' . $action;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_ENCODING, "");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'X-Requested-With: XMLHttpRequest',
      'X-Civi-Auth: Bearer ' . CIVICRM_API_KEY,
    ]);
    curl_setopt($ch, CURLOPT_VERBOSE, true);

    $postFields = "entity={$entity}&action={$action}&api_key=" . CIVICRM_API_KEY . "&key=" . CIVICRM_SITE_KEY ."&json=" . (json_encode($params));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    $data = curl_exec($ch);

    if (curl_errno($ch)) {
      throw new \Exception(curl_error($ch));
    } else {
      $result = json_decode($data, true);
    }
    curl_close($ch);
    if ($result['is_error']) {
      throw new \Exception(print_r($result, true));
    }
    return $result;
    */
    }

    public static function log($message)
    {
        if (WP_DEBUG === true) {
            if (is_array($message) || is_object($message)) {
                error_log(print_r($message, true));
            } else {
                error_log(FORMINATOR_CIVICRM_PLUGIN_BASENAME . ' ' . $message);
            }
        }
    }
}
