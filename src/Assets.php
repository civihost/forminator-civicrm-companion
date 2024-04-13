<?php

namespace Forminator\CiviCRMCompanion;

class Assets
{
    /**
     * Create a new class instance.
     *
     * @return void
     */
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Enqueue the plugin assets.
     *
     * @return void
     */
    public function enqueue_scripts()
    {
        //wp_enqueue_style( 'ordini', FORMINATOR_CIVICRM_PLUGIN_URL . 'public/css/plugin.css', [], FORMINATOR_CIVICRM_PLUGIN_VERSION );
    }
}

new Assets;
