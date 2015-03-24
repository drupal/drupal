<?php

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Config\BootstrapConfigStorageFactory;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\Install\TaskException;
use Drupal\Core\Form\FormState;
use Drupal\Core\Installer\Exception\AlreadyInstalledException;
use Drupal\Core\Installer\Exception\InstallerException;
use Drupal\Core\Installer\Exception\NoProfilesException;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\Translator\FileTranslation;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

use GuzzleHttp\Exception\RequestException;

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
 * @param $class_loader
 *   The class loader. Normally Composer's ClassLoader, as included by the
 *   front controller, but may also be decorated; e.g.,
 *   \Symfony\Component\ClassLoader\ApcClassLoader.
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
function install_drupal($class_loader, $settings = array()) {
  // Support the old way of calling this function with just a settings array.
  // @todo Remove this when Drush is updated in the Drupal testing
  //   infrastructure in https://www.drupal.org/node/2389243
  if (is_array($class_loader) && $settings === array()) {
    $settings = $class_loader;
    $class_loader = require __DIR__ . '/../../autoload.php';
  }

  global $install_state;
  // Initialize the installation state with the settings that were passed in,
  // as well as a boolean indicating whether or not this is an interactive
  // installation.
  $interactive = empty($settings);
  $install_state = $settings + array('interactive' => $interactive) + install_state_defaults();

  try {
    // Begin the page request. This adds information about the current state of
    // the Drupal installation to the passed-in array.
    install_begin_request($class_loader, $install_state);
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
    elseif ($state['installation_finished']) {
      // Redirect to the newly installed site.
      install_goto('');
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
 * submission is for, and the values are used as the $form_state->getValues()
 * array that is passed on to the form submission via
 * \Drupal::formBuilder()->submitForm()).
 *
 * @see \Drupal\Core\Form\FormBuilderInterface::submitForm()
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
    // TRUE if database is empty & ready to install.
    'database_ready' => FALSE,
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
    // used as the $form_state->getValues() array that is passed on to the form
    // submission via \Drupal::formBuilder()->submitForm().
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
 * @param $class_loader
 *   The class loader. Normally Composer's ClassLoader, as included by the
 *   front controller, but may also be decorated; e.g.,
 *   \Symfony\Component\ClassLoader\ApcClassLoader.
 * @param $install_state
 *   An array of information about the current installation state. This is
 *   modified with information gleaned from the beginning of the page request.
 */
function install_begin_request($class_loader, &$install_state) {
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

  // If the hash salt leaks, it becomes possible to forge a valid testing user
  // agent, install a new copy of Drupal, and take over the original site.
  // The user agent header is used to pass a database prefix in the request when
  // running tests. However, for security reasons, it is imperative that no
  // installation be permitted using such a prefix.
  if ($install_state['interactive'] && strpos($request->server->get('HTTP_USER_AGENT'), 'simpletest') !== FALSE && !drupal_valid_test_ua()) {
    header($request->server->get('SERVER_PROTOCOL') . ' 403 Forbidden');
    exit;
  }

  $site_path = DrupalKernel::findSitePath($request, FALSE);
  Settings::initialize(dirname(dirname(__DIR__)), $site_path, $class_loader);

  // Ensure that procedural dependencies are loaded as early as possible,
  // since the error/exception handlers depend on them.
  require_once __DIR__ . '/../modules/system/system.install';
  require_once __DIR__ . '/common.inc';
  require_once __DIR__ . '/file.inc';
  require_once __DIR__ . '/install.inc';
  require_once __DIR__ . '/schema.inc';
  require_once __DIR__ . '/database.inc';
  require_once __DIR__ . '/form.inc';
  require_once __DIR__ . '/batch.inc';

  // Load module basics (needed for hook invokes).
  include_once __DIR__ . '/module.inc';
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

  // Register the stream wrapper manager.
  $container
    ->register('stream_wrapper_manager', 'Drupal\Core\StreamWrapper\StreamWrapperManager')
    ->addMethodCall('setContainer', array(new Reference('service_container')));
  $container
    ->register('file_system', 'Drupal\Core\File\FileSystem')
    ->addArgument(new Reference('stream_wrapper_manager'))
    ->addArgument(Settings::getInstance())
    ->addArgument((new LoggerChannelFactory())->get('file'));

  \Drupal::setContainer($container);

  // Determine whether base system services are ready to operate.
  $install_state['config_verified'] = install_ensure_config_directory(CONFIG_ACTIVE_DIRECTORY) && install_ensure_config_directory(CONFIG_STAGING_DIRECTORY);
  $install_state['database_verified'] = install_verify_database_settings();
  $install_state['settings_verified'] = $install_state['config_verified'] && $install_state['database_verified'];

  // Install factory tables only after checking the database.
  if ($install_state['database_verified'] && $install_state['database_ready']) {
    $container
      ->register('path.matcher', 'Drupal\Core\Path\PathMatcher')
      ->addArgument(new Reference('config.factory'));
  }

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

  // Only allow dumping the container once the hash salt has been created.
  $kernel = InstallerKernel::createFromRequest($request, $class_loader, $environment, (bool) Settings::get('hash_salt', FALSE));
  $kernel->setSitePath($site_path);
  $kernel->boot();
  $container = $kernel->getContainer();

  // Register the file translation service.
  if (isset($GLOBALS['config']['locale.settings']['translation']['path'])) {
    $directory = $GLOBALS['config']['locale.settings']['translation']['path'];
  }
  else {
    $directory = $site_path . '/files/translations';
  }
  $container->set('string_translator.file_translation', new FileTranslation($directory));
  $container->get('string_translation')
    ->addTranslator($container->get('string_translator.file_translation'));

  // Set the default language to the selected language, if any.
  if (isset($install_state['parameters']['langcode'])) {
    $default_language = new Language(array('id' => $install_state['parameters']['langcode']));
    $container->get('language.default')->set($default_language);
    \Drupal::translation()->setDefaultLangcode($install_state['parameters']['langcode']);
  }

  // Add list of all available profiles to the installation state.
  $listing = new ExtensionDiscovery($container->get('app.root'));
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

  // Load all modules and perform request related initialization.
  $kernel->preHandle($request);

  // Initialize a route on this legacy request similar to
  // \Drupal\Core\DrupalKernel::prepareLegacyRequest() since normal routing
  // will not happen.
  $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('<none>'));
  $request->attributes->set(RouteObjectInterface::ROUTE_NAME, '<none>');

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
    if (Database::getConnectionInfo()) {
      throw new AlreadyInstalledException($container->get('string_translation'));
    }
  }

  // Ensure that the active configuration is empty before installation starts.
  if ($install_state['config_verified'] && empty($task)) {
    $config = BootstrapConfigStorageFactory::get()->listAll();
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
    // Ensure the maintenance theme is initialized. If the install task has
    // rebuilt the container the active theme will not be set. This can occur if
    // the task has installed a module.
    drupal_maintenance_theme();

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
      $batches = $function($install_state);
      if (empty($batches)) {
        // If the task did some processing and decided no batch was necessary,
        // there is nothing more to do here.
        return;
      }
      // Create a one item list of batches if only one batch was provided.
      if (isset($batches['operations'])) {
        $batches = array($batches);
      }
      foreach ($batches as $batch) {
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
      }
      // Process the batch. For progressive batches, this will redirect.
      // Otherwise, the batch will complete.
      // Disable the default script for the URL and clone the object, as
      // batch_process() will add additional options to the batch URL.
      $url = Url::fromUri('base:install.php', ['query' => $install_state['parameters'], 'script' => '']);
      $response = batch_process($url, clone $url);
      if ($response instanceof Response) {
        // Save $_SESSION data from batch.
        \Drupal::service('session')->save();
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
  // available and selected. Also we will need translations even if the
  // installer language is English but there are other languages on the system.
  $needs_translations = (count($install_state['translations']) > 1 && !empty($install_state['parameters']['langcode']) && $install_state['parameters']['langcode'] != 'en') || \Drupal::languageManager()->isMultilingual();
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
      'function' => 'Drupal\Core\Installer\Form\SiteSettingsForm',
    ),
    'install_verify_database_ready' => array(
      'run' => $install_state['database_ready'] ? INSTALL_TASK_SKIP : INSTALL_TASK_RUN_IF_NOT_COMPLETED,
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
    'install_profile_themes' => array(
    ),
    'install_install_profile' => array(
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
      'function' => 'Drupal\Core\Installer\Form\SiteConfigureForm',
    ),
  );

  // Now add any tasks defined by the installation profile.
  if (!empty($install_state['parameters']['profile'])) {
    // Load the profile install file, because it is not always loaded when
    // hook_install_tasks() is invoked (e.g. batch processing).
    $profile = $install_state['parameters']['profile'];
    $profile_install_file = $install_state['profiles'][$profile]->getPath() . '/' . $profile . '.install';
    if (file_exists($profile_install_file)) {
      include_once \Drupal::root() . '/' . $profile_install_file;
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
    'install_finish_translations' => array(
      'display_name' => t('Finish translations'),
      'display' => $needs_translations,
      'type' => 'batch',
      'run' => $needs_translations ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
    ),
    'install_finished' => array(
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
 * maintenance-task-list.html.twig.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @return
 *   A list of tasks, with keys equal to the machine-readable task name and
 *   values equal to the name that should be displayed.
 *
 * @see maintenance-task-list.html.twig
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
  $form_state = (new FormState())
    ->addBuildInfo('args', [&$install_state])
    ->disableRedirect();
  $form_builder = \Drupal::formBuilder();
  if ($install_state['interactive']) {
    $form = $form_builder->buildForm($form_id, $form_state);
    // If the form submission was not successful, the form needs to be rendered,
    // which means the task is not complete yet.
    if (!$form_state->isExecuted()) {
      $install_state['task_not_complete'] = TRUE;
      return $form;
    }
  }
  else {
    // For non-interactive installs, submit the form programmatically with the
    // values taken from the installation state.
    $install_form_id = $form_builder->getFormId($form_id, $form_state);
    if (!empty($install_state['forms'][$install_form_id])) {
      $form_state->setValues($install_state['forms'][$install_form_id]);
    }
    $form_builder->submitForm($form_id, $form_state);

    // Throw an exception in case of any form validation error.
    if ($errors = $form_state->getErrors()) {
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
  return 'core/install.php?' . UrlHelper::buildQuery($install_state['parameters']);
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
  $output['#attached']['html_head'][] = [$noindex_meta_tag, 'install_meta_robots'];

  // Only show the task list if there is an active task; otherwise, the page
  // request has ended before tasks have even been started, so there is nothing
  // meaningful to show.
  $regions = array();
  if (isset($install_state['active_task'])) {
    // Let the theming function know when every step of the installation has
    // been completed.
    $active_task = $install_state['installation_finished'] ? NULL : $install_state['active_task'];
    $task_list = array(
      '#theme' => 'maintenance_task_list',
      '#items' => install_tasks_to_display($install_state),
      '#active' => $active_task,
    );
    $regions['sidebar_first'] = $task_list;
  }

  $response = new Response();
  $default_headers = array(
    'Expires' => 'Sun, 19 Nov 1978 05:00:00 GMT',
    'Last-Modified' => gmdate(DATE_RFC1123, REQUEST_TIME),
    'Cache-Control' => 'no-cache, must-revalidate, post-check=0, pre-check=0',
    'ETag' => '"' . REQUEST_TIME . '"',
  );
  $response->headers->add($default_headers);
  $response->setContent(\Drupal::service('bare_html_page_renderer')->renderBarePage($output, $output['#title'], 'install_page', $regions));
  $response->send();
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
  \Drupal::service('module_installer')->install(array('user'), FALSE);

  // Save the list of other modules to install for the upcoming tasks.
  // State can be set to the database now that system.module is installed.
  $modules = $install_state['profile_info']['dependencies'];

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
  if ($database = Database::getConnectionInfo()) {
    $database = $database['default'];
    $settings_file = './' . conf_path(FALSE) . '/settings.php';
    $errors = install_database_errors($database, $settings_file);
    if (empty($errors)) {
      return TRUE;
    }
  }
  return FALSE;
}

/**
 * Verify that the database is ready (no existing Drupal installation).
 */
function install_verify_database_ready() {
  $system_schema = system_schema();
  end($system_schema);
  $table = key($system_schema);

  if ($database = Database::getConnectionInfo()) {
    if (Database::getConnection()->schema()->tableExists($table)) {
      throw new AlreadyInstalledException(\Drupal::service('string_translation'));
    }
  }
}

/**
 * Checks a database connection and returns any errors.
 */
function install_database_errors($database, $settings_file) {
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
    if (!empty($errors)) {
      // No point to try further.
      return $errors;
    }
    // Run tasks associated with the database type. Any errors are caught in the
    // calling function.
    Database::addConnectionInfo('default', 'default', $database);
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
      return install_get_form('Drupal\Core\Installer\Form\SelectProfileForm', $install_state);
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
 * 4. Only one visible profile is available.
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

  // Get all visible (not hidden) profiles.
  $visible_profiles = array_filter($install_state['profiles'], function ($profile) {
    $profile_info = install_profile_info($profile->getName());
    return !isset($profile_info['hidden']) || !$profile_info['hidden'];
  });

  if (count($visible_profiles) == 1) {
    return (key($visible_profiles));
  }
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
  // languages available at http://localize.drupal.org.
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
      return install_get_form('Drupal\Core\Installer\Form\SelectLanguageForm', $install_state);
    }
    // If we are performing a non-interactive installation. If only one language
    // (English) is available, assume the user knows what he is doing. Otherwise
    // throw an error.
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
 * Download a translation file for the selected language.
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
  $requirements = install_check_translations($install_state['parameters']['langcode'], $install_state['server_pattern']);
  if ($output = install_display_requirements($install_state, $requirements)) {
    return $output;
  }

  // The download was successful, reload the page in the new language.
  $install_state['translations'][$install_state['parameters']['langcode']] = TRUE;
  if ($install_state['interactive']) {
    install_goto(install_redirect_url($install_state));
  }
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
    $request = \Drupal::httpClient()->get($uri, array('headers' => array('Accept' => 'text/plain')));
    $data = $request->getBody(TRUE);
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
    \Drupal::httpClient()->head($uri);
    return TRUE;
  }
  catch (RequestException $e) {
    return FALSE;
  }
}

/**
 * Gets the core release version and release alternatives for localization.
 *
 * In case core is a development version or the translation file for the
 * release is not available, fall back to an earlier release.
 * For example, 8.2.0-dev might fall back to 8.1.0 and 8.0.0-dev
 * might fall back to 7.0.
 *
 * @param string $version
 *   (optional) Version of core trying to find translation files for.
 *
 * @return array
 *   Array of release data. Each array element is an associative array with:
 *   - core: Core compatibility version (e.g., 8.x).
 *   - version: Release version (e.g., 8.1.0).
 */
function install_get_localization_release($version = \Drupal::VERSION) {
  $releases = array();
  $alternatives = array();

  $info = _install_get_version_info($version);

  // This code assumes there is a first alpha available as "alpha1". For
  // Drupal 8.0.0 that isn't the case. In addition the semantic versioning
  // wasn't introduced before "alpha14". However, we have already gotten to
  // "beta1" so we are ignoring this for now.

  // The fallback detection is relaxed - removing version duplicates at the end.

  // Check if the version is a regular stable release (no 'rc', 'beta', 'alpha',
  // 'dev', etc.)
  if (!isset($info['extra_text'])) {
    // First version alternative: the current version.
    $alternatives[] = $version;

    // Patch-level: Previous and zero patch level (e.g., 8.2.4 falls back to
    // 8.2.3 and 8.2.0).
    if ($info['patch'] > 0) {
      $alternatives[] = $info['major'] . '.' . $info['minor'] . '.' . ($info['patch'] - 1);
      $alternatives[] = $info['major'] . '.' . $info['minor'] . '.0';
    }
    // Zero patch: First release candidate (e.g., 8.0.0 falls back to
    // 8.0.0-rc1).
    else {
      $alternatives[] = $info['major'] . '.' . $info['minor'] . '.0-rc1';
    }
    // Point-releases: Previous and zero minor release (e.g., 8.2.x falls back
    // to 8.1.0 and 8.0.0).
    if ($info['minor'] > 0) {
      $alternatives[] = $info['major'] . '.' . ($info['minor'] - 1) . '.0';
      $alternatives[] = $info['major'] . '.0.0';
    }
  }
  else {
    // Below we assume that for all dev, alpha, beta or rc releases the patch
    // level is 0.

    $release_levels = array('rc', 'beta', 'alpha');

    if ($info['extra_text'] == 'dev') {
      // Dev release: Any unstable release (e.g., 8.2.0-dev falls back to
      // 8.2.0-rc1, 8.2.0-beta1 and 8.2.0-alpha1).
      $alternatives[] = $info['major'] . '.' . $info['minor'] . '.0-rc1';
      $alternatives[] = $info['major'] . '.' . $info['minor'] . '.0-beta1';
      $alternatives[] = $info['major'] . '.' . $info['minor'] . '.0-alpha1';
    }
    elseif (in_array($info['extra_text'], $release_levels)) {
      // All other unstable release always includes the current version.
      $alternatives[] = $version;
      // Alpha release: Previous alpha release (e.g. 8.0.0-alpha2 falls back to
      // 8.0.0-alpha1).
      // Beta release: Previous beta release and first alpha release (e.g.
      // 8.0.0-beta2 falls back to 8.0.0-beta1 and 8.0.0-alpha1).
      // Release candidate: Previous release candidate and the first beta
      // release (e.g. 8.0.0-rc2 falls back to 8.0.0-rc1 and 8.0.0-beta1).
      $release_level_key = array_search($info['extra_text'], $release_levels);
      if ($info['extra_number'] > 1) {
        $alternatives[] = $info['major'] . '.' . $info['minor'] . '.0-' . $info['extra_text'] . ($info['extra_number'] - 1);
      }
      if ($info['extra_text'] != 'alpha') {
        $alternatives[] = $info['major'] . '.' . $info['minor'] . '.0-' . $release_levels[$release_level_key + 1] . '1';
      }
    }
    else {
      $alternatives[] = $info['major'] . '.' . $info['minor'] . '.0';
    }
    // All unstable releases except the zero release: The previous minor release
    // and the zero release.
    if ($info['minor'] > 0) {
      $alternatives[] = $info['major'] . '.' . ($info['minor'] - 1) . '.0';
      $alternatives[] = $info['major'] . '.0.0';
    }
  }

  $alternatives = array_unique($alternatives);
  foreach ($alternatives as $alternative) {
    list($core) = explode('.', $alternative);
    $releases[] = array(
      'core' => $core . '.x',
      'version' => $alternative,
    );
  }

  // All releases may fall back to the previous major release (e.g., 8.1.0
  // may fall back to 7.0). This will probably only be used for early dev
  // releases or languages with an inactive translation team.
  if ($info['major'] == 8) {
    $releases[] = array(
      'core' => '7.x',
      'version' => '7.0',
    );
  }
  else {
    $prev_major = $info['major'] - 1;
    $releases[] = array(
      'core' => $prev_major . '.x',
      'version' => $prev_major . '.0.0',
    );
  }

  return $releases;
}

/**
 * Extracts version information from a drupal core version string.
 *
 * @param string $version
 *   Version info string (e.g., 8.0.0, 8.1.0, 8.0.0-dev, 8.0.0-unstable1,
 *   8.0.0-alpha2, 8.0.0-beta3, and 8.0.0-rc4).
 *
 * @return array
 *   Associative array of version info:
 *   - major: Major version (e.g., "8").
 *   - minor: Minor version (e.g., "0").
 *   - patch: Patch version (e.g., "0").
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
      \.          # .
      (?P<patch>[0-9]+)    # Patch release number.
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
  \Drupal::service('session')->start();
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
  );
  return $batch;
}

/**
 * Installs themes.
 *
 * This does not use a batch, since installing themes is faster than modules and
 * because an installation profile typically installs 1-3 themes only (default
 * theme, base theme, admin theme).
 *
 * @param $install_state
 *   An array of information about the current installation state.
 */
function install_profile_themes(&$install_state) {
  $theme_handler = \Drupal::service('theme_handler');

  // ThemeHandler::install() resets the current list of themes. The theme used
  // in the installer is not necessarily in the list of themes to install, so
  // retain the current list.
  // @see _drupal_maintenance_theme()
  $current_themes = $theme_handler->listInfo();

  // Install the themes specified by the installation profile.
  $themes = $install_state['profile_info']['themes'];
  $theme_handler->install($themes);

  foreach ($current_themes as $theme) {
    $theme_handler->addTheme($theme);
  }
}

/**
 * Installs the install profile.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 */
function install_install_profile(&$install_state) {
  \Drupal::service('module_installer')->install(array(drupal_get_profile()), FALSE);
  // Install all available optional config. During installation the module order
  // is determined by dependencies. If there are no dependencies between modules
  // then the order in which they are installed is dependent on random factors
  // like PHP version. Optional configuration therefore might or might not be
  // created depending on this order. Ensuring that we have installed all of the
  // optional configuration whose dependencies can be met at this point removes
  // any disparities that this creates.
  \Drupal::service('config.installer')->installOptionalConfig();
}

/**
 * Prepares the system for import and downloads additional translations.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @return
 *   The batch definition, if there are language files to download.
 */
function install_download_additional_translations_operations(&$install_state) {
  \Drupal::moduleHandler()->loadInclude('locale', 'bulk.inc');

  $langcode = $install_state['parameters']['langcode'];
  if (!($language = entity_load('configurable_language', $langcode))) {
    // Create the language if not already shipped with a profile.
    $language = ConfigurableLanguage::createFromLangcode($langcode);
  }
  $language->save();

  // If a non-English language was selected, change the default language and
  // remove English.
  if ($langcode != 'en') {
    \Drupal::configFactory()->getEditable('system.site')->set('langcode', $langcode)->save();
    \Drupal::service('language.default')->set($language);
    if (empty($install_state['profile_info']['keep_english'])) {
      entity_delete_multiple('configurable_language', array('en'));
    }
  }

  // If there is more than one language or the single one is not English, we
  // should download/import translations.
  $languages = \Drupal::languageManager()->getLanguages();
  $operations = array();
  foreach($languages as $langcode => $language) {
    // The installer language was already downloaded. Check downloads for the
    // other languages if any. Ignore any download errors here, since we
    // are in the middle of an install process and there is no way back. We
    // will not import what we cannot download.
    if ($langcode != 'en' && $langcode != $install_state['parameters']['langcode']) {
      $operations[] = array('install_check_translations', array($langcode, $install_state['server_pattern']));
    }
  }
  return $operations;
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
  \Drupal::moduleHandler()->loadInclude('locale', 'translation.inc');

  // If there is more than one language or the single one is not English, we
  // should import translations.
  $operations = install_download_additional_translations_operations($install_state);
  $languages = \Drupal::languageManager()->getLanguages();
  if (count($languages) > 1 || !isset($languages['en'])) {
    $operations[] = array('_install_prepare_import', array(array_keys($languages), $install_state['server_pattern']));

    // Set up a batch to import translations for drupal core. Translation import
    // for contrib modules happens in install_import_translations_remaining.
    foreach ($languages as $language) {
      if (locale_translation_use_remote_source()) {
        $operations[] = array('locale_translation_batch_fetch_download', array('drupal', $language->getId()));
      }
      $operations[] = array('locale_translation_batch_fetch_import', array('drupal', $language->getId(), array()));
    }

    module_load_include('fetch.inc', 'locale');
    $batch = array(
      'operations' => $operations,
      'title' => t('Updating translations.'),
      'progress_message' => '',
      'error_message' => t('Error importing translation files'),
      'finished' => 'locale_translation_batch_fetch_finished',
      'file' => drupal_get_path('module', 'locale') . '/locale.batch.inc',
    );
    return $batch;
  }
}

/**
 * Tells the translation import process that Drupal core is installed.
 *
 * @param array $langcodes
 *   Language codes used for the translations.
 * @param string $server_pattern
 *   Server access pattern (to replace language code, version number, etc. in).
 */
function _install_prepare_import($langcodes, $server_pattern) {
  \Drupal::moduleHandler()->loadInclude('locale', 'bulk.inc');
  $matches = array();

  foreach ($langcodes as $langcode) {
    // Get the translation files located in the translations directory.
    $files = locale_translate_get_interface_translation_files(array('drupal'), array($langcode));
    // Pick the first file which matches the language, if any.
    $file = reset($files);
    if (is_object($file)) {
      $filename = $file->filename;
      preg_match('/drupal-([0-9a-z\.-]+)\.' . $langcode . '\.po/', $filename, $matches);
      // Get the version information.
      if ($version = $matches[1]) {
        $info = _install_get_version_info($version);
        // Picking the first file does not necessarily result in the right file. So
        // we check if at least the major version number is available.
        if ($info['major']) {
          $core = $info['major'] . '.x';
          $data = array(
            'name' => 'drupal',
            'project_type' => 'module',
            'core' => $core,
            'version' => $version,
            'server_pattern' => $server_pattern,
            'status' => 1,
          );
          \Drupal::service('locale.project')->set($data['name'], $data);
          module_load_include('compare.inc', 'locale');
          locale_translation_check_projects_local(array('drupal'), array($langcode));
        }
      }
    }
  }
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
 * @return array
 *   An array of batch definitions.
 */
function install_finish_translations(&$install_state) {
  \Drupal::moduleHandler()->loadInclude('locale', 'fetch.inc');
  \Drupal::moduleHandler()->loadInclude('locale', 'compare.inc');
  \Drupal::moduleHandler()->loadInclude('locale', 'bulk.inc');

  // Build a fresh list of installed projects. When more projects than core are
  // installed, their translations will be downloaded (if required) and imported
  // using a batch.
  $projects = locale_translation_build_projects();
  $languages = \Drupal::languageManager()->getLanguages();
  $batches = array();
  if (count($projects) > 1) {
    $options = _locale_translation_default_update_options();
    if ($batch = locale_translation_batch_update_build(array(), array_keys($languages), $options)) {
      $batches[] = $batch;
    }
  }

  // Creates configuration translations.
  $batches[] = locale_config_batch_update_components(array(), array_keys($languages));
  return $batches;
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

  // Build the router once after installing all modules.
  // This would normally happen upon KernelEvents::TERMINATE, but since the
  // installer does not use an HttpKernel, that event is never triggered.
  \Drupal::service('router.builder')->rebuild();

  // Run cron to populate update status tables (if available) so that users
  // will be warned if they've installed an out of date Drupal version.
  // Will also trigger indexing of profile-supplied content or feeds.
  \Drupal::service('cron')->run();

  if ($install_state['interactive']) {
    // Load current user and perform final login tasks.
    // This has to be done after drupal_flush_all_caches()
    // to avoid session regeneration.
    $account = user_load(1);
    user_login_finalize($account);
  }

  $success_message = t('Congratulations, you installed @drupal!', array(
    '@drupal' => drupal_install_profile_distribution_name(),
  ));
  drupal_set_message($success_message);
}

/**
 * Implements callback_batch_operation().
 *
 * Performs batch installation of modules.
 */
function _install_module_batch($module, $module_name, &$context) {
  \Drupal::service('module_installer')->install(array($module), FALSE);
  $context['results'][] = $module;
  $context['message'] = t('Installed %module module.', array('%module' => $module_name));
}

/**
 * Checks installation requirements and reports any errors.
 *
 * @param string $langcode
 *   Language code to check for download.
 * @param string $server_pattern
 *   Server access pattern (to replace language code, version number, etc. in).
 *
 * @return array
 *   Requirements compliance array. If the translation was downloaded
 *   successfully then an empty array is returned. Otherwise the requirements
 *   error with detailed information.
 */
function install_check_translations($langcode, $server_pattern) {
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
  if (drupal_verify_install_file($translations_directory, FILE_EXIST, 'dir')) {
    $readable = is_readable($translations_directory);
    $writable = is_writable($translations_directory);
    $translations_directory_exists = TRUE;
  }

  // The file already exists, no need to attempt to download.
  if ($existing_file = glob($translations_directory . '/drupal-*.' . $langcode . '.po')) {
    return;
  }

  // Build URLs for the translation file and the translation server.
  $releases = install_get_localization_release();
  $translation_urls = array();
  foreach ($releases as $release) {
    $variables = array(
      '%project' => 'drupal',
      '%version' => $release['version'],
      '%core' => $release['core'],
      '%language' => $langcode,
    );
    $translation_urls[] = strtr($server_pattern, $variables);
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

  if ($install_state['settings_verified']) {
    return $requirements;
  }

  // If Drupal is not set up already, we need to try to create the default
  // settings and services files.
  $default_files = array();
  $default_files['settings.php'] = array(
    'file' => 'settings.php',
    'file_default' => 'default.settings.php',
    'title_default' => t('Default settings file'),
    'description_default' => t('The default settings file does not exist.'),
    'title' => t('Settings file'),
  );
  $default_files['services.yml'] = array(
    'file' => 'services.yml',
    'file_default' => 'default.services.yml',
    'title_default' => t('Default services file'),
    'description_default' => t('The default services file does not exist.'),
    'title' => t('Services file'),
  );

  foreach ($default_files as $default_file_info) {
    $readable = FALSE;
    $writable = FALSE;
    $conf_path = './' . conf_path(FALSE);
    $file = $conf_path . "/{$default_file_info['file']}";
    $default_file = "./sites/default/{$default_file_info['file_default']}";
    $exists = FALSE;
    // Verify that the directory exists.
    if (drupal_verify_install_file($conf_path, FILE_EXIST, 'dir')) {
      if (drupal_verify_install_file($file, FILE_EXIST)) {
        // If it does, make sure it is writable.
        $readable = drupal_verify_install_file($file, FILE_READABLE);
        $writable = drupal_verify_install_file($file, FILE_WRITABLE);
        $exists = TRUE;
      }
    }

    // If the default $default_file does not exist, or is not readable,
    // report an error.
    if (!drupal_verify_install_file($default_file, FILE_EXIST | FILE_READABLE)) {
      $requirements["default $file file exists"] = array(
        'title' => $default_file_info['title_default'],
        'value' => $default_file_info['description_default'],
        'severity' => REQUIREMENT_ERROR,
        'description' => t('The @drupal installer requires that the %default-file file not be modified in any way from the original download.', array(
            '@drupal' => drupal_install_profile_distribution_name(),
            '%default-file' => $default_file
          )),
      );
    }
    // Otherwise, if $file does not exist yet, we can try to copy
    // $default_file to create it.
    elseif (!$exists) {
      $copied = drupal_verify_install_file($conf_path, FILE_EXIST | FILE_WRITABLE, 'dir') && @copy($default_file, $file);
      if ($copied) {
        // If the new $file file has the same owner as $default_file this means
        // $default_file is owned by the webserver user. This is an inherent
        // security weakness because it allows a malicious webserver process to
        // append arbitrary PHP code and then execute it. However, it is also a
        // common configuration on shared hosting, and there is nothing Drupal
        // can do to prevent it. In this situation, having $file also owned by
        // the webserver does not introduce any additional security risk, so we
        // keep the file in place. Additionally, this situation also occurs when
        // the test runner is being run be different user than the webserver.
        if (fileowner($default_file) === fileowner($file) || DRUPAL_TEST_IN_CHILD_SITE) {
          $readable = drupal_verify_install_file($file, FILE_READABLE);
          $writable = drupal_verify_install_file($file, FILE_WRITABLE);
          $exists = TRUE;
        }
        // If $file and $default_file have different owners, this probably means
        // the server is set up "securely" (with the webserver running as its
        // own user, distinct from the user who owns all the Drupal PHP files),
        // although with either a group or world writable sites directory.
        // Keeping $file owned by the webserver would therefore introduce a
        // security risk. It would also cause a usability problem, since site
        // owners who do not have root access to the file system would be unable
        // to edit their settings file later on. We therefore must delete the
        // file we just created and force the administrator to log on to the
        // server and create it manually.
        else {
          $deleted = @drupal_unlink($file);
          // We expect deleting the file to be successful (since we just
          // created it ourselves above), but if it fails somehow, we set a
          // variable so we can display a one-time error message to the
          // administrator at the bottom of the requirements list. We also try
          // to make the file writable, to eliminate any conflicting error
          // messages in the requirements list.
          $exists = !$deleted;
          if ($exists) {
            $settings_file_ownership_error = TRUE;
            $readable = drupal_verify_install_file($file, FILE_READABLE);
            $writable = drupal_verify_install_file($file, FILE_WRITABLE);
          }
        }
      }
    }

    // If the $file does not exist, throw an error.
    if (!$exists) {
      $requirements["$file file exists"] = array(
        'title' => $default_file_info['title'],
        'value' => t('The %file does not exist.', array('%file' => $default_file_info['title'])),
        'severity' => REQUIREMENT_ERROR,
        'description' => t('The @drupal installer requires that you create a %file as part of the installation process. Copy the %default_file file to %file. More details about installing Drupal are available in <a href="@install_txt">INSTALL.txt</a>.', array(
            '@drupal' => drupal_install_profile_distribution_name(),
            '%file' => $file,
            '%default_file' => $default_file,
            '@install_txt' => base_path() . 'core/INSTALL.txt'
          )),
      );
    }
    else {
      $requirements["$file file exists"] = array(
        'title' => $default_file_info['title'],
        'value' => t('The %file exists.', array('%file' => $file)),
      );
      // If the $file is not readable, throw an error.
      if (!$readable) {
        $requirements["$file file readable"] = array(
          'title' => $default_file_info['title'],
          'value' => t('The %file is not readable.', array('%file' => $default_file_info['title'])),
          'severity' => REQUIREMENT_ERROR,
          'description' => t('@drupal requires read permissions to %file at all times. If you are unsure how to grant file permissions, consult the <a href="@handbook_url">online handbook</a>.', array(
              '@drupal' => drupal_install_profile_distribution_name(),
              '%file' => $file,
              '@handbook_url' => 'http://drupal.org/server-permissions'
            )),
        );
      }
      // If the $file is not writable, throw an error.
      if (!$writable) {
        $requirements["$file file writeable"] = array(
          'title' => $default_file_info['title'],
          'value' => t('The %file is not writable.', array('%file' => $default_file_info['title'])),
          'severity' => REQUIREMENT_ERROR,
          'description' => t('The @drupal installer requires write permissions to %file during the installation process. If you are unsure how to grant file permissions, consult the <a href="@handbook_url">online handbook</a>.', array(
              '@drupal' => drupal_install_profile_distribution_name(),
              '%file' => $file,
              '@handbook_url' => 'http://drupal.org/server-permissions'
            )),
        );
      }
      else {
        $requirements["$file file"] = array(
          'title' => $default_file_info['title'],
          'value' => t('The @file is writable.', array('@file' => $default_file_info['title'])),
        );
      }
      if (!empty($settings_file_ownership_error)) {
        $requirements["$file file ownership"] = array(
          'title' => $default_file_info['title'],
          'value' => t('The @file is owned by the web server.', array('@file' => $default_file_info['title'])),
          'severity' => REQUIREMENT_ERROR,
          'description' => t('The @drupal installer failed to create a %file file with proper file ownership. Log on to your web server, remove the existing %file file, and create a new one by copying the %default_file file to %file. More details about installing Drupal are available in <a href="@install_txt">INSTALL.txt</a>. If you have problems with the file permissions on your server, consult the <a href="@handbook_url">online handbook</a>.', array(
              '@drupal' => drupal_install_profile_distribution_name(),
              '%file' => $file,
              '%default_file' => $default_file,
              '@install_txt' => base_path() . 'core/INSTALL.txt',
              '@handbook_url' => 'http://drupal.org/server-permissions'
            )),
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
      $build['report']['#theme'] = 'status_report';
      $build['report']['#requirements'] = $requirements;
      if ($severity == REQUIREMENT_WARNING) {
        $build['#title'] = t('Requirements review');
        $build['#suffix'] = t('Check the messages and <a href="!retry">retry</a>, or you may choose to <a href="!cont">continue anyway</a>.', array('!retry' => check_url(drupal_requirements_url(REQUIREMENT_ERROR)), '!cont' => check_url(drupal_requirements_url($severity))));
      }
      else {
        $build['#title'] = t('Requirements problem');
        $build['#suffix'] = t('Check the messages and <a href="!url">try again</a>.', array('!url' => check_url(drupal_requirements_url($severity))));
      }
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
