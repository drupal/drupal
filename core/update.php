<?php

/**
 * @file
 * Administrative page for handling updates from one Drupal version to another.
 *
 * Point your browser to "http://www.example.com/core/update.php" and follow the
 * instructions.
 *
 * If you are not logged in using either the site maintenance account or an
 * account with the "Administer software updates" permission, you will need to
 * modify the access check statement inside your settings.php file. After
 * finishing the upgrade, be sure to open settings.php again, and change it
 * back to its original state!
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Reference;

// Change the directory to the Drupal root.
chdir('..');

require_once __DIR__ . '/vendor/autoload.php';

// Exit early if an incompatible PHP version would cause fatal errors.
// The minimum version is specified explicitly, as DRUPAL_MINIMUM_PHP is not
// yet available. It is defined in bootstrap.inc, but it is not possible to
// load that file yet as it would cause a fatal error on older versions of PHP.
if (version_compare(PHP_VERSION, '5.3.10') < 0) {
  print 'Your PHP installation is too old. Drupal requires at least PHP 5.3.10. See the <a href="http://drupal.org/requirements">system requirements</a> page for more information.';
  exit;
}

/**
 * Global flag indicating that update.php is being run.
 *
 * When this flag is set, various operations do not take place, such as css/js
 * preprocessing and translation.
 *
 * This constant is defined using define() instead of const so that PHP
 * versions older than 5.3 can display the proper PHP requirements instead of
 * causing a fatal error.
 */
define('MAINTENANCE_MODE', 'update');

/**
 * Renders a form with a list of available database updates.
 */
function update_selection_page() {
  // Make sure there is no stale theme registry.
  cache()->deleteAll();

  drupal_set_title('Drupal database update');
  $elements = drupal_get_form('update_script_selection_form');
  $output = drupal_render($elements);

  update_task_list('select');

  return $output;
}

/**
 * Form constructor for the list of available database module updates.
 */
function update_script_selection_form($form, &$form_state) {
  $count = 0;
  $incompatible_count = 0;
  $form['start'] = array(
    '#tree' => TRUE,
    '#type' => 'details',
    '#collapsed' => TRUE,
  );

  // Ensure system.module's updates appear first.
  $form['start']['system'] = array();

  $updates = update_get_update_list();
  $starting_updates = array();
  $incompatible_updates_exist = FALSE;
  foreach ($updates as $module => $update) {
    if (!isset($update['start'])) {
      $form['start'][$module] = array(
        '#type' => 'item',
        '#title' => $module . ' module',
        '#markup'  => $update['warning'],
        '#prefix' => '<div class="messages messages--warning">',
        '#suffix' => '</div>',
      );
      $incompatible_updates_exist = TRUE;
      continue;
    }
    if (!empty($update['pending'])) {
      $starting_updates[$module] = $update['start'];
      $form['start'][$module] = array(
        '#type' => 'hidden',
        '#value' => $update['start'],
      );
      $form['start'][$module . '_updates'] = array(
        '#theme' => 'item_list',
        '#items' => $update['pending'],
        '#title' => $module . ' module',
      );
    }
    if (isset($update['pending'])) {
      $count = $count + count($update['pending']);
    }
  }

  // Find and label any incompatible updates.
  foreach (update_resolve_dependencies($starting_updates) as $data) {
    if (!$data['allowed']) {
      $incompatible_updates_exist = TRUE;
      $incompatible_count++;
      $module_update_key = $data['module'] . '_updates';
      if (isset($form['start'][$module_update_key]['#items'][$data['number']])) {
        $text = $data['missing_dependencies'] ? 'This update will been skipped due to the following missing dependencies: <em>' . implode(', ', $data['missing_dependencies']) . '</em>' : "This update will be skipped due to an error in the module's code.";
        $form['start'][$module_update_key]['#items'][$data['number']] .= '<div class="warning">' . $text . '</div>';
      }
      // Move the module containing this update to the top of the list.
      $form['start'] = array($module_update_key => $form['start'][$module_update_key]) + $form['start'];
    }
  }

  // Warn the user if any updates were incompatible.
  if ($incompatible_updates_exist) {
    drupal_set_message('Some of the pending updates cannot be applied because their dependencies were not met.', 'warning');
  }

  if (empty($count)) {
    drupal_set_message(t('No pending updates.'));
    unset($form);
    $form['links'] = array(
      '#theme' => 'links',
      '#links' => update_helpful_links(),
    );

    // No updates to run, so caches won't get flushed later.  Clear them now.
    update_flush_all_caches();
  }
  else {
    $form['help'] = array(
      '#markup' => '<p>The version of Drupal you are updating from has been automatically detected.</p>',
      '#weight' => -5,
    );
    if ($incompatible_count) {
      $form['start']['#title'] = format_plural(
        $count,
        '1 pending update (@number_applied to be applied, @number_incompatible skipped)',
        '@count pending updates (@number_applied to be applied, @number_incompatible skipped)',
        array('@number_applied' => $count - $incompatible_count, '@number_incompatible' => $incompatible_count)
      );
    }
    else {
      $form['start']['#title'] = format_plural($count, '1 pending update', '@count pending updates');
    }
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Apply pending updates',
    );
  }
  return $form;
}

/**
 * Provides links to the homepage and administration pages.
 */
function update_helpful_links() {
  $links['front'] = array(
    'title' => t('Front page'),
    'href' => '<front>',
  );
  if (user_access('access administration pages')) {
    $links['admin-pages'] = array(
      'title' => t('Administration pages'),
      'href' => 'admin',
    );
  }
  return $links;
}

/**
 * Remove update overrides and flush all caches.
 *
 * This will need to be run once all (if any) updates are run. Do not call this
 * while updates are running.
 */
function update_flush_all_caches() {
  unset($GLOBALS['conf']['container_service_providers']['UpdateServiceProvider']);
  \Drupal::service('kernel')->updateModules(\Drupal::moduleHandler()->getModuleList());

  // No updates to run, so caches won't get flushed later.  Clear them now.
  drupal_flush_all_caches();
}

/**
 * Displays results of the update script with any accompanying errors.
 */
function update_results_page() {
  drupal_set_title('Drupal database update');

  update_task_list();
  // Report end result.
  if (\Drupal::moduleHandler()->moduleExists('dblog') && user_access('access site reports')) {
    $log_message = ' All errors have been <a href="' . base_path() . '?q=admin/reports/dblog">logged</a>.';
  }
  else {
    $log_message = ' All errors have been logged.';
  }

  if ($_SESSION['update_success']) {
    $output = '<p>Updates were attempted. If you see no failures below, you may proceed happily back to your <a href="' . base_path() . '">site</a>. Otherwise, you may need to update your database manually.' . $log_message . '</p>';
  }
  else {
    $last = reset($_SESSION['updates_remaining']);
    list($module, $version) = array_pop($last);
    $output = '<p class="error">The update process was aborted prematurely while running <strong>update #' . $version . ' in ' . $module . '.module</strong>.' . $log_message;
    if (\Drupal::moduleHandler()->moduleExists('dblog')) {
      $output .= ' You may need to check the <code>watchdog</code> database table manually.';
    }
    $output .= '</p>';
  }

  if (settings()->get('update_free_access')) {
    $output .= "<p><strong>Reminder: don't forget to set the <code>\$settings['update_free_access']</code> value in your <code>settings.php</code> file back to <code>FALSE</code>.</strong></p>";
  }

  $links = array(
    '#theme' => 'links',
    '#links' => update_helpful_links(),
  );
  $output .= drupal_render($links);

  // Output a list of queries executed.
  if (!empty($_SESSION['update_results'])) {
    $all_messages = '';
    foreach ($_SESSION['update_results'] as $module => $updates) {
      if ($module != '#abort') {
        $module_has_message = FALSE;
        $query_messages = '';
        foreach ($updates as $number => $queries) {
          $messages = array();
          foreach ($queries as $query) {
            // If there is no message for this update, don't show anything.
            if (empty($query['query'])) {
              continue;
            }

            if ($query['success']) {
              $messages[] = '<li class="success">' . $query['query'] . '</li>';
            }
            else {
              $messages[] = '<li class="failure"><strong>Failed:</strong> ' . $query['query'] . '</li>';
            }
          }

          if ($messages) {
            $module_has_message = TRUE;
            $query_messages .= '<h4>Update #' . $number . "</h4>\n";
            $query_messages .= '<ul>' . implode("\n", $messages) . "</ul>\n";
          }
        }

        // If there were any messages in the queries then prefix them with the
        // module name and add it to the global message list.
        if ($module_has_message) {
          $all_messages .= '<h3>' . $module . " module</h3>\n" . $query_messages;
        }
      }
    }
    if ($all_messages) {
      $output .= '<div class="update-results"><h2>The following updates returned messages</h2>';
      $output .= $all_messages;
      $output .= '</div>';
    }
  }
  unset($_SESSION['update_results']);
  unset($_SESSION['update_success']);

  return $output;
}

/**
 * Provides an overview of the Drupal database update.
 *
 * This page provides cautionary suggestions that should happen before
 * proceeding with the update to ensure data integrity.
 *
 * @return
 *   Rendered HTML form.
 */
function update_info_page() {
  // Change query-strings on css/js files to enforce reload for all users.
  _drupal_flush_css_js();
  // Flush the cache of all data for the update status module.
  drupal_container()->get('keyvalue.expirable')->get('update')->deleteAll();
  drupal_container()->get('keyvalue.expirable')->get('update_available_release')->deleteAll();

  update_task_list('info');
  drupal_set_title('Drupal database update');
  $token = drupal_get_token('update');
  $output = '<p>Use this utility to update your database whenever a new release of Drupal or a module is installed.</p><p>For more detailed information, see the <a href="http://drupal.org/upgrade">upgrading handbook</a>. If you are unsure what these terms mean you should probably contact your hosting provider.</p>';
  $output .= "<ol>\n";
  $output .= "<li><strong>Back up your code</strong>. Hint: when backing up module code, do not leave that backup in the 'modules' or 'sites/*/modules' directories as this may confuse Drupal's auto-discovery mechanism.</li>\n";
  $output .= '<li>Put your site into <a href="' . base_path() . '?q=admin/config/development/maintenance">maintenance mode</a>.</li>' . "\n";
  $output .= "<li><strong>Back up your database</strong>. This process will change your database values and in case of emergency you may need to revert to a backup.</li>\n";
  $output .= "<li>Install your new files in the appropriate location, as described in the handbook.</li>\n";
  $output .= "</ol>\n";
  $output .= "<p>When you have performed the steps above, you may proceed.</p>\n";
  $form_action = check_url(drupal_current_script_url(array('op' => 'selection', 'token' => $token)));
  $output .= '<form method="post" action="' . $form_action . '"><p><input type="submit" value="Continue" class="form-submit" /></p></form>';
  $output .= "\n";
  return $output;
}

/**
 * Renders a 403 access denied page for update.php.
 *
 * @return
 *   Rendered HTML warning with 403 status.
 */
function update_access_denied_page() {
  drupal_add_http_header('Status', '403 Forbidden');
  header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
  watchdog('access denied', 'update.php', NULL, WATCHDOG_WARNING);
  drupal_set_title('Access denied');
  return '<p>Access denied. You are not authorized to access this page. Log in using either an account with the <em>administer software updates</em> permission or the site maintenance account (the account you created during installation). If you cannot log in, you will have to edit <code>settings.php</code> to bypass this access check. To do this:</p>
<ol>
 <li>With a text editor find the settings.php file on your system. From the main Drupal directory that you installed all the files into, go to <code>sites/your_site_name</code> if such directory exists, or else to <code>sites/default</code> which applies otherwise.</li>
 <li>There is a line inside your settings.php file that says <code>$settings[\'update_free_access\'] = FALSE;</code>. Change it to <code>$settings[\'update_free_access\'] = TRUE;</code>.</li>
 <li>As soon as the update.php script is done, you must change the settings.php file back to its original form with <code>$settings[\'update_free_access\'] = FALSE;</code>.</li>
 <li>To avoid having this problem in the future, remember to log in to your website using either an account with the <em>administer software updates</em> permission or the site maintenance account (the account you created during installation) before you backup your database at the beginning of the update process.</li>
</ol>';
}

/**
 * Determines if the current user is allowed to run update.php.
 *
 * @return
 *   TRUE if the current user should be granted access, or FALSE otherwise.
 */
function update_access_allowed() {
  $user = \Drupal::currentUser();

  // Allow the global variable in settings.php to override the access check.
  if (settings()->get('update_free_access')) {
    return TRUE;
  }
  // Calls to user_access() might fail during the Drupal 6 to 7 update process,
  // so we fall back on requiring that the user be logged in as user #1.
  try {
    $module_handler = \Drupal::moduleHandler();
    $module_filenames = $module_handler->getModuleList();
    $module_filenames['user'] = 'core/modules/user/user.module';
    $module_handler->setModuleList($module_filenames);
    $module_handler->reload();
    drupal_container()->get('kernel')->updateModules($module_filenames, $module_filenames);
    return user_access('administer software updates');
  }
  catch (\Exception $e) {
    return ($user->id() == 1);
  }
}

/**
 * Adds the update task list to the current page.
 */
function update_task_list($active = NULL) {
  // Default list of tasks.
  $tasks = array(
    'requirements' => 'Verify requirements',
    'info' => 'Overview',
    'select' => 'Review updates',
    'run' => 'Run updates',
    'finished' => 'Review log',
  );

  $task_list = array(
    '#theme' => 'task_list',
    '#items' => $tasks,
    '#active' => $active,
  );

  drupal_add_region_content('sidebar_first', drupal_render($task_list));
}

/**
 * Returns and stores extra requirements that apply during the update process.
 */
function update_extra_requirements($requirements = NULL) {
  static $extra_requirements = array();
  if (isset($requirements)) {
    $extra_requirements += $requirements;
  }
  return $extra_requirements;
}

/**
 * Checks update requirements and reports errors and (optionally) warnings.
 *
 * @param $skip_warnings
 *   (optional) If set to TRUE, requirement warnings will be ignored, and a
 *   report will only be issued if there are requirement errors. Defaults to
 *   FALSE.
 */
function update_check_requirements($skip_warnings = FALSE) {
  // Check requirements of all loaded modules.
  $requirements = \Drupal::moduleHandler()->invokeAll('requirements', array('update'));
  $requirements += update_extra_requirements();
  $severity = drupal_requirements_severity($requirements);

  // If there are errors, always display them. If there are only warnings, skip
  // them if the caller has indicated they should be skipped.
  if ($severity == REQUIREMENT_ERROR || ($severity == REQUIREMENT_WARNING && !$skip_warnings)) {
    update_task_list('requirements');
    drupal_set_title('Requirements problem');
    $status = array(
      '#theme' => 'status_report',
      '#requirements' => $requirements,
    );
    $status_report = drupal_render($status);
    $status_report .= 'Check the messages and <a href="' . check_url(drupal_requirements_url($severity)) . '">try again</a>.';
    drupal_add_http_header('Content-Type', 'text/html; charset=utf-8');
    $maintenance_page = array(
      '#theme' => 'maintenance_page',
      '#content' => $status_report,
    );
    print drupal_render($maintenance_page);
    exit();
  }
}


// Some unavoidable errors happen because the database is not yet up-to-date.
// Our custom error handler is not yet installed, so we just suppress them.
ini_set('display_errors', FALSE);

// We prepare a minimal bootstrap for the update requirements check to avoid
// reaching the PHP memory limit.
require_once __DIR__ . '/includes/bootstrap.inc';
require_once __DIR__ . '/includes/update.inc';
require_once __DIR__ . '/includes/common.inc';
require_once __DIR__ . '/includes/file.inc';
require_once __DIR__ . '/includes/unicode.inc';
require_once __DIR__ . '/includes/schema.inc';
update_prepare_d8_bootstrap();

// Determine if the current user has access to run update.php.
drupal_bootstrap(DRUPAL_BOOTSTRAP_VARIABLES);
require_once DRUPAL_ROOT . '/' . settings()->get('session_inc', 'core/includes/session.inc');
drupal_session_initialize();

// A request object from the HTTPFoundation to tell us about the request.
$request = Request::createFromGlobals();
\Drupal::getContainer()->set('request', $request);

// Ensure that URLs generated for the home and admin pages don't have 'update.php'
// in them.
$generator = \Drupal::urlGenerator();
$generator->setBasePath(str_replace('/core', '', $request->getBasePath()) . '/');
$generator->setScriptPath('');

// There can be conflicting 'op' parameters because both update and batch use
// this parameter name. We need the 'op' coming from a POST request to trump
// that coming from a GET request.
$op = $request->request->get('op');
if (is_null($op)) {
  $op = $request->query->get('op');
}

// Only allow the requirements check to proceed if the current user has access
// to run updates (since it may expose sensitive information about the site's
// configuration).
if (is_null($op) && update_access_allowed()) {
  require_once __DIR__ . '/includes/install.inc';
  require_once DRUPAL_ROOT . '/core/modules/system/system.install';

  // Set up $language, since the installer components require it.
  drupal_language_initialize();

  // Set up theme system for the maintenance page.
  drupal_maintenance_theme();

  // Check the update requirements for Drupal. Only report on errors at this
  // stage, since the real requirements check happens further down.
  update_check_requirements(TRUE);

  // Redirect to the update information page if all requirements were met.
  install_goto('core/update.php?op=info');
}

// Allow update_fix_d8_requirements() to run before code that can break on a
// Drupal 7 database.
drupal_bootstrap(DRUPAL_BOOTSTRAP_CODE);
update_fix_d8_requirements();
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
drupal_maintenance_theme();

// Turn error reporting back on. From now on, only fatal errors (which are
// not passed through the error handler) will cause a message to be printed.
ini_set('display_errors', TRUE);


// Only proceed with updates if the user is allowed to run them.
if (update_access_allowed()) {

  include_once __DIR__ . '/includes/install.inc';
  include_once __DIR__ . '/includes/batch.inc';
  drupal_load_updates();

  update_fix_compatibility();

  // Check the update requirements for all modules. If there are warnings, but
  // no errors, skip reporting them if the user has provided a URL parameter
  // acknowledging the warnings and indicating a desire to continue anyway. See
  // drupal_requirements_url().
  $continue = $request->query->get('continue');
  $skip_warnings = !empty($continue);
  update_check_requirements($skip_warnings);

  switch ($op) {
    // update.php ops.

    case 'selection':
      $token = $request->query->get('token');
      if (isset($token) && drupal_valid_token($token, 'update')) {
        $output = update_selection_page();
        break;
      }

    case 'Apply pending updates':
      $token = $request->query->get('token');
      if (isset($token) && drupal_valid_token($token, 'update')) {
        // Generate absolute URLs for the batch processing (using $base_root),
        // since the batch API will pass them to url() which does not handle
        // update.php correctly by default.
        $batch_url = $base_root . drupal_current_script_url();
        $redirect_url = $base_root . drupal_current_script_url(array('op' => 'results'));
        $output = update_batch($request->request->get('start'), $redirect_url, $batch_url);
        break;
      }

    case 'info':
      $output = update_info_page();
      break;

    case 'results':
      $output = update_results_page();
      break;

    // Regular batch ops : defer to batch processing API.
    default:
      update_task_list('run');
      $output = _batch_page($request);
      break;
  }
}
else {
  $output = update_access_denied_page();
}
if (isset($output) && $output) {
  // Explicitly start a session so that the update.php token will be accepted.
  drupal_session_start();
  // We defer the display of messages until all updates are done.
  $progress_page = ($batch = batch_get()) && isset($batch['running']);
  if ($output instanceof Response) {
    $output->send();
  }
  else {
    drupal_add_http_header('Content-Type', 'text/html; charset=utf-8');
    $maintenance_page = array(
      '#theme' => 'maintenance_page',
      '#content' => $output,
      '#show_messages' => !$progress_page,
    );
    print drupal_render($maintenance_page);
  }
}
