<?php
// vim: set ts=4 sw=4 sts=4 et:

// $Id: dev_install.php 4783 2010-12-24 09:32:25Z svowl $

/**
 * @file
 * Initiates a browser-based installation of Drupal (for development only).
 * Condition of successfull installation:
 * - xlite is need to be deployed and installed (and cache is built)
 * - path to xlite directory must be '~user/public_html/xlite/src'
 * - file sites/default/settings.php must be presented and configured
 */


error_reporting(E_ALL ^ E_DEPRECATED);
ini_set('display_errors', true);

// Exit early if running an incompatible PHP version to avoid fatal errors.
if (version_compare(PHP_VERSION, '5.3.0') < 0) {
  print 'Your PHP installation is too old. Drupal requires at least PHP 5.2.4. See the <a href="http://drupal.org/requirements">system requirements</a> page for more information.';
  exit;
}

define('DEV_MODE', 1);

$drupal_root_dir = realpath(dirname(__FILE__) . '/../src');

chdir($drupal_root_dir);

/**
 * Root directory of Drupal installation.
 */
define('DRUPAL_ROOT', getcwd());


// Drop drupal tables
if (!isset($_GET['profile']) && !isset($_POST['profile'])) {
	require_once '../.dev/db_clean.php';
}

if (isset($_GET['lcweb']) || isset($_POST['lcweb'])) {
	define('LCWEB', 1);
	$devProfile = 'litecommerce_site';

} else {
	$devProfile = 'litecommerce';
}

if (!preg_match('/~(\w+)/', $_SERVER['REQUEST_URI'], $match)) {
    die('Can\'t get ~login from the URL ' . $_SERVER['REQUEST_URI']);
}

define('LC_URI', sprintf('/~%s/xlite/src/', $match[1]));

/**
 * Global flag to indicate that site is in installation mode.
 */
define('MAINTENANCE_MODE', 'install');


$_COOKIE['lc'] = 1;


function dev_install_configure_form($form)
{
//	$form['site_information']['site_name']['#default_value'] = 'Ecommerce CMS';
	$form['site_information']['site_mail']['#default_value'] = 'rnd_tester@cdev.ru';
	$form['admin_account']['account']['name']['#default_value'] = 'master';
    $form['admin_account']['account']['mail']['#default_value'] = 'rnd_tester@cdev.ru';

    $form['server_settings']['site_default_country']['#default_value'] = 'US';

	$form['update_notifications']['update_status_module']['#default_value'] = array();

	$form['admin_account']['account']['pass'] = array(
		'#type' => 'textfield',
	    '#title' => st('Password'),
		'#required' => TRUE,
		'#default_value' => 'master',
		'#weight' => 0,
		'#size' => 25
	);

    drupal_add_js('setTimeout(function() { jQuery(\'#install-configure-form\').submit(); }, 3000);', 'inline');

	return $form;
}


// Start the installer.
require_once DRUPAL_ROOT . '/../.dev/dev_install.inc.php';

install_drupal();
