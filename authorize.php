<?php
// $Id: authorize.php,v 1.12 2010/12/29 04:07:52 webchick Exp $

/**
 * @file
 * Administrative script for running authorized file operations.
 *
 * Using this script, the site owner (the user actually owning the files on
 * the webserver) can authorize certain file-related operations to proceed
 * with elevated privileges, for example to deploy and upgrade modules or
 * themes. Users should not visit this page directly, but instead use an
 * administrative user interface which knows how to redirect the user to this
 * script as part of a multistep process. This script actually performs the
 * selected operations without loading all of Drupal, to be able to more
 * gracefully recover from errors. Access to the script is controlled by a
 * global killswitch in settings.php ('allow_authorize_operations') and via
 * the 'administer software updates' permission.
 *
 * There are helper functions for setting up an operation to run via this
 * system in modules/system/system.module. For more information, see:
 * @link authorize Authorized operation helper functions @endlink
 */

/**
 * Root directory of Drupal installation.
 */
define('DRUPAL_ROOT', getcwd());

/**
 * Global flag to identify update.php and authorize.php runs, and so
 * avoid various unwanted operations, such as hook_init() and
 * hook_exit() invokes, css/js preprocessing and translation, and
 * solve some theming issues. This flag is checked on several places
 * in Drupal code (not just authorize.php).
 */
define('MAINTENANCE_MODE', 'update');

/**
 * Render a 403 access denied page for authorize.php
 */
function authorize_access_denied_page() {
  drupal_add_http_header('Status', '403 Forbidden');
  watchdog('access denied', 'authorize.php', NULL, WATCHDOG_WARNING);
  drupal_set_title('Access denied');
  return t('You are not allowed to access this page.');
}

/**
 * Determine if the current user is allowed to run authorize.php.
 *
 * The killswitch in settings.php overrides all else, otherwise, the user must
 * have access to the 'administer software updates' permission.
 *
 * @return
 *   TRUE if the current user can run authorize.php, otherwise FALSE.
 */
function authorize_access_allowed() {
  return variable_get('allow_authorize_operations', TRUE) && user_access('administer software updates');
}

// *** Real work of the script begins here. ***

require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
require_once DRUPAL_ROOT . '/includes/session.inc';
require_once DRUPAL_ROOT . '/includes/common.inc';
require_once DRUPAL_ROOT . '/includes/file.inc';
require_once DRUPAL_ROOT . '/includes/module.inc';
require_once DRUPAL_ROOT . '/includes/ajax.inc';

// We prepare only a minimal bootstrap. This includes the database and
// variables, however, so we have access to the class autoloader registry.
drupal_bootstrap(DRUPAL_BOOTSTRAP_SESSION);

// This must go after drupal_bootstrap(), which unsets globals!
global $conf;

// We have to enable the user and system modules, even to check access and
// display errors via the maintainence theme.
$module_list['system']['filename'] = 'modules/system/system.module';
$module_list['user']['filename'] = 'modules/user/user.module';
module_list(TRUE, FALSE, FALSE, $module_list);
drupal_load('module', 'system');
drupal_load('module', 'user');

// We also want to have the language system available, but we do *NOT* want to
// actually call drupal_bootstrap(DRUPAL_BOOTSTRAP_LANGUAGE), since that would
// also force us through the DRUPAL_BOOTSTRAP_PAGE_HEADER phase, which loads
// all the modules, and that's exactly what we're trying to avoid.
drupal_language_initialize();

// Initialize the maintenance theme for this administrative script.
drupal_maintenance_theme();

$output = '';
$show_messages = TRUE;

if (authorize_access_allowed()) {
  // Load both the Form API and Batch API.
  require_once DRUPAL_ROOT . '/includes/form.inc';
  require_once DRUPAL_ROOT . '/includes/batch.inc';
  // Load the code that drives the authorize process.
  require_once DRUPAL_ROOT . '/includes/authorize.inc';

  // For the sake of Batch API and a few other low-level functions, we need to
  // initialize the URL path into $_GET['q']. However, we do not want to raise
  // our bootstrap level, nor do we want to call drupal_initialize_path(),
  // since that is assuming that modules are loaded and invoking hooks.
  // However, all we really care is if we're in the middle of a batch, in which
  // case $_GET['q'] will already be set, we just initialize it to an empty
  // string if it's not already defined.
  if (!isset($_GET['q'])) {
    $_GET['q'] = '';
  }

  if (isset($_SESSION['authorize_operation']['page_title'])) {
    drupal_set_title($_SESSION['authorize_operation']['page_title']);
  }
  else {
    drupal_set_title(t('Authorize file system changes'));
  }

  // See if we've run the operation and need to display a report.
  if (isset($_SESSION['authorize_results']) && $results = $_SESSION['authorize_results']) {

    // Clear the session out.
    unset($_SESSION['authorize_results']);
    unset($_SESSION['authorize_operation']);
    unset($_SESSION['authorize_filetransfer_info']);

    if (!empty($results['page_title'])) {
      drupal_set_title($results['page_title']);
    }
    if (!empty($results['page_message'])) {
      drupal_set_message($results['page_message']['message'], $results['page_message']['type']);
    }

    $output = theme('authorize_report', array('messages' => $results['messages']));

    $links = array();
    if (is_array($results['tasks'])) {
      $links += $results['tasks'];
    }
    else {
      $links = array_merge($links, array(
        l(t('Administration pages'), 'admin'),
        l(t('Front page'), '<front>'),
      ));
    }
	
    $output .= theme('item_list', array('items' => $links, 'title' => t('Next steps')));
  }
  // If a batch is running, let it run.
  elseif (isset($_GET['batch'])) {
    $output = _batch_page();
  }
  else {
    if (empty($_SESSION['authorize_operation']) || empty($_SESSION['authorize_filetransfer_info'])) {
      $output = t('It appears you have reached this page in error.');
    }
    elseif (!$batch = batch_get()) {
      // We have a batch to process, show the filetransfer form.
      $elements = drupal_get_form('authorize_filetransfer_form');
      $output = drupal_render($elements);
    }
  }
  // We defer the display of messages until all operations are done.
  $show_messages = !(($batch = batch_get()) && isset($batch['running']));
}
else {
  $output = authorize_access_denied_page();
}

if (!empty($output)) {
  print theme('update_page', array('content' => $output, 'show_messages' => $show_messages));
}

