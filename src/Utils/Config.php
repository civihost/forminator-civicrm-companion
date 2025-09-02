<?php

namespace Forminator\CiviCRMCompanion;

use Symfony\Component\Yaml\Parser;

class Config
{
    public static $settings = null;

    protected static function load()
    {
        if (self::$settings === null) {
            $yaml = new Parser();
            self::$settings = $yaml->parse(file_get_contents(FORMINATOR_CIVICRM_PLUGIN_PATH . '/settings.yaml'));
        }
    }

    /**
     * Get a value from settings array using a dot notation path.
     *
     * @param string $path
     * @param mixed $default
     * @return mixed
     */
    public static function get($path, $default = null)
    {
        self::load();

        $keys = explode('.', $path);
        $value = self::$settings;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }
}
