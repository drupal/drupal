<?php

use Drupal\Component\Utility\UserAgent;
use Drupal\Component\Utility\Crypt;

use Drupal\Component\Utility\Settings;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\Install\TaskException;
use Drupal\Core\Installer\Exception\AlreadyInstalledException;
use Drupal\Core\Installer\Exception\InstallerException;
use Drupal\Core\Installer\Exception\NoProfilesException;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\StringTranslation\Translator\FileTranslation;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Guzzle\Http\Exception\RequestException;

/**
 * @file
 * API functions for installing Drupal.
 */

/**
 * Do not run the task during the current installation request.
 *
 * This can be used to skip running an installation task when certain
 * conditions are met, even though the task may still show on the list of
 * installation tasks presented to the user. For example, the Drupal installer
 * uses this flag to skip over the database configuration form when valid
 * database connection information is already available from settings.php. It
 * also uses this flag to skip language import tasks when the installation is
 * being performed in English.
 */
const INSTALL_TASK_SKIP = 1;

/**
 * Run the task on each installation request that reaches it.
 *
 * This is primarily used by the Drupal installer for bootstrap-related tasks.
 */
const INSTALL_TASK_RUN_IF_REACHED = 2;

/**
 * Run the task on each installation request until the database is set up.
 *
 * This is the default method for running tasks and should be used for most
 * tasks that occur after the database is set up; these tasks will then run
 * once and be marked complete once they are successfully finished. For
 * example, the Drupal installer uses this flag for the batch installation of
 * modules on the new site, and also for the configuration form that collects
 * basic site information and sets up the site maintenance account.
 */
const INSTALL_TASK_RUN_IF_NOT_COMPLETED = 3;

/**
 * Installs Drupal either interactively or via an array of passed-in settings.
 *
 * The Drupal installation happens in a series of steps, which may be spread
 * out over multiple page requests. Each request begins by trying to determine
 * the last completed installation step (also known as a "task"), if one is
 * available from a previous request. Control is then passed to the task
 * handler, which processes the remaining tasks that need to be run until (a)
 * an error is thrown, (b) a new page needs to be displayed, or (c) the
 * installation finishes (whichever happens first).
 *
 * @param $settings
 *   An optional array of installation settings. Leave this empty for a normal,
 *   interactive, browser-based installation intended to occur over multiple
 *   page requests. Alternatively, if an array of settings is passed in, the
 *   installer will attempt to use it to perform the installation in a single
 *   page request (optimized for the command line) and not send any output
 *   intended for the web browser. See install_state_defaults() for a list of
 *   elements that are allowed to appear in this array.
 *
 * @see install_state_defaults()
 */
function install_drupal($settings = array()) {
  global $install_state;
  // Initialize the installation state with the settings that were passed in,
  // as well as a boolean indicating whether or not this is an interactive
  // installation.
  $interactive = empty($settings);
  $install_state = $settings + array('interactive' => $interactive) + install_state_defaults();

  try {
    // Begin the page request. This adds information about the current state of
    // the Drupal installation to the passed-in array.
    install_begin_request($install_state);
    // Based on the installation state, run the remaining tasks for this page
    // request, and collect any output.
    $output = install_run_tasks($install_state);
  }
  catch (InstallerException $e) {
    // In the non-interactive installer, exceptions are always thrown directly.
    if (!$install_state['interactive']) {
      throw $e;
    }
    $output = array(
      '#title' => $e->getTitle(),
      '#markup' => $e->getMessage(),
    );
  }

  // After execution, all tasks might be complete, in which case
  // $install_state['installation_finished'] is TRUE. In case the last task
  // has been processed, remove the global $install_state, so other code can
  // reliably check whether it is running during the installer.
  // @see drupal_installation_attempted()
  $state = $install_state;
  if (!empty($install_state['installation_finished'])) {
    unset($GLOBALS['install_state']);
  }

  // All available tasks for this page request are now complete. Interactive
  // installations can send output to the browser or redirect the user to the
  // next page.
  if ($state['interactive']) {
    if ($state['parameters_changed']) {
      // Redirect to the correct page if the URL parameters have changed.
      install_goto(install_redirect_url($state));
    }
    elseif (isset($output)) {
      // Display a page only if some output is available. Otherwise it is
      // possible that we are printing a JSON page and theme output should
      // not be shown.
      install_display_output($output, $state);
    }
  }
}

/**
 * Returns an array of default settings for the global installation state.
 *
 * The installation state is initialized with these settings at the beginning
 * of each page request. They may evolve during the page request, but they are
 * initialized again once the next request begins.
 *
 * Non-interactive Drupal installations can override some of these default
 * settings by passing in an array to the installation script, most notably
 * 'parameters' (which contains one-time parameters such as 'profile' and
 * 'langcode' that are normally passed in via the URL) and 'forms' (which can
 * be used to programmatically submit forms during the installation; the keys
 * of each element indicate the name of the installation task that the form
 * submission is for, and the values are used as the $form_state['values']
 * array that is passed on to the form submission via drupal_form_submit()).
 *
 * @see drupal_form_submit()
 */
function install_state_defaults() {
  $defaults = array(
    // The current task being processed.
    'active_task' => NULL,
    // The last task that was completed during the previous installation
    // request.
    'completed_task' => NULL,
    // TRUE when there are valid config directories.
    'config_verified' => FALSE,
    // TRUE when there is a valid database connection.
    'database_verified' => FALSE,
    // TRUE when a valid settings.php exists (containing both database
    // connection information and config directory names).
    'settings_verified' => FALSE,
    // TRUE when the base system has been installed and is ready to operate.
    'base_system_verified' => FALSE,
    // Whether a translation file for the selected language will be downloaded
    // from the translation server.
    'download_translation' => FALSE,
    // An array of forms to be programmatically submitted during the
    // installation. The keys of each element indicate the name of the
    // installation task that the form submission is for, and the values are
    // used as the $form_state['values'] array that is passed on to the form
    // submission via drupal_form_submit().
    'forms' => array(),
    // This becomes TRUE only at the end of the installation process, after
    // all available tasks have been completed and Drupal is fully installed.
    // It is used by the installer to store correct information in the database
    // about the completed installation, as well as to inform theme functions
    // that all tasks are finished (so that the task list can be displayed
    // correctly).
    'installation_finished' => FALSE,
    // Whether or not this installation is interactive. By default this will
    // be set to FALSE if settings are passed in to install_drupal().
    'interactive' => TRUE,
    // An array of parameters for the installation, pre-populated by the URL
    // or by the settings passed in to install_drupal(). This is primarily
    // used to store 'profile' (the name of the chosen installation profile)
    // and 'langcode' (the code of the chosen installation language), since
    // these settings need to persist from page request to page request before
    // the database is available for storage.
    'parameters' => array(),
    // Whether or not the parameters have changed during the current page
    // request. For interactive installations, this will trigger a page
    // redirect.
    'parameters_changed' => FALSE,
    // An array of information about the chosen installation profile. This will
    // be filled in based on the profile's .info.yml file.
    'profile_info' => array(),
    // An array of available installation profiles.
    'profiles' => array(),
    // The name of the theme to use during installation.
    'theme' => 'seven',
    // The server URL where the interface translation files can be downloaded.
    // Tokens in the pattern will be replaced by appropriate values for the
    // required translation file.
    'server_pattern' => 'http://ftp.drupal.org/files/translations/%core/%project/%project-%version.%language.po',
    // Installation tasks can set this to TRUE to force the page request to
    // end (even if there is no themable output), in the case of an interactive
    // installation. This is needed only rarely; for example, it would be used
    // by an installation task that prints JSON output rather than returning a
    // themed page. The most common example of this is during batch processing,
    // but the Drupal installer automatically takes care of setting this
    // parameter properly in that case, so that individual installation tasks
    // which implement the batch API do not need to set it themselves.
    'stop_page_request' => FALSE,
    // Installation tasks can set this to TRUE to indicate that the task should
    // be run again, even if it normally wouldn't be. This can be used, for
    // example, if a single task needs to be spread out over multiple page
    // requests, or if it needs to perform some validation before allowing
    // itself to be marked complete. The most common examples of this are batch
    // processing and form submissions, but the Drupal installer automatically
    // takes care of setting this parameter properly in those cases, so that
    // individual installation tasks which implement the batch API or form API
    // do not need to set it themselves.
    'task_not_complete' => FALSE,
    // A list of installation tasks which have already been performed during
    // the current page request.
    'tasks_performed' => array(),
    // An array of translation files URIs available for the installation. Keyed
    // by the translation language code.
    'translations' => array(),
  );
  return $defaults;
}

/**
 * Begins an installation request, modifying the installation state as needed.
 *
 * This function performs commands that must run at the beginning of every page
 * request. It throws an exception if the installation should not proceed.
 *
 * @param $install_state
 *   An array of information about the current installation state. This is
 *   modified with information gleaned from the beginning of the page request.
 */
function install_begin_request(&$install_state) {
  $request = Request::createFromGlobals();

  // Add any installation parameters passed in via the URL.
  if ($install_state['interactive']) {
    $install_state['parameters'] += $request->query->all();
  }

  // Validate certain core settings that are used throughout the installation.
  if (!empty($install_state['parameters']['profile'])) {
    $install_state['parameters']['profile'] = preg_replace('/[^a-zA-Z_0-9]/', '', $install_state['parameters']['profile']);
  }
  if (!empty($install_state['parameters']['langcode'])) {
    $install_state['parameters']['langcode'] = preg_replace('/[^a-zA-Z_0-9\-]/', '', $install_state['parameters']['langcode']);
  }

  // Allow command line scripts to override server variables used by Drupal.
  require_once __DIR__ . '/bootstrap.inc';

  // Initialize conf_path().
  // This primes the site path to be used during installation. By not requiring
  // settings.php, a bare site folder can be prepared in the /sites directory,
  // which will be used for installing Drupal.
  conf_path(FALSE);

  // If the hash salt leaks, it becomes possible to forge a valid testing user
  // agent, install a new copy of Drupal, and take over the original site.
  // The user agent header is used to pass a database prefix in the request when
  // running tests. However, for security reasons, it is imperative that no
  // installation be permitted using such a prefix.
  if ($install_state['interactive'] && strpos($request->server->get('HTTP_USER_AGENT'), 'simpletest') !== FALSE && !drupal_valid_test_ua()) {
    header($request->server->get('SERVER_PROTOCOL') . ' 403 Forbidden');
    exit;
  }

  drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION);

  // Ensure that procedural dependencies are loaded as early as possible,
  // since the error/exception handlers depend on them.
  require_once __DIR__ . '/../modules/system/system.install';
  require_once __DIR__ . '/common.inc';
  require_once __DIR__ . '/file.inc';
  require_once __DIR__ . '/install.inc';
  require_once __DIR__ . '/schema.inc';
  require_once __DIR__ . '/../../' . settings()->get('path_inc', 'core/includes/path.inc');
  require_once __DIR__ . '/database.inc';
  require_once __DIR__ . '/form.inc';
  require_once __DIR__ . '/batch.inc';
  require_once __DIR__ . '/ajax.inc';

  // Load module basics (needed for hook invokes).
  include_once __DIR__ . '/module.inc';
  include_once __DIR__ . '/session.inc';
  require_once __DIR__ . '/entity.inc';

  // Create a minimal mocked container to support calls to t() in the pre-kernel
  // base system verification code paths below. The strings are not actually
  // used or output for these calls.
  // @todo Separate API level checks from UI-facing error messages.
  $container = new ContainerBuilder();
  $container->setParameter('language.default_values', Language::$defaultValues);
  $container
    ->register('language.default', 'Drupal\Core\Language\LanguageDefault')
    ->addArgument('%language.default_values%');
  $container
    ->register('language_manager', 'Drupal\Core\Language\LanguageManager')
    ->addArgument(new Reference('language.default'));
  $container
    ->register('string_translation', 'Drupal\Core\StringTranslation\TranslationManager')
    ->addArgument(new Reference('language_manager'));

  \Drupal::setContainer($container);

  // Determine whether base system services are ready to operate.
  $install_state['config_verified'] = install_verify_config_directory(CONFIG_ACTIVE_DIRECTORY) && install_verify_config_directory(CONFIG_STAGING_DIRECTORY);
  $install_state['database_verified'] = install_verify_database_settings();
  $install_state['settings_verified'] = $install_state['config_verified'] && $install_state['database_verified'];

  if ($install_state['settings_verified']) {
    try {
      $system_schema = system_schema();
      end($system_schema);
      $table = key($system_schema);
      $install_state['base_system_verified'] = Database::getConnection()->schema()->tableExists($table);
    }
    catch (DatabaseExceptionWrapper $e) {
      // The last defined table of the base system_schema() does not exist yet.
      // $install_state['base_system_verified'] defaults to FALSE, so the code
      // following below will use the minimal installer service container.
      // As soon as the base system is verified here, the installer operates in
      // a full and regular Drupal environment, without any kind of exceptions.
    }
  }

  // Replace services with in-memory and null implementations. This kernel is
  // replaced with a regular one in drupal_install_system().
  if (!$install_state['base_system_verified']) {
    $environment = 'install';
    $GLOBALS['conf']['container_service_providers']['InstallerServiceProvider'] = 'Drupal\Core\Installer\InstallerServiceProvider';
  }
  else {
    $environment = 'prod';
  }

  $kernel = new DrupalKernel($environment, drupal_classloader(), FALSE);
  $kernel->boot();

  // Enter the request scope and add the Request.
  // @todo Remove this after converting all installer screens into controllers.
  $container = $kernel->getContainer();
  $container->enterScope('request');
  $container->set('request', $request, 'request');

  // Register the file translation service.
  if (isset($GLOBALS['config']['locale.settings']['translation.path'])) {
    $directory = $GLOBALS['config']['locale.settings']['translation.path'];
  }
  else {
    $directory = conf_path() . '/files/translations';
  }
  $container->set('string_translator.file_translation', new FileTranslation($directory));
  $container->get('string_translation')
    ->addTranslator($container->get('string_translator.file_translation'));

  // Add in installation language if present.
  if (isset($install_state['parameters']['langcode'])) {
    \Drupal::translation()->setDefaultLangcode($install_state['parameters']['langcode']);
  }

  // Add list of all available profiles to the installation state.
  $listing = new ExtensionDiscovery();
  $listing->setProfileDirectories(array());
  $install_state['profiles'] += $listing->scan('profile');

  // Prime drupal_get_filename()'s static cache.
  foreach ($install_state['profiles'] as $name => $profile) {
    drupal_get_filename('profile', $name, $profile->getPathname());
  }

  if ($profile = _install_select_profile($install_state)) {
    $install_state['parameters']['profile'] = $profile;
    install_load_profile($install_state);
    if (isset($install_state['profile_info']['distribution']['install']['theme'])) {
      $install_state['theme'] = $install_state['profile_info']['distribution']['install']['theme'];
    }
  }

  // Override the module list with a minimal set of modules.
  $module_handler = \Drupal::moduleHandler();
  if (!$module_handler->moduleExists('system')) {
    $module_handler->addModule('system', 'core/modules/system');
  }
  if ($profile && !$module_handler->moduleExists($profile)) {
    $module_handler->addProfile($profile, $install_state['profiles'][$profile]->getPath());
  }
  // After setting up a custom and finite module list in a custom low-level
  // bootstrap like here, ensure to use ModuleHandler::loadAll() so that
  // ModuleHandler::isLoaded() returns TRUE, since that is a condition being
  // checked by other subsystems (e.g., the theme system).
  $module_handler->loadAll();

  // Prepare for themed output. We need to run this at the beginning of the
  // page request to avoid a different theme accidentally getting set. (We also
  // need to run it even in the case of command-line installations, to prevent
  // any code in the installer that happens to initialize the theme system from
  // accessing the database before it is set up yet.)
  drupal_maintenance_theme();

  if ($install_state['database_verified']) {
    // Verify the last completed task in the database, if there is one.
    $task = install_verify_completed_task();
  }
  else {
    $task = NULL;

    // Do not install over a configured settings.php.
    if (!empty($GLOBALS['databases'])) {
      throw new AlreadyInstalledException($container->get('string_translation'));
    }
  }

  // Ensure that the active configuration directory is empty before installation
  // starts.
  if ($install_state['config_verified'] && empty($task)) {
    $config = glob(config_get_config_directory(CONFIG_ACTIVE_DIRECTORY) . '/*.' . FileStorage::getFileExtension());
    if (!empty($config)) {
      $task = NULL;
      throw new AlreadyInstalledException($container->get('string_translation'));
    }
  }

  // Modify the installation state as appropriate.
  $install_state['completed_task'] = $task;
}

/**
 * Runs all tasks for the current installation request.
 *
 * In the case of an interactive installation, all tasks will be attempted
 * until one is reached that has output which needs to be displayed to the
 * user, or until a page redirect is required. Otherwise, tasks will be
 * attempted until the installation is finished.
 *
 * @param $install_state
 *   An array of information about the current installation state. This is
 *   passed along to each task, so it can be modified if necessary.
 *
 * @return
 *   HTML output from the last completed task.
 */
function install_run_tasks(&$install_state) {
  do {
    // Obtain a list of tasks to perform. The list of tasks itself can be
    // dynamic (e.g., some might be defined by the installation profile,
    // which is not necessarily known until the earlier tasks have run),
    // so we regenerate the remaining tasks based on the installation state,
    // each time through the loop.
    $tasks_to_perform = install_tasks_to_perform($install_state);
    // Run the first task on the list.
    reset($tasks_to_perform);
    $task_name = key($tasks_to_perform);
    $task = array_shift($tasks_to_perform);
    $install_state['active_task'] = $task_name;
    $original_parameters = $install_state['parameters'];
    $output = install_run_task($task, $install_state);
    $install_state['parameters_changed'] = ($install_state['parameters'] != $original_parameters);
    // Store this task as having been performed during the current request,
    // and save it to the database as completed, if we need to and if the
    // database is in a state that allows us to do so. Also mark the
    // installation as 'done' when we have run out of tasks.
    if (!$install_state['task_not_complete']) {
      $install_state['tasks_performed'][] = $task_name;
      $install_state['installation_finished'] = empty($tasks_to_perform);
      if ($task['run'] == INSTALL_TASK_RUN_IF_NOT_COMPLETED || $install_state['installation_finished']) {
        \Drupal::state()->set('install_task', $install_state['installation_finished'] ? 'done' : $task_name);
      }
    }
    // Stop when there are no tasks left. In the case of an interactive
    // installation, also stop if we have some output to send to the browser,
    // the URL parameters have changed, or an end to the page request was
    // specifically called for.
    $finished = empty($tasks_to_perform) || ($install_state['interactive'] && (isset($output) || $install_state['parameters_changed'] || $install_state['stop_page_request']));
  } while (!$finished);
  return $output;
}

/**
 * Runs an individual installation task.
 *
 * @param $task
 *   An array of information about the task to be run.
 * @param $install_state
 *   An array of information about the current installation state. This is
 *   passed in by reference so that it can be modified by the task.
 *
 * @return
 *   The output of the task function, if there is any.
 */
function install_run_task($task, &$install_state) {
  $function = $task['function'];

  if ($task['type'] == 'form') {
    return install_get_form($function, $install_state);
  }
  elseif ($task['type'] == 'batch') {
    // Start a new batch based on the task function, if one is not running
    // already.
    $current_batch = \Drupal::state()->get('install_current_batch');
    if (!$install_state['interactive'] || !$current_batch) {
      $batch = $function($install_state);
      if (empty($batch)) {
        // If the task did some processing and decided no batch was necessary,
        // there is nothing more to do here.
        return;
      }
      batch_set($batch);
      // For interactive batches, we need to store the fact that this batch
      // task is currently running. Otherwise, we need to make sure the batch
      // will complete in one page request.
      if ($install_state['interactive']) {
        \Drupal::state()->set('install_current_batch', $function);
      }
      else {
        $batch =& batch_get();
        $batch['progressive'] = FALSE;
      }
      // Process the batch. For progressive batches, this will redirect.
      // Otherwise, the batch will complete.
      $response = batch_process(install_redirect_url($install_state), install_full_redirect_url($install_state));
      if ($response instanceof Response) {
        // Save $_SESSION data from batch.
        drupal_session_commit();
        // Send the response.
        $response->send();
        exit;
      }
    }
    // If we are in the middle of processing this batch, keep sending back
    // any output from the batch process, until the task is complete.
    elseif ($current_batch == $function) {
      $output = _batch_page(\Drupal::request());
      // Because Batch API now returns a JSON response for intermediary steps,
      // but the installer doesn't handle Response objects yet, just send the
      // output here and emulate the old model.
      // @todo Replace this when we refactor the installer to use a request-
      //   response workflow.
      if ($output instanceof Response) {
        $output->send();
        $output = NULL;
      }
      // The task is complete when we try to access the batch page and receive
      // FALSE in return, since this means we are at a URL where we are no
      // longer requesting a batch ID.
      if ($output === FALSE) {
        // Return nothing so the next task will run in the same request.
        \Drupal::state()->delete('install_current_batch');
        return;
      }
      else {
        // We need to force the page request to end if the task is not
        // complete, since the batch API sometimes prints JSON output
        // rather than returning a themed page.
        $install_state['task_not_complete'] = $install_state['stop_page_request'] = TRUE;
        return $output;
      }
    }
  }

  else {
    // For normal tasks, just return the function result, whatever it is.
    return $function($install_state);
  }
}

/**
 * Returns a list of tasks to perform during the current installation request.
 *
 * Note that the list of tasks can change based on the installation state as
 * the page request evolves (for example, if an installation profile hasn't
 * been selected yet, we don't yet know which profile tasks need to be run).
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @return
 *   A list of tasks to be performed, with associated metadata.
 */
function install_tasks_to_perform($install_state) {
  // Start with a list of all currently available tasks.
  $tasks = install_tasks($install_state);
  foreach ($tasks as $name => $task) {
    // Remove any tasks that were already performed or that never should run.
    // Also, if we started this page request with an indication of the last
    // task that was completed, skip that task and all those that come before
    // it, unless they are marked as always needing to run.
    if ($task['run'] == INSTALL_TASK_SKIP || in_array($name, $install_state['tasks_performed']) || (!empty($install_state['completed_task']) && empty($completed_task_found) && $task['run'] != INSTALL_TASK_RUN_IF_REACHED)) {
      unset($tasks[$name]);
    }
    if (!empty($install_state['completed_task']) && $name == $install_state['completed_task']) {
      $completed_task_found = TRUE;
    }
  }
  return $tasks;
}

/**
 * Returns a list of all tasks the installer currently knows about.
 *
 * This function will return tasks regardless of whether or not they are
 * intended to run on the current page request. However, the list can change
 * based on the installation state (for example, if an installation profile
 * hasn't been selected yet, we don't yet know which profile tasks will be
 * available).
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @return
 *   A list of tasks, with associated metadata.
 */
function install_tasks($install_state) {
  // Determine whether a translation file must be imported during the
  // 'install_import_translations' task. Import when a non-English language is
  // available and selected.
  $needs_translations = count($install_state['translations']) > 1 && !empty($install_state['parameters']['langcode']) && $install_state['parameters']['langcode'] != 'en';
  // Determine whether a translation file must be downloaded during the
  // 'install_download_translation' task. Download when a non-English language
  // is selected, but no translation is yet in the translations directory.
  $needs_download = isset($install_state['parameters']['langcode']) && !isset($install_state['translations'][$install_state['parameters']['langcode']]) && $install_state['parameters']['langcode'] != 'en';

  // Start with the core installation tasks that run before handing control
  // to the installation profile.
  $tasks = array(
    'install_select_language' => array(
      'display_name' => t('Choose language'),
      'run' => INSTALL_TASK_RUN_IF_REACHED,
    ),
    'install_download_translation' => array(
      'run' => $needs_download ? INSTALL_TASK_RUN_IF_REACHED : INSTALL_TASK_SKIP,
    ),
    'install_select_profile' => array(
      'display_name' => t('Choose profile'),
      'display' => empty($install_state['profile_info']['distribution']['name']) && count($install_state['profiles']) != 1,
      'run' => INSTALL_TASK_RUN_IF_REACHED,
    ),
    'install_load_profile' => array(
      'run' => INSTALL_TASK_RUN_IF_REACHED,
    ),
    'install_verify_requirements' => array(
      'display_name' => t('Verify requirements'),
    ),
    'install_settings_form' => array(
      'display_name' => t('Set up database'),
      'type' => 'form',
      // Even though the form only allows the user to enter database settings,
      // we still need to display it if settings.php is invalid in any way,
      // since the form submit handler is where settings.php is rewritten.
      'run' => $install_state['settings_verified'] ? INSTALL_TASK_SKIP : INSTALL_TASK_RUN_IF_NOT_COMPLETED,
    ),
    'install_base_system' => array(
      'run' => $install_state['base_system_verified'] ? INSTALL_TASK_SKIP : INSTALL_TASK_RUN_IF_NOT_COMPLETED,
    ),
    // All tasks below are executed in a regular, full Drupal environment.
    'install_bootstrap_full' => array(
      'run' => INSTALL_TASK_RUN_IF_REACHED,
    ),
    'install_profile_modules' => array(
      'display_name' => t('Install site'),
      'type' => 'batch',
    ),
    'install_import_translations' => array(
      'display_name' => t('Set up translations'),
      'display' => $needs_translations,
      'type' => 'batch',
      'run' => $needs_translations ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
    ),
    'install_configure_form' => array(
      'display_name' => t('Configure site'),
      'type' => 'form',
    ),
  );

  // Now add any tasks defined by the installation profile.
  if (!empty($install_state['parameters']['profile'])) {
    // Load the profile install file, because it is not always loaded when
    // hook_install_tasks() is invoked (e.g. batch processing).
    $profile = $install_state['parameters']['profile'];
    $profile_install_file = $install_state['profiles'][$profile]->getPath() . '/' . $profile . '.install';
    if (file_exists($profile_install_file)) {
      include_once DRUPAL_ROOT . '/' . $profile_install_file;
    }
    $function = $install_state['parameters']['profile'] . '_install_tasks';
    if (function_exists($function)) {
      $result = $function($install_state);
      if (is_array($result)) {
        $tasks += $result;
      }
    }
  }

  // Finish by adding the remaining core tasks.
  $tasks += array(
    'install_import_translations_remaining' => array(
      'display_name' => t('Finish translations'),
      'display' => $needs_translations,
      'type' => 'batch',
      'run' => $needs_translations ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
    ),
    'install_update_configuration_translations' => array(
      'display_name' => t('Translate configuration'),
      'display' => $needs_translations,
      'type' => 'batch',
      'run' => $needs_translations ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
    ),
    'install_finished' => array(
      'display_name' => t('Finished'),
    ),
  );

  // Allow the installation profile to modify the full list of tasks.
  if (!empty($install_state['parameters']['profile'])) {
    $profile = $install_state['parameters']['profile'];
    if ($install_state['profiles'][$profile]->load()) {
      $function = $install_state['parameters']['profile'] . '_install_tasks_alter';
      if (function_exists($function)) {
        $function($tasks, $install_state);
      }
    }
  }

  // Fill in default parameters for each task before returning the list.
  foreach ($tasks as $task_name => &$task) {
    $task += array(
      'display_name' => NULL,
      'display' => !empty($task['display_name']),
      'type' => 'normal',
      'run' => INSTALL_TASK_RUN_IF_NOT_COMPLETED,
      'function' => $task_name,
    );
  }
  return $tasks;
}

/**
 * Returns a list of tasks that should be displayed to the end user.
 *
 * The output of this function is a list suitable for sending to
 * theme_task_list().
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @return
 *   A list of tasks, with keys equal to the machine-readable task name and
 *   values equal to the name that should be displayed.
 *
 * @see theme_task_list()
 */
function install_tasks_to_display($install_state) {
  $displayed_tasks = array();
  foreach (install_tasks($install_state) as $name => $task) {
    if ($task['display']) {
      $displayed_tasks[$name] = $task['display_name'];
    }
  }
  return $displayed_tasks;
}

/**
 * Builds and processes a form for the installer environment.
 *
 * Ensures that FormBuilder does not redirect after submitting a form, since the
 * installer uses a custom step/flow logic via install_run_tasks().
 *
 * @param string|array $form_id
 *   The form ID to build and process.
 * @param array $install_state
 *   The current state of the installation.
 *
 * @return array|null
 *   A render array containing the form to render, or NULL in case the form was
 *   successfully submitted.
 *
 * @throws \Drupal\Core\Installer\Exception\InstallerException
 */
function install_get_form($form_id, array &$install_state) {
  // Ensure the form will not redirect, since install_run_tasks() uses a custom
  // redirection logic.
  $form_state = array(
    'build_info' => array(
      'args' => array(&$install_state),
    ),
    'no_redirect' => TRUE,
  );
  if ($install_state['interactive']) {
    $form = drupal_build_form($form_id, $form_state);
    // If the form submission was not successful, the form needs to be rendered,
    // which means the task is not complete yet.
    if (empty($form_state['executed'])) {
      $install_state['task_not_complete'] = TRUE;
      return $form;
    }
  }
  else {
    // For non-interactive installs, submit the form programmatically with the
    // values taken from the installation state.
    $form_state['values'] = array();
    if (!empty($install_state['forms'][$form_id])) {
      $form_state['values'] = $install_state['forms'][$form_id];
    }
    drupal_form_submit($form_id, $form_state);

    // Throw an exception in case of any form validation error.
    if ($errors = form_get_errors($form_state)) {
      throw new InstallerException(implode("\n", $errors));
    }
  }
}

/**
 * Returns the URL that should be redirected to during an installation request.
 *
 * The output of this function is suitable for sending to install_goto().
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @return
 *   The URL to redirect to.
 *
 * @see install_full_redirect_url()
 */
function install_redirect_url($install_state) {
  return 'core/install.php?' . drupal_http_build_query($install_state['parameters']);
}

/**
 * Returns the complete URL redirected to during an installation request.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @return
 *   The complete URL to redirect to.
 *
 * @see install_redirect_url()
 */
function install_full_redirect_url($install_state) {
  global $base_url;
  return $base_url . '/' . install_redirect_url($install_state);
}

/**
 * Displays themed installer output and ends the page request.
 *
 * Installation tasks should use #title to set the desired page
 * title, but otherwise this function takes care of theming the overall page
 * output during every step of the installation.
 *
 * @param $output
 *   The content to display on the main part of the page.
 * @param $install_state
 *   An array of information about the current installation state.
 */
function install_display_output($output, $install_state) {
  // Ensure the maintenance theme is initialized.
  // The regular initialization call in install_begin_request() may not be
  // reached in case of an early installer error.
  drupal_maintenance_theme();

  drupal_page_header();

  // Prevent install.php from being indexed when installed in a sub folder.
  // robots.txt rules are not read if the site is within domain.com/subfolder
  // resulting in /subfolder/install.php being found through search engines.
  // When settings.php is writeable this can be used via an external database
  // leading a malicious user to gain php access to the server.
  $noindex_meta_tag = array(
    '#tag' => 'meta',
    '#attributes' => array(
      'name' => 'robots',
      'content' => 'noindex, nofollow',
    ),
  );
  drupal_add_html_head($noindex_meta_tag, 'install_meta_robots');

  // Only show the task list if there is an active task; otherwise, the page
  // request has ended before tasks have even been started, so there is nothing
  // meaningful to show.
  if (isset($install_state['active_task'])) {
    // Let the theming function know when every step of the installation has
    // been completed.
    $active_task = $install_state['installation_finished'] ? NULL : $install_state['active_task'];
    $task_list = array(
      '#theme' => 'task_list',
      '#items' => install_tasks_to_display($install_state),
      '#active' => $active_task,
      '#variant' => 'install',
    );
    drupal_add_region_content('sidebar_first', drupal_render($task_list));
  }
  $install_page = array(
    '#theme' => 'install_page',
    // $output has to be rendered here, because the install page template is not
    // wrapped into the html template, which means that any #attached libraries
    // in $output will not be loaded, because the wrapping HTML has been printed
    // already.
    '#content' => drupal_render($output),
  );
  if (isset($output['#title'])) {
    $install_page['#page']['#title'] = $output['#title'];
  }
  print drupal_render($install_page);
  exit;
}

/**
 * Verifies the requirements for installing Drupal.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @return
 *   A themed status report, or an exception if there are requirement errors.
 */
function install_verify_requirements(&$install_state) {
  // Check the installation requirements for Drupal and this profile.
  $requirements = install_check_requirements($install_state);

  // Verify existence of all required modules.
  $requirements += drupal_verify_profile($install_state);

  return install_display_requirements($install_state, $requirements);
}

/**
 * Installation task; install the base functionality Drupal needs to bootstrap.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 */
function install_base_system(&$install_state) {
  // Install system.module.
  drupal_install_system($install_state);

  // Call file_ensure_htaccess() to ensure that all of Drupal's standard
  // directories (e.g., the public files directory and config directory) have
  // appropriate .htaccess files. These directories will have already been
  // created by this point in the installer, since Drupal creates them during
  // the install_verify_requirements() task. Note that we cannot call
  // file_ensure_access() any earlier than this, since it relies on
  // system.module in order to work.
  file_ensure_htaccess();

  // Enable the user module so that sessions can be recorded during the
  // upcoming bootstrap step.
  \Drupal::moduleHandler()->install(array('user'), FALSE);

  // Save the list of other modules to install for the upcoming tasks.
  // State can be set to the database now that system.module is installed.
  $modules = $install_state['profile_info']['dependencies'];

  // The installation profile is also a module, which needs to be installed
  // after all the dependencies have been installed.
  $modules[] = drupal_get_profile();

  \Drupal::state()->set('install_profile_modules', array_diff($modules, array('system')));
  $install_state['base_system_verified'] = TRUE;
}

/**
 * Verifies and returns the last installation task that was completed.
 *
 * @return
 *   The last completed task, if there is one. An exception is thrown if Drupal
 *   is already installed.
 */
function install_verify_completed_task() {
  try {
    $task = \Drupal::state()->get('install_task');
  }
  // Do not trigger an error if the database query fails, since the database
  // might not be set up yet.
  catch (\Exception $e) {
  }
  if (isset($task)) {
    if ($task == 'done') {
      throw new AlreadyInstalledException(\Drupal::service('string_translation'));
    }
    return $task;
  }
}

/**
 * Verifies that settings.php specifies a valid database connection.
 */
function install_verify_database_settings() {
  global $databases;
  if (!empty($databases)) {
    $database = $databases['default']['default'];
    $settings_file = './' . conf_path(FALSE) . '/settings.php';
    $errors = install_database_errors($database, $settings_file);
    if (empty($errors)) {
      return TRUE;
    }
  }
  return FALSE;
}

/**
 * Form constructor for a form to configure and rewrite settings.php.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @see install_settings_form_validate()
 * @see install_settings_form_submit()
 * @ingroup forms
 */
function install_settings_form($form, &$form_state, &$install_state) {
  global $databases;

  $conf_path = './' . conf_path(FALSE);
  $settings_file = $conf_path . '/settings.php';

  $form['#title'] = t('Database configuration');

  $drivers = drupal_get_database_types();
  $drivers_keys = array_keys($drivers);

  // If database connection settings have been prepared in settings.php already,
  // then the existing values need to be taken over.
  // Note: The installer even executes this form if there is a valid database
  // connection already, since the submit handler of this form is responsible
  // for writing all $settings to settings.php (not limited to $databases).
  if (isset($databases['default']['default'])) {
    $default_driver = $databases['default']['default']['driver'];
    $default_options = $databases['default']['default'];
  }
  // Otherwise, use the database connection settings from the form input.
  // For a non-interactive installation, this is derived from the original
  // $settings array passed into install_drupal().
  elseif (isset($form_state['input']['driver'])) {
    $default_driver = $form_state['input']['driver'];
    $default_options = $form_state['input'][$default_driver];
  }
  // If there is no database information at all yet, just suggest the first
  // available driver as default value, so that its settings form is made
  // visible via #states when JavaScript is enabled (see below).
  else {
    $default_driver = current($drivers_keys);
    $default_options = array();
  }

  $form['driver'] = array(
    '#type' => 'radios',
    '#title' => t('Database type'),
    '#required' => TRUE,
    '#default_value' => $default_driver,
  );
  if (count($drivers) == 1) {
    $form['driver']['#disabled'] = TRUE;
  }

  // Add driver specific configuration options.
  foreach ($drivers as $key => $driver) {
    $form['driver']['#options'][$key] = $driver->name();

    $form['settings'][$key] = $driver->getFormOptions($default_options);
    $form['settings'][$key]['#prefix'] = '<h2 class="js-hide">' . t('@driver_name settings', array('@driver_name' => $driver->name())) . '</h2>';
    $form['settings'][$key]['#type'] = 'container';
    $form['settings'][$key]['#tree'] = TRUE;
    $form['settings'][$key]['advanced_options']['#parents'] = array($key);
    $form['settings'][$key]['#states'] = array(
      'visible' => array(
        ':input[name=driver]' => array('value' => $key),
      )
    );
  }

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['save'] = array(
    '#type' => 'submit',
    '#value' => t('Save and continue'),
    '#button_type' => 'primary',
    '#limit_validation_errors' => array(
      array('driver'),
      array($default_driver),
    ),
    '#submit' => array('install_settings_form_submit'),
  );

  $form['errors'] = array();
  $form['settings_file'] = array('#type' => 'value', '#value' => $settings_file);

  return $form;
}

/**
 * Form validation handler for install_settings_form().
 *
 * @see install_settings_form_submit()
 */
function install_settings_form_validate($form, &$form_state) {
  $driver = $form_state['values']['driver'];
  $database = $form_state['values'][$driver];
  $drivers = drupal_get_database_types();
  $reflection = new \ReflectionClass($drivers[$driver]);
  $install_namespace = $reflection->getNamespaceName();
  // Cut the trailing \Install from namespace.
  $database['namespace'] = substr($install_namespace, 0, strrpos($install_namespace, '\\'));
  $database['driver'] = $driver;

  $form_state['storage']['database'] = $database;
  $errors = install_database_errors($database, $form_state['values']['settings_file']);
  foreach ($errors as $name => $message) {
    form_set_error($name, $form_state, $message);
  }
}

/**
 * Checks a database connection and returns any errors.
 */
function install_database_errors($database, $settings_file) {
  global $databases;
  $errors = array();

  // Check database type.
  $database_types = drupal_get_database_types();
  $driver = $database['driver'];
  if (!isset($database_types[$driver])) {
    $errors['driver'] = t("In your %settings_file file you have configured @drupal to use a %driver server, however your PHP installation currently does not support this database type.", array('%settings_file' => $settings_file, '@drupal' => drupal_install_profile_distribution_name(), '%driver' => $driver));
  }
  else {
    // Run driver specific validation
    $errors += $database_types[$driver]->validateDatabaseSettings($database);

    // Run tasks associated with the database type. Any errors are caught in the
    // calling function.
    $databases['default']['default'] = $database;
    // Just changing the global doesn't get the new information processed.
    // We need to close any active connections and tell the Database class to
    // re-parse $databases.
    if (Database::isActiveConnection()) {
      Database::closeConnection();
    }
    Database::parseConnectionInfo();

    try {
      db_run_tasks($driver);
    }
    catch (TaskException $e) {
      // These are generic errors, so we do not have any specific key of the
      // database connection array to attach them to; therefore, we just put
      // them in the error array with standard numeric keys.
      $errors[$driver . '][0'] = $e->getMessage();
    }
  }
  return $errors;
}

/**
 * Form submission handler for install_settings_form().
 *
 * @see install_settings_form_validate()
 */
function install_settings_form_submit($form, &$form_state) {
  global $install_state;

  // Update global settings array and save.
  $settings = array();
  $database = $form_state['storage']['database'];
  $settings['databases']['default']['default'] = (object) array(
    'value'    => $database,
    'required' => TRUE,
  );
  $settings['settings']['hash_salt'] = (object) array(
    'value'    => Crypt::randomBytesBase64(55),
    'required' => TRUE,
  );
  // Remember the profile which was used.
  $settings['settings']['install_profile'] = (object) array(
    'value' => $install_state['parameters']['profile'],
    'required' => TRUE,
  );

  drupal_rewrite_settings($settings);

  // Add the config directories to settings.php.
  drupal_install_config_directories();

  // Indicate that the settings file has been verified, and check the database
  // for the last completed task, now that we have a valid connection. This
  // last step is important since we want to trigger an error if the new
  // database already has Drupal installed.
  $install_state['settings_verified'] = TRUE;
  $install_state['config_verified'] = TRUE;
  $install_state['database_verified'] = TRUE;
  $install_state['completed_task'] = install_verify_completed_task();
}

/**
 * Selects which profile to install.
 *
 * @param $install_state
 *   An array of information about the current installation state. The chosen
 *   profile will be added here, if it was not already selected previously, as
 *   will a list of all available profiles.
 *
 * @return
 *   For interactive installations, a form allowing the profile to be selected,
 *   if the user has a choice that needs to be made. Otherwise, an exception is
 *   thrown if a profile cannot be chosen automatically.
 */
function install_select_profile(&$install_state) {
  if (empty($install_state['parameters']['profile'])) {
    // If there are no profiles at all, installation cannot proceed.
    if (empty($install_state['profiles'])) {
      throw new NoProfilesException(\Drupal::service('string_translation'));
    }
    // Try to automatically select a profile.
    if ($profile = _install_select_profile($install_state)) {
      $install_state['parameters']['profile'] = $profile;
    }
    else {
      // The non-interactive installer requires a profile parameter.
      if (!$install_state['interactive']) {
        throw new InstallerException(t('Missing profile parameter.'));
      }
      // Otherwise, display a form to select a profile.
      return install_get_form('install_select_profile_form', $install_state);
    }
  }
}

/**
 * Determines the installation profile to use in the installer.
 *
 * A profile will be selected in the following order of conditions:
 *
 * 1. Only one profile is available.
 * 2. A specific profile name is requested in installation parameters:
 *    - for interactive installations via request query parameters.
 *    - for non-interactive installations via install_drupal() settings.
 * 3. A discovered profile that is a distribution.
 *    If multiple profiles are distributions, then the first discovered profile
 *    will be selected.
 *
 * @param array $install_state
 *   The current installer state, containing a 'profiles' key, which is an
 *   associative array of profiles with the machine-readable names as keys.
 *
 * @return
 *   The machine-readable name of the selected profile or NULL if no profile was
 *   selected.
 */
function _install_select_profile(&$install_state) {
  // Don't need to choose profile if only one available.
  if (count($install_state['profiles']) == 1) {
    return key($install_state['profiles']);
  }
  if (!empty($install_state['parameters']['profile'])) {
    $profile = $install_state['parameters']['profile'];
    if (isset($install_state['profiles'][$profile])) {
      return $profile;
    }
  }
  // Check for a distribution profile.
  foreach ($install_state['profiles'] as $profile) {
    $profile_info = install_profile_info($profile->getName());
    if (!empty($profile_info['distribution'])) {
      return $profile->getName();
    }
  }
}

/**
 * Form constructor for the profile selection form.
 *
 * @param array $install_state
 *   An array of information about the current installation state.
 *
 * @ingroup forms
 */
function install_select_profile_form($form, &$form_state, $install_state) {
  $form['#title'] = t('Select an installation profile');

  $profiles = array();
  $names = array();
  foreach ($install_state['profiles'] as $profile) {
    $details = install_profile_info($profile->getName());
    // Skip this extension if its type is not profile.
    if (!isset($details['type']) || $details['type'] != 'profile') {
      continue;
    }
    // Don't show hidden profiles. This is used by to hide the testing profile,
    // which only exists to speed up test runs.
    if ($details['hidden'] === TRUE) {
      continue;
    }
    $profiles[$profile->getName()] = $details;

    // Determine the name of the profile; default to file name if defined name
    // is unspecified.
    $name = isset($details['name']) ? $details['name'] : $profile->getName();
    $names[$profile->getName()] = $name;
  }

  // Display radio buttons alphabetically by human-readable name, but always
  // put the core profiles first (if they are present in the filesystem).
  natcasesort($names);
  if (isset($names['minimal'])) {
    // If the expert ("Minimal") core profile is present, put it in front of
    // any non-core profiles rather than including it with them alphabetically,
    // since the other profiles might be intended to group together in a
    // particular way.
    $names = array('minimal' => $names['minimal']) + $names;
  }
  if (isset($names['standard'])) {
    // If the default ("Standard") core profile is present, put it at the very
    // top of the list. This profile will have its radio button pre-selected,
    // so we want it to always appear at the top.
    $names = array('standard' => $names['standard']) + $names;
  }

  // The profile name and description are extracted for translation from the
  // .info file, so we can use t() on them even though they are dynamic data
  // at this point.
  $form['profile'] = array(
    '#type' => 'radios',
    '#title' => t('Select an installation profile'),
    '#title_display' => 'invisible',
    '#options' => array_map('t', $names),
    '#default_value' => 'standard',
  );
  foreach (array_keys($names) as $profile) {
    $form['profile'][$profile]['#description'] = isset($profiles[$profile]['description']) ? t($profiles[$profile]['description']) : '';
  }
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] =  array(
    '#type' => 'submit',
    '#value' => t('Save and continue'),
    '#button_type' => 'primary',
  );
  return $form;
}

/**
 * Form submission handler for install_select_profile_form().
 */
function install_select_profile_form_submit($form, &$form_state) {
  global $install_state;
  $install_state['parameters']['profile'] = $form_state['values']['profile'];
}

/**
 * Finds all .po files that are useful to the installer.
 *
 * @return
 *   An associative array of file URIs keyed by language code. URIs as
 *   returned by file_scan_directory().
 *
 * @see file_scan_directory()
 */
function install_find_translations() {
  $translations = array();
  $files = \Drupal::service('string_translator.file_translation')->findTranslationFiles();
  // English does not need a translation file.
  array_unshift($files, (object) array('name' => 'en'));
  foreach ($files as $uri => $file) {
    // Strip off the file name component before the language code.
    $langcode = preg_replace('!^(.+\.)?([^\.]+)$!', '\2', $file->name);
    // Language codes cannot exceed 12 characters to fit into the {language}
    // table.
    if (strlen($langcode) <= 12) {
      $translations[$langcode] = $uri;
    }
  }
  return $translations;
}

/**
 * Selects which language to use during installation.
 *
 * @param $install_state
 *   An array of information about the current installation state. The chosen
 *   langcode will be added here, if it was not already selected previously, as
 *   will a list of all available languages.
 *
 * @return
 *   For interactive installations, a form or other page output allowing the
 *   language to be selected or providing information about language selection,
 *   if a language has not been chosen. Otherwise, an exception is thrown if a
 *   language cannot be chosen automatically.
 */
function install_select_language(&$install_state) {
  // Find all available translation files.
  $files = install_find_translations();
  $install_state['translations'] += $files;

  // If a valid language code is set, continue with the next installation step.
  // When translations from the localization server are used, any language code
  // is accepted because the standard language list is kept in sync with the
  // langauges available at http://localize.drupal.org.
  // When files from the translation directory are used, we only accept
  // languages for which a file is available.
  if (!empty($install_state['parameters']['langcode'])) {
    $standard_languages = LanguageManager::getStandardLanguageList();
    $langcode = $install_state['parameters']['langcode'];
    if ($langcode == 'en' || isset($files[$langcode]) || isset($standard_languages[$langcode])) {
      $install_state['parameters']['langcode'] = $langcode;
      return;
    }
  }

  if (empty($install_state['parameters']['langcode'])) {
    // If we are performing an interactive installation, we display a form to
    // select a right language. If no translation files were found in the
    // translations directory, the form shows a list of standard languages. If
    // translation files were found the form shows a select list of the
    // corresponding languages to choose from.
    if ($install_state['interactive']) {
      return install_get_form('install_select_language_form', $install_state);
    }
    // If we are performing a non-interactive installation. If only one language
    // (English) is available, assume the user knows what he is doing. Otherwise
    // thow an error.
    else {
      if (count($files) == 1) {
        $install_state['parameters']['langcode'] = current(array_keys($files));
        return;
      }
      else {
        throw new InstallerException(t('Sorry, you must select a language to continue the installation.'));
      }
    }
  }
}

/**
 * Form constructor for the language selection form.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @see file_scan_directory()
 * @ingroup forms
 */
function install_select_language_form($form, &$form_state, &$install_state) {
  if (count($install_state['translations']) > 1) {
    $files = $install_state['translations'];
  }
  else {
    $files = array();
  }
  $standard_languages = LanguageManager::getStandardLanguageList();
  $select_options = array();
  $browser_options = array();

  $form['#title'] = t('Choose language');

  // Build a select list with language names in native language for the user
  // to choose from. And build a list of available languages for the browser
  // to select the language default from.
  if (count($files)) {
    // Select lists based on available language files.
    foreach ($files as $langcode => $uri) {
      $select_options[$langcode] = isset($standard_languages[$langcode]) ? $standard_languages[$langcode][1] : $langcode;
      $browser_options[] = $langcode;
    }
  }
  else {
    // Select lists based on all standard languages.
    foreach ($standard_languages as $langcode => $language_names) {
      $select_options[$langcode] = $language_names[1];
      $browser_options[] = $langcode;
    }
  }

  $request = Request::createFromGlobals();
  $browser_langcode = UserAgent::getBestMatchingLangcode($request->server->get('HTTP_ACCEPT_LANGUAGE'), $browser_options);
  $form['langcode'] = array(
    '#type' => 'select',
    '#title' => t('Choose language'),
    '#title_display' => 'invisible',
    '#options' => $select_options,
    // Use the browser detected language as default or English if nothing found.
    '#default_value' => !empty($browser_langcode) ? $browser_langcode : 'en',
  );

  if (empty($files)) {
    $form['help'] = array(
      '#type' => 'item',
      '#markup' => \Drupal\Component\Utility\String::format('<p>Translations will be downloaded from the <a href="http://localize.drupal.org">Drupal Translation website</a>.
        If you do not want this, select <a href="!english">English</a>.</p>', array(
          '!english' => install_full_redirect_url(array('parameters' => array('langcode' => 'en'))),
      )),
      '#states' => array(
        'invisible' => array(
          'select[name="langcode"]' => array('value' => 'en'),
        ),
      ),
    );
  }
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] =  array(
    '#type' => 'submit',
    '#value' => t('Save and continue'),
    '#button_type' => 'primary',
  );
  return $form;
}

/**
 * Form submission handler for the language selection form.
 */
function install_select_language_form_submit($form, &$form_state) {
  $install_state = &$form_state['build_info']['args'][0];
  $install_state['parameters']['langcode'] = $form_state['values']['langcode'];
}

/**
 * Download a translation file for the selected langaguage.
 *
 * @param array $install_state
 *   An array of information about the current installation state.
 *
 * @return string
 *   A themed status report, or an exception if there are requirement errors.
 *   Upon successful download the page is reloaded and no output is returned.
 */
function install_download_translation(&$install_state) {
  // Check whether all conditions are met to download. Download the translation
  // if possible.
  $requirements = install_check_translations($install_state);
  if ($output = install_display_requirements($install_state, $requirements)) {
    return $output;
  }

  // The download was successful, reload the page in the new lanagage.
  install_goto(install_redirect_url($install_state));
}

/**
 * Attempts to get a file using a HTTP request and to store it locally.
 *
 * @param string $uri
 *   The URI of the file to grab.
 * @param string $destination
 *   Stream wrapper URI specifying where the file should be placed. If a
 *   directory path is provided, the file is saved into that directory under its
 *   original name. If the path contains a filename as well, that one will be
 *   used instead.
 *
 * @return boolean
 *   TRUE on success, FALSE on failure.
 */
function install_retrieve_file($uri, $destination) {
  $parsed_url = parse_url($uri);
  if (is_dir(drupal_realpath($destination))) {
    // Prevent URIs with triple slashes when gluing parts together.
    $path = str_replace('///', '//', "$destination/") . drupal_basename($parsed_url['path']);
  }
  else {
    $path = $destination;
  }

  try {
    $request = \Drupal::httpClient()->get($uri, array('Accept' => 'text/plain'));
    $data = $request->send()->getBody(TRUE);
    if (empty($data)) {
      return FALSE;
    }
  }
  catch (RequestException $e) {
    return FALSE;
  }
  return file_put_contents($path, $data) !== FALSE;
}

/**
 *  Checks if the localization server can be contacted.
 *
 * @param string $uri
 *  The URI to contact.
 *
 * @return string
 *   TRUE if the URI was contacted successfully, FALSE if not.
 */
function install_check_localization_server($uri) {
  try {
    $request = \Drupal::httpClient()->head($uri);
    $request->send();
    return TRUE;
  }
  catch (RequestException $e) {
    return FALSE;
  }
}

/**
 * Gets the core release version and release alternatives for localization.
 *
 * In case core is a development version or the translation file for the release
 * is not available, fall back to the latest stable release. For example,
 * 8.2-dev might fall back to 8.1 and 8.0-dev might fall back to 7.0. Fallback
 * is required because the localization server only provides translation files
 * for stable releases.
 *
 * @param string $version
 *   (optional) Version of core trying to find translation files for.
 *
 * @return array
 *   Array of release data. Each array element is an associative array with:
 *   - core: Core compatibility version (e.g., 8.x).
 *   - version: Release version (e.g., 8.1).
 */
function install_get_localization_release($version = \Drupal::VERSION) {
  $releases = array();
  $alternatives = array();

  // The version string is broken up into:
  // - major: Major version (e.g., "8").
  // - minor: Minor version (e.g., "0").
  // - extra: Extra version info (e.g., "alpha2").
  // - extra_text: The text part of "extra" (e.g., "alpha").
  // - extra_number: The number part of "extra" (e.g., "2").
  $info = _install_get_version_info($version);

  // Check if the version is a regular stable release (no 'rc', 'beta', 'alpha',
  // 'dev', etc.)
  if (!isset($info['extra_text'])) {
    // First version alternative: the current version.
    $alternatives[] = $version;
    // Point-releases: previous minor release (e.g., 8.2 falls back to 8.1).
    if ($info['minor'] > 0) {
      $alternatives[]= $info['major'] . '.' . ($info['minor'] - 1);
    }
    // Zero release: first release candidate (e.g., 8.0 falls back to 8.0-rc1).
    else {
      $alternatives[] = $info['major'] . '.0-rc1';
    }
  }
  else {
    switch ($info['extra_text']) {
      // Alpha release: current alpha or previous alpha release (e.g. 8.0-alpha2
      // falls back to 8.0-alpha1).
      case 'alpha':
        $alternatives[] = $version;
        if ($info['extra_number'] > 1) {
          $alternatives[] = $info['major'] . '.0-alpha' . ($info['extra_number'] - 1);
        }
        break;

      // Beta release: current beta or previous beta release (e.g. 8.0-beta2
      // falls back to 8.0-beta1) or first alpha release.
      case 'beta':
        $alternatives[] = $version;
        if ($info['extra_number'] > 1) {
          $alternatives[] = $info['major'] . '.0-beta' . ($info['extra_number'] - 1);
        }
        else {
          $alternatives[] = $info['major'] . '.0-alpha2';
        }
        break;

      // Dev release: the previous point release (e.g., 8.2-dev falls back to
      // 8.1) or any unstable release (e.g., 8.0-dev falls back to 8.0-rc1,
      // 8.0-beta1 or 8.0-alpha2)
      case 'dev':
        if ($info['minor'] >= 1) {
          $alternatives[] = $info['major'] . '.' . ($info['minor'] - 1);
        }
        else {
          $alternatives[] = $info['major'] . '.0-rc1';
          $alternatives[] = $info['major'] . '.0-beta1';
          $alternatives[] = $info['major'] . '.0-alpha2';
        }
        break;

      // Release candidate: the current or previous release candidate (e.g.,
      // 8.0-rc2 falls back to 8.0-rc1) or the first beta release.
      case 'rc':
        $alternatives[] =  $version;
        if ($info['extra_number'] >= 2) {
          $alternatives[] = $info['major'] . '.0-rc' . ($info['extra_number'] - 1);
        }
        else {
          $alternatives[] = $info['major'] . '.0-beta1';
        }
        break;
    }
  }

  // All releases may fall back to the previous major release (e.g., 8.1 falls
  // back to 7.0, 8.x-dev falls back to 7.0). This will probably only be used
  // for early dev releases or languages with an inactive translation team.
  $alternatives[] = $info['major'] - 1 . '.0';

  foreach ($alternatives as $alternative) {
    list($core) = explode('.', $alternative);
    $releases[] = array(
      'core' => $core . '.x',
      'version' => $alternative,
    );
  }

  return $releases;
}

/**
 * Extracts version information from a drupal core version string.
 *
 * @param string $version
 *   Version info string (e.g., 8.0, 8.1, 8.0-dev, 8.0-unstable1, 8.0-alpha2,
 *   8.0-beta3, and 8.0-rc4).
 *
 *
 * @return array
 *   Associative array of version info:
 *   - major: Major version (e.g., "8").
 *   - minor: Minor version (e.g., "0").
 *   - extra: Extra version info (e.g., "alpha2").
 *   - extra_text: The text part of "extra" (e.g., "alpha").
 *   - extra_number: The number part of "extra" (e.g., "2").
 */
function _install_get_version_info($version) {
  preg_match('/
    (
      (?P<major>[0-9]+)    # Major release number.
      \.          # .
      (?P<minor>[0-9]+)    # Minor release number.
    )             #
    (             #
      -           # - separator for "extra" version information.
      (?P<extra>   #
        (?P<extra_text>[a-z]+)  # Release extra text (e.g., "alpha").
        (?P<extra_number>[0-9]*)  # Release extra number (no separator between text and number).
      )           #
      |           # OR no "extra" information.
    )
    /sx', $version, $matches);

  return $matches;
}

/**
 * Loads information about the chosen profile during installation.
 *
 * @param $install_state
 *   An array of information about the current installation state. The loaded
 *   profile information will be added here.
 */
function install_load_profile(&$install_state) {
  $profile = $install_state['parameters']['profile'];
  $install_state['profiles'][$profile]->load();
  $install_state['profile_info'] = install_profile_info($profile, isset($install_state['parameters']['langcode']) ? $install_state['parameters']['langcode'] : 'en');
}

/**
 * Performs a full bootstrap of Drupal during installation.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 */
function install_bootstrap_full() {
  drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
  require_once DRUPAL_ROOT . '/' . settings()->get('session_inc', 'core/includes/session.inc');
  drupal_session_initialize();
}

/**
 * Installs required modules via a batch process.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @return
 *   The batch definition.
 */
function install_profile_modules(&$install_state) {
  $modules = \Drupal::state()->get('install_profile_modules') ?: array();
  $files = system_rebuild_module_data();
  \Drupal::state()->delete('install_profile_modules');

  // Always install required modules first. Respect the dependencies between
  // the modules.
  $required = array();
  $non_required = array();
  // Although the profile module is marked as required, it needs to go after
  // every dependency, including non-required ones. So clear its required
  // flag for now to allow it to install late.
  $files[$install_state['parameters']['profile']]->info['required'] = FALSE;
  // Add modules that other modules depend on.
  foreach ($modules as $module) {
    if ($files[$module]->requires) {
      $modules = array_merge($modules, array_keys($files[$module]->requires));
    }
  }
  $modules = array_unique($modules);
  foreach ($modules as $module) {
    if (!empty($files[$module]->info['required'])) {
      $required[$module] = $files[$module]->sort;
    }
    else {
      $non_required[$module] = $files[$module]->sort;
    }
  }
  arsort($required);
  arsort($non_required);

  $operations = array();
  foreach ($required + $non_required as $module => $weight) {
    $operations[] = array('_install_module_batch', array($module, $files[$module]->info['name']));
  }
  $batch = array(
    'operations' => $operations,
    'title' => t('Installing @drupal', array('@drupal' => drupal_install_profile_distribution_name())),
    'error_message' => t('The installation has encountered an error.'),
    'finished' => '_install_profile_modules_finished',
  );
  return $batch;
}

/**
 * Imports languages via a batch process during installation.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @return
 *   The batch definition, if there are language files to import.
 */
function install_import_translations(&$install_state) {
  include_once __DIR__ . '/../modules/locale/locale.bulk.inc';

  $langcode = $install_state['parameters']['langcode'];
  $standard_languages = LanguageManager::getStandardLanguageList();
  if (!isset($standard_languages[$langcode])) {
    // Drupal does not know about this language, so we prefill its values with
    // our best guess. The user will be able to edit afterwards.
    $language = new Language(array(
      'id' => $langcode,
      'name' => $langcode,
      'default' => TRUE,
    ));
    language_save($language);
  }
  else {
    // A known predefined language, details will be filled in properly.
    $language = new Language(array(
      'id' => $langcode,
      'default' => TRUE,
    ));
    language_save($language);
  }

  // If a non-English language was selected, remove English and import the
  // translations.
  if ($langcode != 'en') {
    language_delete('en');

    // Set up a batch to import translations for the newly added language.
    _install_prepare_import($langcode);
    module_load_include('fetch.inc', 'locale');
    if ($batch = locale_translation_batch_fetch_build(array(), array($langcode))) {
      return $batch;
    }
  }
}

/**
 * Tells the translation import process that Drupal core is installed.
 *
 * @param string $langcode
 *   Language code used for the translation.
 */
function _install_prepare_import($langcode) {
  $matches = array();
  global $install_state;

  // Get the translation files located in the translations directory.
  $files = locale_translate_get_interface_translation_files(array('drupal'), array($langcode));
  // We pick the first file which matches the installation language.
  $file = reset($files);
  $filename = $file->filename;
  preg_match('/drupal-([0-9a-z\.-]+)\.' . $langcode . '\.po/', $filename, $matches);
  // Get the version information.
  if ($version = $matches[1]) {
    $info = _install_get_version_info($version);
    // Picking the first file does not necessarily result in the right file. So
    // we check if at least the major version number is available.
    if ($info['major']) {
      $core = $info['major'] . '.x';
      db_insert('locale_project')
        ->fields(array(
          'name' => 'drupal',
          'project_type' => 'module',
          'core' => $core,
          'version' => $version,
          'server_pattern' => $install_state['server_pattern'],
          'status' => 1,
        ))
        ->execute();
      module_load_include('compare.inc', 'locale');
      locale_translation_check_projects_local(array('drupal'), array($install_state['parameters']['langcode']));
    }
  }
}

/**
 * Form constructor for a form to configure the new site.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @see install_configure_form_validate()
 * @see install_configure_form_submit()
 * @ingroup forms
 */
function install_configure_form($form, &$form_state, &$install_state) {
  $form['#title'] = t('Configure site');

  // Warn about settings.php permissions risk
  $settings_dir = conf_path();
  $settings_file = $settings_dir . '/settings.php';
  // Check that $_POST is empty so we only show this message when the form is
  // first displayed, not on the next page after it is submitted. (We do not
  // want to repeat it multiple times because it is a general warning that is
  // not related to the rest of the installation process; it would also be
  // especially out of place on the last page of the installer, where it would
  // distract from the message that the Drupal installation has completed
  // successfully.)
  $post_params = \Drupal::request()->request->all();
  if (empty($post_params) && (!drupal_verify_install_file(DRUPAL_ROOT . '/' . $settings_file, FILE_EXIST|FILE_READABLE|FILE_NOT_WRITABLE) || !drupal_verify_install_file(DRUPAL_ROOT . '/' . $settings_dir, FILE_NOT_WRITABLE, 'dir'))) {
    drupal_set_message(t('All necessary changes to %dir and %file have been made, so you should remove write permissions to them now in order to avoid security risks. If you are unsure how to do so, consult the <a href="@handbook_url">online handbook</a>.', array('%dir' => $settings_dir, '%file' => $settings_file, '@handbook_url' => 'http://drupal.org/server-permissions')), 'warning');
  }

  $form['#attached']['library'][] = 'system/drupal.system';
  // Add JavaScript time zone detection.
  $form['#attached']['library'][] = 'core/drupal.timezone';
  // We add these strings as settings because JavaScript translation does not
  // work during installation.
  $js = array('copyFieldValue' => array('edit-site-mail' => array('edit-account-mail')));
  $form['#attached']['js'][] = array('data' => $js, 'type' => 'setting');

  // Cache a fully-built schema. This is necessary for any invocation of
  // index.php because: (1) setting cache table entries requires schema
  // information, (2) that occurs during bootstrap before any module are
  // loaded, so (3) if there is no cached schema, drupal_get_schema() will
  // try to generate one but with no loaded modules will return nothing.
  //
  // @todo Move this to the 'install_finished' task?
  drupal_get_schema(NULL, TRUE);

  // Return the form.
  return _install_configure_form($form, $form_state, $install_state);
}

/**
 * Finishes importing files at end of installation.
 *
 * If other projects besides Drupal core have been installed, their translation
 * will be imported here.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @return
 *   The batch definition, if there are language files to import.
 */
function install_import_translations_remaining(&$install_state) {
  module_load_include('fetch.inc', 'locale');
  module_load_include('compare.inc', 'locale');

  // Build a fresh list of installed projects. When more projects than core are
  // installed, their translations will be downloaded (if required) and imported
  // using a batch.
  $projects = locale_translation_build_projects();
  if (count($projects) > 1) {
    $options = _locale_translation_default_update_options();
    if ($batch =  locale_translation_batch_update_build(array(), array($install_state['parameters']['langcode']), $options)) {
      return $batch;
    }
  }
}

/**
 * Creates configuration translations.
 *
 * @param array $install_state
 *   An array of information about the current installation state.
 *
 * @return array
 *   The batch definition, if there are configuration objects to update.
 *
 * @see install_tasks()
 */
function install_update_configuration_translations(&$install_state) {
  \Drupal::moduleHandler()->loadInclude('locale', 'bulk.inc');
  return locale_config_batch_update_components(array(), array($install_state['parameters']['langcode']));
}

/**
 * Performs final installation steps and displays a 'finished' page.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @return
 *   A message informing the user that the installation is complete.
 */
function install_finished(&$install_state) {
  $profile = drupal_get_profile();

  // Installation profiles are always loaded last.
  module_set_weight($profile, 1000);

  // Flush all caches to ensure that any full bootstraps during the installer
  // do not leave stale cached data, and that any content types or other items
  // registered by the installation profile are registered correctly.
  drupal_flush_all_caches();

  $messages = drupal_set_message();
  $output = '<p>' . t('Congratulations, you installed @drupal!', array('@drupal' => drupal_install_profile_distribution_name())) . '</p>';
  // Ensure the URL that is generated for the home page does not have 'install.php'
  // in it.
  $request = Request::createFromGlobals();
  $generator = \Drupal::urlGenerator();
  $generator->setBasePath(preg_replace('#/core$#', '', $request->getBasePath()) . '/' );
  $generator->setScriptPath('');
  $url = $generator->generateFromPath('');
  $output .= '<p>' . (isset($messages['error']) ? t('Review the messages above before visiting <a href="@url">your new site</a>.', array('@url' => $url)) : t('<a href="@url">Visit your new site</a>.', array('@url' => $url))) . '</p>';

  // Run cron to populate update status tables (if available) so that users
  // will be warned if they've installed an out of date Drupal version.
  // Will also trigger indexing of profile-supplied content or feeds.
  \Drupal::service('cron')->run();

  // Save a snapshot of the initially installed configuration.
  $active = \Drupal::service('config.storage');
  $snapshot = \Drupal::service('config.storage.snapshot');
  \Drupal::service('config.manager')->createSnapshot($active, $snapshot);

  $build = array(
    '#title' => t('@drupal installation complete', array('@drupal' => drupal_install_profile_distribution_name())),
    '#markup' => $output,
  );
  return $build;
}

/**
 * Batch callback for batch installation of modules.
 */
function _install_module_batch($module, $module_name, &$context) {
  // Install and enable the module right away, so that the module will be
  // loaded by drupal_bootstrap in subsequent batch requests, and other
  // modules possibly depending on it can safely perform their installation
  // steps.
  \Drupal::moduleHandler()->install(array($module), FALSE);
  $context['results'][] = $module;
  $context['message'] = t('Installed %module module.', array('%module' => $module_name));
}

/**
 * 'Finished' callback for module installation batch.
 */
function _install_profile_modules_finished($success, $results, $operations) {
  // Flush all caches to complete the module installation process. Subsequent
  // installation tasks will now have full access to the profile's modules.
  drupal_flush_all_caches();
}

/**
 * Checks installation requirements and reports any errors.
 */
function install_check_translations($install_state) {
  $requirements = array();

  $readable = FALSE;
  $writable = FALSE;
  // @todo: Make this configurable.
  $files_directory = conf_path() . '/files';
  $translations_directory = conf_path() . '/files/translations';
  $translations_directory_exists = FALSE;
  $translation_available = FALSE;
  $online = FALSE;

  // First attempt to create or make writable the files directory.
  file_prepare_directory($files_directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
  // Then, attempt to create or make writable the translations directory.
  file_prepare_directory($translations_directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);

  // Get values so the requirements errors can be specific.
  if (drupal_verify_install_file($translations_directory, FILE_EXIST|FILE_WRITABLE, 'dir')) {
    $readable = is_readable($translations_directory);
    $writable = is_writable($translations_directory);
    $translations_directory_exists = TRUE;
  }

  // Build URLs for the translation file and the translation server.
  $releases = install_get_localization_release();
  $langcode = $install_state['parameters']['langcode'];
  $translation_urls = array();
  foreach ($releases as $release) {
    $variables = array(
      '%project' => 'drupal',
      '%version' => $release['version'],
      '%core' => $release['core'],
      '%language' => $langcode,
    );
    $translation_urls[] = strtr($install_state['server_pattern'], $variables);
  }
  $elements = parse_url(reset($translation_urls));
  $server_url = $elements['scheme'] . '://' . $elements['host'];

  // Build the language name for display.
  $languages = LanguageManager::getStandardLanguageList();
  $language = isset($languages[$langcode]) ? $languages[$langcode][0] : $langcode;

  // Check if any of the desired translation files are available or if the
  // translation server can be reached. In other words, check if we are online
  // and have an internet connection.
  foreach ($translation_urls as $translation_url) {
    if ($translation_available = install_check_localization_server($translation_url)) {
      $online = TRUE;
      break;
    }
  }
  if (!$translation_available) {
    if (install_check_localization_server($server_url)) {
      $online = TRUE;
    }
  }

  // If the translations directory does not exists, throw an error.
  if (!$translations_directory_exists) {
    $requirements['translations directory exists'] = array(
      'title'       => t('Translations directory'),
      'value'       => t('The translations directory does not exist.'),
      'severity'    => REQUIREMENT_ERROR,
      'description' => t('The installer requires that you create a translations directory as part of the installation process. Create the directory %translations_directory . More details about installing Drupal are available in <a href="@install_txt">INSTALL.txt</a>.', array('%translations_directory' => $translations_directory, '@install_txt' => base_path() . 'core/INSTALL.txt')),
    );
  }
  else {
    $requirements['translations directory exists'] = array(
      'title'       => t('Translations directory'),
      'value'       => t('The directory %translations_directory exists.', array('%translations_directory' => $translations_directory)),
    );
    // If the translations directory is not readable, throw an error.
    if (!$readable) {
      $requirements['translations directory readable'] = array(
        'title'       => t('Translations directory'),
        'value'       => t('The translations directory is not readable.'),
        'severity'    => REQUIREMENT_ERROR,
        'description' => t('The installer requires read permissions to %translations_directory at all times. If you are unsure how to grant file permissions, consult the <a href="@handbook_url">online handbook</a>.', array('%translations_directory' => $translations_directory, '@handbook_url' => 'http://drupal.org/server-permissions')),
      );
    }
    // If translations directory is not writable, throw an error.
    if (!$writable) {
      $requirements['translations directory writable'] = array(
        'title'       => t('Translations directory'),
        'value'       => t('The translations directory is not writable.'),
        'severity'    => REQUIREMENT_ERROR,
        'description' => t('The installer requires write permissions to %translations_directory during the installation process. If you are unsure how to grant file permissions, consult the <a href="@handbook_url">online handbook</a>.', array('%translations_directory' => $translations_directory, '@handbook_url' => 'http://drupal.org/server-permissions')),
      );
    }
    else {
      $requirements['translations directory writable'] = array(
        'title'       => t('Translations directory'),
        'value'       => t('The translations directory is writable.'),
      );
    }
  }

  // If the translations server can not be contacted, throw an error.
  if (!$online) {
    $requirements['online'] = array(
      'title'       => t('Internet'),
      'value'       => t('The translation server is offline.'),
      'severity'    => REQUIREMENT_ERROR,
      'description' => t('The installer requires to contact the translation server to download a translation file. Check your internet connection and verify that your website can reach the translation server at <a href="!server_url">!server_url</a>.', array('!server_url' => $server_url)),
    );
  }
  else {
    $requirements['online'] = array(
      'title'       => t('Internet'),
      'value'       => t('The translation server is online.'),
    );
    // If translation file is not found at the translation server, throw an
    // error.
    if (!$translation_available) {
      $requirements['translation available'] = array(
        'title'       => t('Translation'),
        'value'       => t('The %language translation is not available.', array('%language' => $language)),
        'severity'    => REQUIREMENT_ERROR,
        'description' => t('The %language translation file is not available at the translation server. <a href="!url">Choose a different language</a> or select English and translate your website later.', array('%language' => $language, '!url' => check_url($_SERVER['SCRIPT_NAME']))),
      );
    }
    else {
      $requirements['translation available'] = array(
        'title'       => t('Translation'),
        'value'       => t('The %language translation is available.', array('%language' => $language)),
      );
    }
  }

  if ($translations_directory_exists && $readable && $writable && $translation_available) {
    $translation_downloaded = install_retrieve_file($translation_url, $translations_directory);

    if (!$translation_downloaded) {
      $requirements['translation downloaded'] = array(
        'title'       => t('Translation'),
        'value'       => t('The %language translation could not be downloaded.', array('%language' => $language)),
        'severity'    => REQUIREMENT_ERROR,
        'description' => t('The %language translation file could not be downloaded. <a href="!url">Choose a different language</a> or select English and translate your website later.', array('%language' => $language, '!url' => check_url($_SERVER['SCRIPT_NAME']))),
      );
    }
  }

  return $requirements;
}

/**
 * Checks installation requirements and reports any errors.
 */
function install_check_requirements($install_state) {
  $profile = $install_state['parameters']['profile'];

  // Check the profile requirements.
  $requirements = drupal_check_profile($profile, $install_state);

  // If Drupal is not set up already, we need to create a settings file.
  if (!$install_state['settings_verified']) {
    $readable = FALSE;
    $writable = FALSE;
    $conf_path = './' . conf_path(FALSE);
    $settings_file = $conf_path . '/settings.php';
    $default_settings_file = './sites/default/default.settings.php';
    $file = $conf_path;
    $exists = FALSE;
    // Verify that the directory exists.
    if (drupal_verify_install_file($conf_path, FILE_EXIST, 'dir')) {
      // Check if a settings.php file already exists.
      $file = $settings_file;
      if (drupal_verify_install_file($settings_file, FILE_EXIST)) {
        // If it does, make sure it is writable.
        $readable = drupal_verify_install_file($settings_file, FILE_READABLE);
        $writable = drupal_verify_install_file($settings_file, FILE_WRITABLE);
        $exists = TRUE;
      }
    }

    // If default.settings.php does not exist, or is not readable, throw an
    // error.
    if (!drupal_verify_install_file($default_settings_file, FILE_EXIST|FILE_READABLE)) {
      $requirements['default settings file exists'] = array(
        'title'       => t('Default settings file'),
        'value'       => t('The default settings file does not exist.'),
        'severity'    => REQUIREMENT_ERROR,
        'description' => t('The @drupal installer requires that the %default-file file not be modified in any way from the original download.', array('@drupal' => drupal_install_profile_distribution_name(), '%default-file' => $default_settings_file)),
      );
    }
    // Otherwise, if settings.php does not exist yet, we can try to copy
    // default.settings.php to create it.
    elseif (!$exists) {
      $copied = drupal_verify_install_file($conf_path, FILE_EXIST|FILE_WRITABLE, 'dir') && @copy($default_settings_file, $settings_file);
      if ($copied) {
        // If the new settings file has the same owner as default.settings.php,
        // this means default.settings.php is owned by the webserver user.
        // This is an inherent security weakness because it allows a malicious
        // webserver process to append arbitrary PHP code and then execute it.
        // However, it is also a common configuration on shared hosting, and
        // there is nothing Drupal can do to prevent it. In this situation,
        // having settings.php also owned by the webserver does not introduce
        // any additional security risk, so we keep the file in place.
        if (fileowner($default_settings_file) === fileowner($settings_file)) {
          $readable = drupal_verify_install_file($settings_file, FILE_READABLE);
          $writable = drupal_verify_install_file($settings_file, FILE_WRITABLE);
          $exists = TRUE;
        }
        // If settings.php and default.settings.php have different owners, this
        // probably means the server is set up "securely" (with the webserver
        // running as its own user, distinct from the user who owns all the
        // Drupal PHP files), although with either a group or world writable
        // sites directory. Keeping settings.php owned by the webserver would
        // therefore introduce a security risk. It would also cause a usability
        // problem, since site owners who do not have root access to the file
        // system would be unable to edit their settings file later on. We
        // therefore must delete the file we just created and force the
        // administrator to log on to the server and create it manually.
        else {
          $deleted = @drupal_unlink($settings_file);
          // We expect deleting the file to be successful (since we just
          // created it ourselves above), but if it fails somehow, we set a
          // variable so we can display a one-time error message to the
          // administrator at the bottom of the requirements list. We also try
          // to make the file writable, to eliminate any conflicting error
          // messages in the requirements list.
          $exists = !$deleted;
          if ($exists) {
            $settings_file_ownership_error = TRUE;
            $readable = drupal_verify_install_file($settings_file, FILE_READABLE);
            $writable = drupal_verify_install_file($settings_file, FILE_WRITABLE);
          }
        }
      }
    }

    // If settings.php does not exist, throw an error.
    if (!$exists) {
      $requirements['settings file exists'] = array(
        'title'       => t('Settings file'),
        'value'       => t('The settings file does not exist.'),
        'severity'    => REQUIREMENT_ERROR,
        'description' => t('The @drupal installer requires that you create a settings file as part of the installation process. Copy the %default_file file to %file. More details about installing Drupal are available in <a href="@install_txt">INSTALL.txt</a>.', array('@drupal' => drupal_install_profile_distribution_name(), '%file' => $file, '%default_file' => $default_settings_file, '@install_txt' => base_path() . 'core/INSTALL.txt')),
      );
    }
    else {
      $requirements['settings file exists'] = array(
        'title'       => t('Settings file'),
        'value'       => t('The %file file exists.', array('%file' => $file)),
      );
      // If settings.php is not readable, throw an error.
      if (!$readable) {
        $requirements['settings file readable'] = array(
          'title'       => t('Settings file'),
          'value'       => t('The settings file is not readable.'),
          'severity'    => REQUIREMENT_ERROR,
          'description' => t('@drupal requires read permissions to %file at all times. If you are unsure how to grant file permissions, consult the <a href="@handbook_url">online handbook</a>.', array('@drupal' => drupal_install_profile_distribution_name(), '%file' => $file, '@handbook_url' => 'http://drupal.org/server-permissions')),
        );
      }
      // If settings.php is not writable, throw an error.
      if (!$writable) {
        $requirements['settings file writable'] = array(
          'title'       => t('Settings file'),
          'value'       => t('The settings file is not writable.'),
          'severity'    => REQUIREMENT_ERROR,
          'description' => t('The @drupal installer requires write permissions to %file during the installation process. If you are unsure how to grant file permissions, consult the <a href="@handbook_url">online handbook</a>.', array('@drupal' => drupal_install_profile_distribution_name(), '%file' => $file, '@handbook_url' => 'http://drupal.org/server-permissions')),
        );
      }
      else {
        $requirements['settings file'] = array(
          'title'       => t('Settings file'),
          'value'       => t('The settings file is writable.'),
        );
      }
      if (!empty($settings_file_ownership_error)) {
        $requirements['settings file ownership'] = array(
          'title'       => t('Settings file'),
          'value'       => t('The settings file is owned by the web server.'),
          'severity'    => REQUIREMENT_ERROR,
          'description' => t('The @drupal installer failed to create a settings file with proper file ownership. Log on to your web server, remove the existing %file file, and create a new one by copying the %default_file file to %file. More details about installing Drupal are available in <a href="@install_txt">INSTALL.txt</a>. If you have problems with the file permissions on your server, consult the <a href="@handbook_url">online handbook</a>.', array('@drupal' => drupal_install_profile_distribution_name(), '%file' => $file, '%default_file' => $default_settings_file, '@install_txt' => base_path() . 'core/INSTALL.txt', '@handbook_url' => 'http://drupal.org/server-permissions')),
        );
      }
    }
  }
  return $requirements;
}

/**
 * Displays installation requirements.
 *
 * @param array $install_state
 *   An array of information about the current installation state.
 * @param array $requirements
 *   An array of requirements, in the same format as is returned by
 *   hook_requirements().
 *
 * @return
 *   A themed status report, or an exception if there are requirement errors.
 *   If there are only requirement warnings, a themed status report is shown
 *   initially, but the user is allowed to bypass it by providing 'continue=1'
 *   in the URL. Otherwise, no output is returned, so that the next task can be
 *   run in the same page request.
 *
 * @throws \Drupal\Core\Installer\Exception\InstallerException
 */
function install_display_requirements($install_state, $requirements) {
  // Check the severity of the requirements reported.
  $severity = drupal_requirements_severity($requirements);

  // If there are errors, always display them. If there are only warnings, skip
  // them if the user has provided a URL parameter acknowledging the warnings
  // and indicating a desire to continue anyway. See drupal_requirements_url().
  if ($severity == REQUIREMENT_ERROR || ($severity == REQUIREMENT_WARNING && empty($install_state['parameters']['continue']))) {
    if ($install_state['interactive']) {
      $build['#title'] = t('Requirements problem');
      $build['report'] = array(
        '#theme' => 'status_report',
        '#requirements' => $requirements,
        '#suffix' => t('Check the messages and <a href="!url">try again</a>.', array('!url' => check_url(drupal_requirements_url($severity)))),
      );
      return $build;
    }
    else {
      // Throw an exception showing any unmet requirements.
      $failures = array();
      foreach ($requirements as $requirement) {
        // Skip warnings altogether for non-interactive installations; these
        // proceed in a single request so there is no good opportunity (and no
        // good method) to warn the user anyway.
        if (isset($requirement['severity']) && $requirement['severity'] == REQUIREMENT_ERROR) {
          $failures[] = $requirement['title'] . ': ' . $requirement['value'] . "\n\n" . $requirement['description'];
        }
      }
      if (!empty($failures)) {
        throw new InstallerException(implode("\n\n", $failures));
      }
    }
  }
}

/**
 * Form constructor for a site configuration form.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @see install_configure_form()
 * @see install_configure_form_validate()
 * @see install_configure_form_submit()
 * @ingroup forms
 */
function _install_configure_form($form, &$form_state, &$install_state) {
  $form['site_information'] = array(
    '#type' => 'fieldgroup',
    '#title' => t('Site information'),
  );
  $form['site_information']['site_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Site name'),
    '#required' => TRUE,
    '#weight' => -20,
  );
  $form['site_information']['site_mail'] = array(
    '#type' => 'email',
    '#title' => t('Site e-mail address'),
    '#default_value' => ini_get('sendmail_from'),
    '#description' => t("Automated e-mails, such as registration information, will be sent from this address. Use an address ending in your site's domain to help prevent these e-mails from being flagged as spam."),
    '#required' => TRUE,
    '#weight' => -15,
  );

  $form['admin_account'] = array(
    '#type' => 'fieldgroup',
    '#title' => t('Site maintenance account'),
  );
  $form['admin_account']['account']['#tree'] = TRUE;
  $form['admin_account']['account']['name'] = array('#type' => 'textfield',
    '#title' => t('Username'),
    '#maxlength' => USERNAME_MAX_LENGTH,
    '#description' => t('Spaces are allowed; punctuation is not allowed except for periods, hyphens, and underscores.'),
    '#required' => TRUE,
    '#weight' => -10,
    '#attributes' => array('class' => array('username')),
  );
  $form['admin_account']['account']['mail'] = array(
    '#type' => 'email',
    '#title' => t('E-mail address'),
    '#required' => TRUE,
    '#weight' => -5,
  );
  $form['admin_account']['account']['pass'] = array(
    '#type' => 'password_confirm',
    '#required' => TRUE,
    '#size' => 25,
    '#weight' => 0,
  );

  $form['regional_settings'] = array(
    '#type' => 'fieldgroup',
    '#title' => t('Regional settings'),
  );
  $countries = \Drupal::service('country_manager')->getList();
  $form['regional_settings']['site_default_country'] = array(
    '#type' => 'select',
    '#title' => t('Default country'),
    '#empty_value' => '',
    '#default_value' => \Drupal::config('system.date')->get('country.default'),
    '#options' => $countries,
    '#description' => t('Select the default country for the site.'),
    '#weight' => 0,
  );
  $form['regional_settings']['date_default_timezone'] = array(
    '#type' => 'select',
    '#title' => t('Default time zone'),
    '#default_value' => date_default_timezone_get(),
    '#options' => system_time_zones(),
    '#description' => t('By default, dates in this site will be displayed in the chosen time zone.'),
    '#weight' => 5,
    '#attributes' => array('class' => array('timezone-detect')),
  );

  $form['update_notifications'] = array(
    '#type' => 'fieldgroup',
    '#title' => t('Update notifications'),
  );
  $form['update_notifications']['update_status_module'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Update notifications'),
    '#options' => array(
      1 => t('Check for updates automatically'),
      2 => t('Receive e-mail notifications'),
    ),
    '#default_value' => array(1, 2),
    '#description' => t('The system will notify you when updates and important security releases are available for installed components. Anonymous information about your site is sent to <a href="@drupal">Drupal.org</a>.', array('@drupal' => 'http://drupal.org')),
    '#weight' => 15,
  );
  $form['update_notifications']['update_status_module'][2] = array(
    '#states' => array(
      'visible' => array(
        'input[name="update_status_module[1]"]' => array('checked' => TRUE),
      ),
    ),
  );

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save and continue'),
    '#weight' => 15,
    '#button_type' => 'primary',
  );

  return $form;
}

/**
 * Form validation handler for install_configure_form().
 *
 * @see install_configure_form_submit()
 */
function install_configure_form_validate($form, &$form_state) {
  if ($error = user_validate_name($form_state['values']['account']['name'])) {
    form_error($form['admin_account']['account']['name'], $form_state, $error);
  }
}

/**
 * Form submission handler for install_configure_form().
 *
 * @see install_configure_form_validate()
 */
function install_configure_form_submit($form, &$form_state) {
  \Drupal::config('system.site')
    ->set('name', $form_state['values']['site_name'])
    ->set('mail', $form_state['values']['site_mail'])
    ->save();

  \Drupal::config('system.date')
    ->set('timezone.default', $form_state['values']['date_default_timezone'])
    ->set('country.default', $form_state['values']['site_default_country'])
    ->save();

  // Enable update.module if this option was selected.
  if ($form_state['values']['update_status_module'][1]) {
    \Drupal::moduleHandler()->install(array('file', 'update'), FALSE);

    // Add the site maintenance account's email address to the list of
    // addresses to be notified when updates are available, if selected.
    if ($form_state['values']['update_status_module'][2]) {
      \Drupal::config('update.settings')->set('notification.emails', array($form_state['values']['account']['mail']))->save();
    }
  }

  // We precreated user 1 with placeholder values. Let's save the real values.
  $account = user_load(1);
  $account->init = $account->mail = $form_state['values']['account']['mail'];
  $account->roles = $account->getRoles();
  $account->activate();
  $account->timezone = $form_state['values']['date_default_timezone'];
  $account->pass = $form_state['values']['account']['pass'];
  $account->name = $form_state['values']['account']['name'];
  $account->save();
  // Load current user and perform final login tasks.
  $account = user_load(1);
  user_login_finalize($account);

  // Record when this install ran.
  \Drupal::state()->set('install_time', $_SERVER['REQUEST_TIME']);
}
