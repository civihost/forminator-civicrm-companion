<?php

/**
 * Plugin Name: Forminator CiviCRM Companion
 * Version: 0.0.1
 *
 * Description: CiviCRM remote integration for Forminator
 * Plugin URI: https://github.com/civihost/forminator-civicrm-companion
 *
 * Author: CiviHOST
 * Author URI: https://civihost.it
 *
 * Text Domain: FORMINATOR_CIVICRM
 * Domain Path: /languages
 *
 * Requires PHP: 8.1
 * Requires at least: 6.4
 */

defined('WPINC') || die;

define('FORMINATOR_CIVICRM_PLUGIN_BASENAME', dirname(plugin_basename(__FILE__)));
define('FORMINATOR_CIVICRM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('FORMINATOR_CIVICRM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FORMINATOR_CIVICRM_PLUGIN_VERSION', '1.0.0');

require FORMINATOR_CIVICRM_PLUGIN_PATH . 'vendor/autoload.php';

require FORMINATOR_CIVICRM_PLUGIN_PATH . 'src/Assets.php';
require FORMINATOR_CIVICRM_PLUGIN_PATH . 'src/Newsletter.php';
