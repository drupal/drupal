<?php
// $Id$

/**
 * @file
 * API functions for installing Drupal.
 */

/**
 * Global flag to indicate that a task should not be run during the current
 * installation request.
 *
 * This can be used to skip running an installation task when certain
 * conditions are met, even though the task may still show on the list of
 * installation tasks presented to the user. For example, the Drupal installer
 * uses this flag to skip over the database configuration form when valid
 * database connection information is already available from settings.php. It
 * also uses this flag to skip language import tasks when the installation is
 * being performed in English.
 */
define('INSTALL_TASK_SKIP', 1);

/**
 * Global flag to indicate that a task should be run on each installation
 * request that reaches it.
 *
 * This is primarily used by the Drupal installer for bootstrap-related tasks.
 */
define('INSTALL_TASK_RUN_IF_REACHED', 2);

/**
 * Global flag to indicate that a task should be run on each installation
 * request that reaches it, until the database is set up and we are able to
 * record the fact that it already ran.
 *
 * This is the default method for running tasks and should be used for most
 * tasks that occur after the database is set up; these tasks will then run
 * once and be marked complete once they are successfully finished. For
 * example, the Drupal installer uses this flag for the batch installation of
 * modules on the new site, and also for the configuration form that collects
 * basic site information and sets up the site maintenance account.
 */
define('INSTALL_TASK_RUN_IF_NOT_COMPLETED', 3);

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
  catch (Exception $e) {
    // When an installation error occurs, either send the error to the web
    // browser or pass on the exception so the calling script can use it.
    if ($install_state['interactive']) {
      install_display_output($e->getMessage(), $install_state);
    }
    else {
      throw $e;
    }
  }
  // All available tasks for this page request are now complete. Interactive
  // installations can send output to the browser or redirect the user to the
  // next page.
  if ($install_state['interactive']) {
    if ($install_state['parameters_changed']) {
      // Redirect to the correct page if the URL parameters have changed.
      install_goto(install_redirect_url($install_state));
    }
    elseif (isset($output)) {
      // Display a page only if some output is available. Otherwise it is
      // possible that we are printing a JSON page and theme output should
      // not be shown.
      install_display_output($output, $install_state);
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
 * 'locale' that are normally passed in via the URL) and 'forms' (which can
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
    // This becomes TRUE only when Drupal's system module is installed.
    'database_tables_exist' => FALSE,
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
    // An array of available languages for the installation.
    'locales' => array(),
    // An array of parameters for the installation, pre-populated by the URL
    // or by the settings passed in to install_drupal(). This is primarily
    // used to store 'profile' (the name of the chosen installation profile)
    // and 'locale' (the name of the chosen installation language), since
    // these settings need to persist from page request to page request before
    // the database is available for storage.
    'parameters' => array(),
    // Whether or not the parameters have changed during the current page
    // request. For interactive installations, this will trigger a page
    // redirect.
    'parameters_changed' => FALSE,
    // An array of information about the chosen installation profile. This will
    // be filled in based on the profile's .info file.
    'profile_info' => array(),
    // An array of available installation profiles.
    'profiles' => array(),
    // An array of server variables that will be substituted into the global
    // $_SERVER array via drupal_override_server_variables(). Used by
    // non-interactive installations only.
    'server' => array(),
    // This becomes TRUE only when a valid database connection can be
    // established.
    'settings_verified' => FALSE,
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
  );
  return $defaults;
}

/**
 * Begin an installation request, modifying the installation state as needed.
 *
 * This function performs commands that must run at the beginning of every page
 * request. It throws an exception if the installation should not proceed.
 *
 * @param $install_state
 *   An array of information about the current installation state. This is
 *   modified with information gleaned from the beginning of the page request.
 */
function install_begin_request(&$install_state) {
  // Add any installation parameters passed in via the URL.
  $install_state['parameters'] += $_GET;

  // Validate certain core settings that are used throughout the installation.
  if (!empty($install_state['parameters']['profile'])) {
    $install_state['parameters']['profile'] = preg_replace('/[^a-zA-Z_0-9]/', '', $install_state['parameters']['profile']);
  }
  if (!empty($install_state['parameters']['locale'])) {
    $install_state['parameters']['locale'] = preg_replace('/[^a-zA-Z_0-9\-]/', '', $install_state['parameters']['locale']);
  }

  // Allow command line scripts to override server variables used by Drupal.
  require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
  if (!$install_state['interactive']) {
    drupal_override_server_variables($install_state['server']);
  }

  // The user agent header is used to pass a database prefix in the request when
  // running tests. However, for security reasons, it is imperative that no
  // installation be permitted using such a prefix.
  if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], "simpletest") !== FALSE) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
    exit;
  }

  drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION);

  // This must go after drupal_bootstrap(), which unsets globals!
  global $conf;

  require_once DRUPAL_ROOT . '/modules/system/system.install';
  require_once DRUPAL_ROOT . '/includes/common.inc';
  require_once DRUPAL_ROOT . '/includes/file.inc';
  require_once DRUPAL_ROOT . '/includes/install.inc';
  require_once DRUPAL_ROOT . '/' . variable_get('path_inc', 'includes/path.inc');

  // Load module basics (needed for hook invokes).
  include_once DRUPAL_ROOT . '/includes/module.inc';
  include_once DRUPAL_ROOT . '/includes/session.inc';

  // Set up $language, so t() caller functions will still work.
  drupal_language_initialize();

  include_once DRUPAL_ROOT . '/includes/entity.inc';
  require_once DRUPAL_ROOT . '/includes/ajax.inc';
  $module_list['system']['filename'] = 'modules/system/system.module';
  $module_list['user']['filename'] = 'modules/user/user.module';
  module_list(TRUE, FALSE, FALSE, $module_list);
  drupal_load('module', 'system');
  drupal_load('module', 'user');

  // Load the cache infrastructure using a "fake" cache implementation that
  // does not attempt to write to the database. We need this during the initial
  // part of the installer because the database is not available yet. We
  // continue to use it even when the database does become available, in order
  // to preserve consistency between interactive and command-line installations
  // (the latter complete in one page request and therefore are forced to
  // continue using the cache implementation they started with) and also
  // because any data put in the cache during the installer is inherently
  // suspect, due to the fact that Drupal is not fully set up yet.
  require_once DRUPAL_ROOT . '/includes/cache.inc';
  require_once DRUPAL_ROOT . '/includes/cache-install.inc';
  $conf['cache_default_class'] = 'DrupalFakeCache';

  // Prepare for themed output. We need to run this at the beginning of the
  // page request to avoid a different theme accidentally getting set. (We also
  // need to run it even in the case of command-line installations, to prevent
  // any code in the installer that happens to initialize the theme system from
  // accessing the database before it is set up yet.)
  drupal_maintenance_theme();

  // Check existing settings.php.
  $install_state['settings_verified'] = install_verify_settings();

  if ($install_state['settings_verified']) {
    // Initialize the database system. Note that the connection
    // won't be initialized until it is actually requested.
    require_once DRUPAL_ROOT . '/includes/database/database.inc';

    // Verify the last completed task in the database, if there is one.
    $task = install_verify_completed_task();
  }
  else {
    $task = NULL;

    // Since previous versions of Drupal stored database connection information
    // in the 'db_url' variable, we should never let an installation proceed if
    // this variable is defined and the settings file was not verified above
    // (otherwise we risk installing over an existing site whose settings file
    // has not yet been updated).
    if (!empty($GLOBALS['db_url'])) {
      throw new Exception(install_already_done_error());
    }
  }

  // Modify the installation state as appropriate.
  $install_state['completed_task'] = $task;
  $install_state['database_tables_exist'] = !empty($task);
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
      if ($install_state['database_tables_exist'] && ($task['run'] == INSTALL_TASK_RUN_IF_NOT_COMPLETED || $install_state['installation_finished'])) {
        variable_set('install_task', $install_state['installation_finished'] ? 'done' : $task_name);
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
    require_once DRUPAL_ROOT . '/includes/form.inc';
    if ($install_state['interactive']) {
      // For interactive forms, build the form and ensure that it will not
      // redirect, since the installer handles its own redirection only after
      // marking the form submission task complete.
      $form_state = array(
        // We need to pass $install_state by reference in order for forms to
        // modify it, since the form API will use it in call_user_func_array(),
        // which requires that referenced variables be passed explicitly.
        'build_info' => array('args' => array(&$install_state)),
        'no_redirect' => TRUE,
      );
      $form = drupal_build_form($function, $form_state);
      // If a successful form submission did not occur, the form needs to be
      // rendered, which means the task is not complete yet.
      if (empty($form_state['executed'])) {
        $install_state['task_not_complete'] = TRUE;
        return drupal_render($form);
      }
      // Otherwise, return nothing so the next task will run in the same
      // request.
      return;
    }
    else {
      // For non-interactive forms, submit the form programmatically with the
      // values taken from the installation state. Throw an exception if any
      // errors were encountered.
      $form_state = array(
        'values' => !empty($install_state['forms'][$function]) ? $install_state['forms'][$function] : array(),
        // We need to pass $install_state by reference in order for forms to
        // modify it, since the form API will use it in call_user_func_array(),
        // which requires that referenced variables be passed explicitly.
        'build_info' => array('args' => array(&$install_state)),
      );
      drupal_form_submit($function, $form_state);
      $errors = form_get_errors();
      if (!empty($errors)) {
        throw new Exception(implode("\n", $errors));
      }
    }
  }

  elseif ($task['type'] == 'batch') {
    // Start a new batch based on the task function, if one is not running
    // already.
    $current_batch = variable_get('install_current_batch');
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
        variable_set('install_current_batch', $function);
      }
      else {
        $batch =& batch_get();
        $batch['progressive'] = FALSE;
      }
      // Process the batch. For progressive batches, this will redirect.
      // Otherwise, the batch will complete.
      batch_process(install_redirect_url($install_state), install_full_redirect_url($install_state));
    }
    // If we are in the middle of processing this batch, keep sending back
    // any output from the batch process, until the task is complete.
    elseif ($current_batch == $function) {
      include_once DRUPAL_ROOT . '/includes/batch.inc';
      $output = _batch_page();
      // The task is complete when we try to access the batch page and receive
      // FALSE in return, since this means we are at a URL where we are no
      // longer requesting a batch ID.
      if ($output === FALSE) {
        // Return nothing so the next task will run in the same request.
        variable_del('install_current_batch');
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
  // Determine whether translation import tasks will need to be performed.
  $needs_translations = count($install_state['locales']) > 1 && !empty($install_state['parameters']['locale']) && $install_state['parameters']['locale'] != 'en';

  // Start with the core installation tasks that run before handing control
  // to the install profile.
  $tasks = array(
    'install_select_profile' => array(
      'display_name' => st('Choose profile'),
      'display' => count($install_state['profiles']) != 1,
      'run' => INSTALL_TASK_RUN_IF_REACHED,
    ),
    'install_select_locale' => array(
      'display_name' => st('Choose language'),
      'run' => INSTALL_TASK_RUN_IF_REACHED,
    ),
    'install_load_profile' => array(
      'run' => INSTALL_TASK_RUN_IF_REACHED,
    ),
    'install_verify_requirements' => array(
      'display_name' => st('Verify requirements'),
    ),
    'install_settings_form' => array(
      'display_name' => st('Set up database'),
      'type' => 'form',
      'run' => $install_state['settings_verified'] ? INSTALL_TASK_SKIP : INSTALL_TASK_RUN_IF_NOT_COMPLETED,
    ),
    'install_system_module' => array(
    ),
    'install_bootstrap_full' => array(
      'run' => INSTALL_TASK_RUN_IF_REACHED,
    ),
    'install_profile_modules' => array(
      'display_name' => count($install_state['profiles']) == 1 ? st('Install site') : st('Install profile'),
      'type' => 'batch',
    ),
    'install_import_locales' => array(
      'display_name' => st('Set up translations'),
      'display' => $needs_translations,
      'type' => 'batch',
      'run' => $needs_translations ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
    ),
    'install_configure_form' => array(
      'display_name' => st('Configure site'),
      'type' => 'form',
    ),
  );

  // Now add any tasks defined by the installation profile.
  if (!empty($install_state['parameters']['profile'])) {
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
    'install_import_locales_remaining' => array(
      'display_name' => st('Finish translations'),
      'display' => $needs_translations,
      'type' => 'batch',
      'run' => $needs_translations ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
    ),
    'install_finished' => array(
      'display_name' => st('Finished'),
    ),
  );

  // Allow the installation profile to modify the full list of tasks.
  if (!empty($install_state['parameters']['profile'])) {
    $profile_file = DRUPAL_ROOT . '/profiles/' . $install_state['parameters']['profile'] . '/' . $install_state['parameters']['profile'] . '.profile';
    if (is_file($profile_file)) {
      include_once $profile_file;
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
  return 'install.php?' . drupal_http_build_query($install_state['parameters']);
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
 * Installation tasks should use drupal_set_title() to set the desired page
 * title, but otherwise this function takes care of theming the overall page
 * output during every step of the installation.
 *
 * @param $output
 *   The content to display on the main part of the page.
 * @param $install_state
 *   An array of information about the current installation state.
 */
function install_display_output($output, $install_state) {
  drupal_page_header();
  // Only show the task list if there is an active task; otherwise, the page
  // request has ended before tasks have even been started, so there is nothing
  // meaningful to show.
  if (isset($install_state['active_task'])) {
    // Let the theming function know when every step of the installation has
    // been completed.
    $active_task = $install_state['installation_finished'] ? NULL : $install_state['active_task'];
    drupal_add_region_content('sidebar_first', theme('task_list', array('items' => install_tasks_to_display($install_state), 'active' => $active_task)));
  }
  print theme('install_page', array('content' => $output));
  exit;
}

/**
 * Installation task; verify the requirements for installing Drupal.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @return
 *   A themed status report, or an exception if there are requirement errors.
 *   Otherwise, no output is returned, so that the next task can be run
 *   in the same page request.
 */
function install_verify_requirements(&$install_state) {
  // Check the installation requirements for Drupal and this profile.
  $requirements = install_check_requirements($install_state);

  // Verify existence of all required modules.
  $requirements += drupal_verify_profile($install_state);

  // Check the severity of the requirements reported.
  $severity = drupal_requirements_severity($requirements);

  if ($severity == REQUIREMENT_ERROR) {
    if ($install_state['interactive']) {
      drupal_set_title(st('Requirements problem'));
      $status_report = theme('status_report', array('requirements' => $requirements));
      $status_report .= st('Check the error messages and <a href="!url">proceed with the installation</a>.', array('!url' => check_url(request_uri())));
      return $status_report;
    }
    else {
      // Throw an exception showing all unmet requirements.
      $failures = array();
      foreach ($requirements as $requirement) {
        if (isset($requirement['severity']) && $requirement['severity'] == REQUIREMENT_ERROR) {
          $failures[] = $requirement['title'] . ': ' . $requirement['value'] . "\n\n" . $requirement['description'];
        }
      }
      throw new Exception(implode("\n\n", $failures));
    }
  }
}

/**
 * Installation task; install the Drupal system module.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 */
function install_system_module(&$install_state) {
  // Install system.module.
  drupal_install_system();

  // Enable the user module so that sessions can be recorded during the
  // upcoming bootstrap step.
  module_enable(array('user'), FALSE);

  // Save the list of other modules to install for the upcoming tasks.
  // variable_set() can be used now that system.module is installed.
  $modules = $install_state['profile_info']['dependencies'];

  // The install profile is also a module, which needs to be installed
  // after all the dependencies have been installed.
  $modules[] = drupal_get_profile();

  variable_set('install_profile_modules', array_diff($modules, array('system')));
  $install_state['database_tables_exist'] = TRUE;
}

/**
 * Verify and return the last installation task that was completed.
 *
 * @return
 *   The last completed task, if there is one. An exception is thrown if Drupal
 *   is already installed.
 */
function install_verify_completed_task() {
  try {
    if ($result = db_query("SELECT value FROM {variable} WHERE name = :name", array('name' => 'install_task'))) {
      $task = unserialize($result->fetchField());
    }
  }
  // Do not trigger an error if the database query fails, since the database
  // might not be set up yet.
  catch (Exception $e) {
  }
  if (isset($task)) {
    if ($task == 'done') {
      throw new Exception(install_already_done_error());
    }
    return $task;
  }
}

/**
 * Verifies the existing settings in settings.php.
 */
function install_verify_settings() {
  global $databases;

  // Verify existing settings (if any).
  if (!empty($databases) && install_verify_pdo()) {
    $database = $databases['default']['default'];
    drupal_static_reset('conf_path');
    $settings_file = './' . conf_path(FALSE) . '/settings.php';
    $errors = install_database_errors($database, $settings_file);
    if (empty($errors)) {
      return TRUE;
    }
  }
  return FALSE;
}

/**
 * Verify PDO library.
 */
function install_verify_pdo() {
  // PDO was moved to PHP core in 5.2.0, but the old extension (targeting 5.0
  // and 5.1) is still available from PECL, and can still be built without
  // errors. To verify that the correct version is in use, we check the
  // PDO::ATTR_DEFAULT_FETCH_MODE constant, which is not available in the
  // PECL extension.
  return extension_loaded('pdo') && defined('PDO::ATTR_DEFAULT_FETCH_MODE');
}

/**
 * Installation task; define a form to configure and rewrite settings.php.
 *
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @return
 *   The form API definition for the database configuration form.
 */
function install_settings_form($form, &$form_state, &$install_state) {
  global $databases;
  $profile = $install_state['parameters']['profile'];
  $install_locale = $install_state['parameters']['locale'];

  drupal_static_reset('conf_path');
  $conf_path = './' . conf_path(FALSE);
  $settings_file = $conf_path . '/settings.php';
  $database = isset($databases['default']['default']) ? $databases['default']['default'] : array();

  drupal_set_title(st('Database configuration'));

  $drivers = drupal_get_database_types();
  $drivers_keys = array_keys($drivers);

  $form['driver'] = array(
    '#type' => 'radios',
    '#title' => st('Database type'),
    '#required' => TRUE,
    '#default_value' => !empty($database['driver']) ? $database['driver'] : current($drivers_keys),
    '#description' => st('The type of database your @drupal data will be stored in.', array('@drupal' => drupal_install_profile_distribution_name())),
  );
  if (count($drivers) == 1) {
    $form['driver']['#disabled'] = TRUE;
    $form['driver']['#description'] .= ' ' . st('Your PHP configuration only supports a single database type, so it has been automatically selected.');
  }

  // Add driver specific configuration options.
  foreach ($drivers as $key => $driver) {
    $form['driver']['#options'][$key] = $driver->name();

    $form['settings'][$key] = $driver->getFormOptions($database);
    $form['settings'][$key]['#prefix'] = '<h2 class="js-hide">' . st('@driver_name settings', array('@driver_name' => $driver->name())) . '</h2>';
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
    '#value' => st('Save and continue'),
    '#limit_validation_errors' => array(
      array('driver'),
      array(isset($form_state['input']['driver']) ? $form_state['input']['driver'] : current($drivers_keys)),
    ),
    '#submit' => array('install_settings_form_submit'),
  );

  $form['errors'] = array();
  $form['settings_file'] = array('#type' => 'value', '#value' => $settings_file);

  return $form;
}

/**
 * Form API validate for install_settings form.
 */
function install_settings_form_validate($form, &$form_state) {
  $driver = $form_state['values']['driver'];
  $database = $form_state['values'][$driver];
  $database['driver'] = $driver;

  // TODO: remove when PIFR will be updated to use 'db_prefix' instead of
  // 'prefix' in the database settings form.
  $database['prefix'] = $database['db_prefix'];
  unset($database['db_prefix']);

  $form_state['storage']['database'] = $database;
  $errors = install_database_errors($database, $form_state['values']['settings_file']);
  foreach ($errors as $name => $message) {
    form_set_error($name, $message);
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
    $errors['driver'] = st("In your %settings_file file you have configured @drupal to use a %driver server, however your PHP installation currently does not support this database type.", array('%settings_file' => $settings_file, '@drupal' => drupal_install_profile_distribution_name(), '%driver' => $driver));
  }
  else {
    // Run driver specific validation
    $errors += $database_types[$driver]->validateDatabaseSettings($database);

    // Run tasks associated with the database type. Any errors are caught in the
    // calling function.
    $databases['default']['default'] = $database;
    // Just changing the global doesn't get the new information processed.
    // We tell tell the Database class to re-parse $databases.
    Database::parseConnectionInfo();

    try {
      db_run_tasks($driver);
    }
    catch (DatabaseTaskException $e) {
      // These are generic errors, so we do not have any specific key of the
      // database connection array to attach them to; therefore, we just put
      // them in the error array with standard numeric keys.
      $errors[$driver . '][0'] = $e->getMessage();
    }
  }
  return $errors;
}

/**
 * Form API submit for install_settings form.
 */
function install_settings_form_submit($form, &$form_state) {
  global $install_state;

  // Update global settings array and save.
  $settings['databases'] = array(
    'value'    => array('default' => array('default' => $form_state['storage']['database'])),
    'required' => TRUE,
  );
  $settings['drupal_hash_salt'] = array(
    'value'    => drupal_hash_base64(drupal_random_bytes(55)),
    'required' => TRUE,
  );
  drupal_rewrite_settings($settings);
  // Indicate that the settings file has been verified, and check the database
  // for the last completed task, now that we have a valid connection. This
  // last step is important since we want to trigger an error if the new
  // database already has Drupal installed.
  $install_state['settings_verified'] = TRUE;
  $install_state['completed_task'] = install_verify_completed_task();
}

/**
 * Finds all .profile files.
 */
function install_find_profiles() {
  return file_scan_directory('./profiles', '/\.profile$/', array('key' => 'name'));
}

/**
 * Installation task; select which profile to install.
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
  $install_state['profiles'] += install_find_profiles();
  if (empty($install_state['parameters']['profile'])) {
    // Try to find a profile.
    $profile = _install_select_profile($install_state['profiles']);
    if (empty($profile)) {
      // We still don't have a profile, so display a form for selecting one.
      // Only do this in the case of interactive installations, since this is
      // not a real form with submit handlers (the database isn't even set up
      // yet), rather just a convenience method for setting parameters in the
      // URL.
      if ($install_state['interactive']) {
        include_once DRUPAL_ROOT . '/includes/form.inc';
        drupal_set_title(st('Select an installation profile'));
        $form = drupal_get_form('install_select_profile_form', $install_state['profiles']);
        return drupal_render($form);
      }
      else {
        throw new Exception(install_no_profile_error());
      }
    }
    else {
      $install_state['parameters']['profile'] = $profile;
    }
  }
}

/**
 * Helper function for automatically selecting an installation profile from a
 * list or from a selection passed in via $_POST.
 */
function _install_select_profile($profiles) {
  if (sizeof($profiles) == 0) {
    throw new Exception(install_no_profile_error());
  }
  // Don't need to choose profile if only one available.
  if (sizeof($profiles) == 1) {
    $profile = array_pop($profiles);
    // TODO: is this right?
    require_once DRUPAL_ROOT . '/' . $profile->uri;
    return $profile->name;
  }
  else {
    foreach ($profiles as $profile) {
      if (!empty($_POST['profile']) && ($_POST['profile'] == $profile->name)) {
        return $profile->name;
      }
    }
  }
}

/**
 * Form API array definition for the profile selection form.
 *
 * @param $form_state
 *   Array of metadata about state of form processing.
 * @param $profile_files
 *   Array of .profile files, as returned from file_scan_directory().
 */
function install_select_profile_form($form, &$form_state, $profile_files) {
  $profiles = array();
  $names = array();

  foreach ($profile_files as $profile) {
    // TODO: is this right?
    include_once DRUPAL_ROOT . '/' . $profile->uri;

    $details = install_profile_info($profile->name);
    // Don't show hidden profiles. This is used by to hide the testing profile,
    // which only exists to speed up test runs.
    if ($details['hidden'] === TRUE) {
      continue;
    }
    $profiles[$profile->name] = $details;

    // Determine the name of the profile; default to file name if defined name
    // is unspecified.
    $name = isset($details['name']) ? $details['name'] : $profile->name;
    $names[$profile->name] = $name;
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

  foreach ($names as $profile => $name) {
    $form['profile'][$name] = array(
      '#type' => 'radio',
      '#value' => 'standard',
      '#return_value' => $profile,
      '#title' => $name,
      '#description' => isset($profiles[$profile]['description']) ? $profiles[$profile]['description'] : '',
      '#parents' => array('profile'),
    );
  }
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] =  array(
    '#type' => 'submit',
    '#value' => st('Save and continue'),
  );
  return $form;
}

/**
 * Find all .po files for the current profile.
 */
function install_find_locales($profilename) {
  $locales = file_scan_directory('./profiles/' . $profilename . '/translations', '/\.po$/', array('recurse' => FALSE));
  array_unshift($locales, (object) array('name' => 'en'));
  foreach ($locales as $key => $locale) {
    // The locale (file name) might be drupal-7.2.cs.po instead of cs.po.
    $locales[$key]->langcode = preg_replace('!^(.+\.)?([^\.]+)$!', '\2', $locale->name);
    // Language codes cannot exceed 12 characters to fit into the {languages}
    // table.
    if (strlen($locales[$key]->langcode) > 12) {
      unset($locales[$key]);
    }
  }
  return $locales;
}

/**
 * Installation task; select which locale to use for the current profile.
 *
 * @param $install_state
 *   An array of information about the current installation state. The chosen
 *   locale will be added here, if it was not already selected previously, as
 *   will a list of all available locales.
 *
 * @return
 *   For interactive installations, a form or other page output allowing the
 *   locale to be selected or providing information about locale selection, if
 *   a locale has not been chosen. Otherwise, an exception is thrown if a
 *   locale cannot be chosen automatically.
 */
function install_select_locale(&$install_state) {
  // Find all available locales.
  $profilename = $install_state['parameters']['profile'];
  $locales = install_find_locales($profilename);
  $install_state['locales'] += $locales;

  if (!empty($_POST['locale'])) {
    foreach ($locales as $locale) {
      if ($_POST['locale'] == $locale->langcode) {
        $install_state['parameters']['locale'] = $locale->langcode;
        return;
      }
    }
  }

  if (empty($install_state['parameters']['locale'])) {
    // If only the built-in (English) language is available, and we are
    // performing an interactive installation, inform the user that the
    // installer can be localized. Otherwise we assume the user knows what he
    // is doing.
    if (count($locales) == 1) {
      if ($install_state['interactive']) {
        drupal_set_title(st('Choose language'));
        if (!empty($install_state['parameters']['localize'])) {
          $output = '<p>Follow these steps to translate Drupal into your language:</p>';
          $output .= '<ol>';
          $output .= '<li>Download a translation from the <a href="http://localize.drupal.org/download" target="_blank">translation server</a>.</li>';
          $output .= '<li>Place it into the following directory:
<pre>
/profiles/' . $profilename . '/translations/
</pre></li>';
          $output .= '</ol>';
          $output .= '<p>For more information on installing Drupal in different languages, visit the <a href="http://drupal.org/localize" target="_blank">drupal.org handbook page</a>.</p>';
          $output .= '<p>How should the installation continue?</p>';
          $output .= '<ul>';
          $output .= '<li><a href="install.php?profile=' . $profilename . '">Reload the language selection page after adding translations</a></li>';
          $output .= '<li><a href="install.php?profile=' . $profilename . '&amp;locale=en">Continue installation in English</a></li>';
          $output .= '</ul>';
        }
        else {
          include_once DRUPAL_ROOT . '/includes/form.inc';
          $elements = drupal_get_form('install_select_locale_form', $locales, $profilename);
          $output = drupal_render($elements);
        }
        return $output;
      }
      // One language, but not an interactive installation. Assume the user
      // knows what he is doing.
      $locale = current($locales);
      $install_state['parameters']['locale'] = $locale->name;
      return;
    }
    else {
      // Allow profile to pre-select the language, skipping the selection.
      $function = $profilename . '_profile_details';
      if (function_exists($function)) {
        $details = $function();
        if (isset($details['language'])) {
          foreach ($locales as $locale) {
            if ($details['language'] == $locale->name) {
              $install_state['parameters']['locale'] = $locale->name;
              return;
            }
          }
        }
      }

      // We still don't have a locale, so display a form for selecting one.
      // Only do this in the case of interactive installations, since this is
      // not a real form with submit handlers (the database isn't even set up
      // yet), rather just a convenience method for setting parameters in the
      // URL.
      if ($install_state['interactive']) {
        drupal_set_title(st('Choose language'));
        include_once DRUPAL_ROOT . '/includes/form.inc';
        $elements = drupal_get_form('install_select_locale_form', $locales, $profilename);
        return drupal_render($elements);
      }
      else {
        throw new Exception(st('Sorry, you must select a language to continue the installation.'));
      }
    }
  }
}

/**
 * Form API array definition for language selection.
 */
function install_select_locale_form($form, &$form_state, $locales, $profilename) {
  include_once DRUPAL_ROOT . '/includes/iso.inc';
  $languages = _locale_get_predefined_list();
  foreach ($locales as $locale) {
    $name = $locale->langcode;
    if (isset($languages[$name])) {
      $name = $languages[$name][0] . (isset($languages[$name][1]) ? ' ' . st('(@language)', array('@language' => $languages[$name][1])) : '');
    }
    $form['locale'][$locale->langcode] = array(
      '#type' => 'radio',
      '#return_value' => $locale->langcode,
      '#default_value' => $locale->langcode == 'en' ? 'en' : '',
      '#title' => $name . ($locale->langcode == 'en' ? ' ' . st('(built-in)') : ''),
      '#parents' => array('locale')
    );
  }
  if (count($locales) == 1) {
    $form['help'] = array(
      '#markup' => '<p><a href="install.php?profile=' . $profilename . '&amp;localize=true">' . st('Learn how to install Drupal in other languages') . '</a></p>',
    );
  }
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] =  array(
    '#type' => 'submit',
    '#value' => st('Save and continue'),
  );
  return $form;
}

/**
 * Indicates that there are no profiles available.
 */
function install_no_profile_error() {
  drupal_set_title(st('No profiles available'));
  return st('We were unable to find any installation profiles. Installation profiles tell us what modules to enable and what schema to install in the database. A profile is necessary to continue with the installation process.');
}

/**
 * Indicates that Drupal has already been installed.
 */
function install_already_done_error() {
  global $base_url;

  drupal_set_title(st('Drupal already installed'));
  return st('<ul><li>To start over, you must empty your existing database.</li><li>To install to a different database, edit the appropriate <em>settings.php</em> file in the <em>sites</em> folder.</li><li>To upgrade an existing installation, proceed to the <a href="@base-url/update.php">update script</a>.</li><li>View your <a href="@base-url">existing site</a>.</li></ul>', array('@base-url' => $base_url));
}

/**
 * Installation task; load information about the chosen profile.
 *
 * @param $install_state
 *   An array of information about the current installation state. The loaded
 *   profile information will be added here, or an exception will be thrown if
 *   the profile cannot be loaded.
 */
function install_load_profile(&$install_state) {
  $profile_file = DRUPAL_ROOT . '/profiles/' . $install_state['parameters']['profile'] . '/' . $install_state['parameters']['profile'] . '.profile';
  if (is_file($profile_file)) {
    include_once $profile_file;
    $install_state['profile_info'] = install_profile_info($install_state['parameters']['profile'], $install_state['parameters']['locale']);
  }
  else {
    throw new Exception(st('Sorry, the profile you have chosen cannot be loaded.'));
  }
}

/**
 * Installation task; perform a full bootstrap of Drupal.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 */
function install_bootstrap_full(&$install_state) {
  drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
}

/**
 * Installation task; install required modules via a batch process.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @return
 *   The batch definition.
 */
function install_profile_modules(&$install_state) {
  $modules = variable_get('install_profile_modules', array());
  $files = system_rebuild_module_data();
  variable_del('install_profile_modules');

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
    'title' => st('Installing @drupal', array('@drupal' => drupal_install_profile_distribution_name())),
    'error_message' => st('The installation has encountered an error.'),
    'finished' => '_install_profile_modules_finished',
  );
  return $batch;
}

/**
 * Installation task; import languages via a batch process.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @return
 *   The batch definition, if there are language files to import.
 */
function install_import_locales(&$install_state) {
  include_once DRUPAL_ROOT . '/includes/locale.inc';
  $install_locale = $install_state['parameters']['locale'];

  include_once DRUPAL_ROOT . '/includes/iso.inc';
  $predefined = _locale_get_predefined_list();
  if (!isset($predefined[$install_locale])) {
    // Drupal does not know about this language, so we prefill its values with
    // our best guess. The user will be able to edit afterwards.
    locale_add_language($install_locale, $install_locale, $install_locale, LANGUAGE_LTR, '', '', TRUE, TRUE);
  }
  else {
    // A known predefined language, details will be filled in properly.
    locale_add_language($install_locale, NULL, NULL, NULL, '', '', TRUE, TRUE);
  }

  // Collect files to import for this language.
  $batch = locale_batch_by_language($install_locale, NULL);
  if (!empty($batch)) {
    // Remember components we cover in this batch set.
    variable_set('install_locale_batch_components', $batch['#components']);
    return $batch;
  }
}

/**
 * Installation task; configure settings for the new site.
 *
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @return
 *   The form API definition for the site configuration form.
 */
function install_configure_form($form, &$form_state, &$install_state) {
  if (variable_get('site_name', FALSE) || variable_get('site_mail', FALSE)) {
    // Site already configured: This should never happen, means re-running the
    // installer, possibly by an attacker after the 'install_task' variable got
    // accidentally blown somewhere. Stop it now.
    throw new Exception(install_already_done_error());
  }

  drupal_set_title(st('Configure site'));

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
  if (empty($_POST) && (!drupal_verify_install_file(DRUPAL_ROOT . '/' . $settings_file, FILE_EXIST|FILE_READABLE|FILE_NOT_WRITABLE) || !drupal_verify_install_file(DRUPAL_ROOT . '/' . $settings_dir, FILE_NOT_WRITABLE, 'dir'))) {
    drupal_set_message(st('All necessary changes to %dir and %file have been made, so you should remove write permissions to them now in order to avoid security risks. If you are unsure how to do so, consult the <a href="@handbook_url">online handbook</a>.', array('%dir' => $settings_dir, '%file' => $settings_file, '@handbook_url' => 'http://drupal.org/server-permissions')), 'warning');
  }

  drupal_add_js(drupal_get_path('module', 'system') . '/system.js');
  // Add JavaScript time zone detection.
  drupal_add_js('misc/timezone.js');
  // We add these strings as settings because JavaScript translation does not
  // work on install time.
  drupal_add_js(array('copyFieldValue' => array('edit-site-mail' => array('edit-account-mail'))), 'setting');
  drupal_add_js('jQuery(function () { Drupal.cleanURLsInstallCheck(); });', 'inline');
  // Add JS to show / hide the 'Email administrator about site updates' elements
  drupal_add_js('jQuery(function () { Drupal.hideEmailAdministratorCheckbox() });', 'inline');
  // Build menu to allow clean URL check.
  menu_rebuild();

  // Cache a fully-built schema. This is necessary for any invocation of
  // index.php because: (1) setting cache table entries requires schema
  // information, (2) that occurs during bootstrap before any module are
  // loaded, so (3) if there is no cached schema, drupal_get_schema() will
  // try to generate one but with no loaded modules will return nothing.
  //
  // This logically could be done during the 'install_finished' task, but the
  // clean URL check requires it now.
  drupal_get_schema(NULL, TRUE);

  // Return the form.
  return _install_configure_form($form, $form_state, $install_state);
}

/**
 * Installation task; import remaining languages via a batch process.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @return
 *   The batch definition, if there are language files to import.
 */
function install_import_locales_remaining(&$install_state) {
  include_once DRUPAL_ROOT . '/includes/locale.inc';
  // Collect files to import for this language. Skip components already covered
  // in the initial batch set.
  $install_locale = $install_state['parameters']['locale'];
  $batch = locale_batch_by_language($install_locale, NULL, variable_get('install_locale_batch_components', array()));
  // Remove temporary variable.
  variable_del('install_locale_batch_components');
  return $batch;
}

/**
 * Installation task; perform final steps and display a 'finished' page.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @return
 *   A message informing the user that the installation is complete.
 */
function install_finished(&$install_state) {
  drupal_set_title(st('@drupal installation complete', array('@drupal' => drupal_install_profile_distribution_name())), PASS_THROUGH);
  $messages = drupal_set_message();
  $output = '<p>' . st('Congratulations, you installed @drupal!', array('@drupal' => drupal_install_profile_distribution_name())) . '</p>';
  $output .= '<p>' . (isset($messages['error']) ? st('Review the messages above before visiting <a href="@url">your new site</a>.', array('@url' => url(''))) : st('<a href="@url">Visit your new site</a>.', array('@url' => url('')))) . '</p>';

  // Flush all caches to ensure that any full bootstraps during the installer
  // do not leave stale cached data, and that any content types or other items
  // registered by the install profile are registered correctly.
  drupal_flush_all_caches();

  // Remember the profile which was used.
  variable_set('install_profile', drupal_get_profile());

  // Install profiles are always loaded last
  db_update('system')
    ->fields(array('weight' => 1000))
    ->condition('type', 'module')
    ->condition('name', drupal_get_profile())
    ->execute();

  // Cache a fully-built schema.
  drupal_get_schema(NULL, TRUE);

  // Run cron to populate update status tables (if available) so that users
  // will be warned if they've installed an out of date Drupal version.
  // Will also trigger indexing of profile-supplied content or feeds.
  drupal_cron_run();

  return $output;
}

/**
 * Batch callback for batch installation of modules.
 */
function _install_module_batch($module, $module_name, &$context) {
  // Install and enable the module right away, so that the module will be
  // loaded by drupal_bootstrap in subsequent batch requests, and other
  // modules possibly depending on it can safely perform their installation
  // steps.
  module_enable(array($module), FALSE);
  $context['results'][] = $module;
  $context['message'] = st('Installed %module module.', array('%module' => $module_name));
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
function install_check_requirements($install_state) {
  $profile = $install_state['parameters']['profile'];

  // Check the profile requirements.
  $requirements = drupal_check_profile($profile);

  // If Drupal is not set up already, we need to create a settings file.
  if (!$install_state['settings_verified']) {
    $writable = FALSE;
    $conf_path = './' . conf_path(FALSE, TRUE);
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
        $writable = drupal_verify_install_file($settings_file, FILE_READABLE|FILE_WRITABLE);
        $exists = TRUE;
      }
    }

    // If default.settings.php does not exist, or is not readable, throw an
    // error.
    if (!drupal_verify_install_file($default_settings_file, FILE_EXIST|FILE_READABLE)) {
      $requirements['default settings file exists'] = array(
        'title'       => st('Default settings file'),
        'value'       => st('The default settings file does not exist.'),
        'severity'    => REQUIREMENT_ERROR,
        'description' => st('The @drupal installer requires that the %default-file file not be modified in any way from the original download.', array('@drupal' => drupal_install_profile_distribution_name(), '%default-file' => $default_settings_file)),
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
          $writable = drupal_verify_install_file($settings_file, FILE_READABLE|FILE_WRITABLE);
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
            $writable = drupal_verify_install_file($settings_file, FILE_READABLE|FILE_WRITABLE);
          }
        }
      }
    }

    // If settings.php does not exist, throw an error.
    if (!$exists) {
      $requirements['settings file exists'] = array(
        'title'       => st('Settings file'),
        'value'       => st('The settings file does not exist.'),
        'severity'    => REQUIREMENT_ERROR,
        'description' => st('The @drupal installer requires that you create a settings file as part of the installation process. Copy the %default_file file to %file. More details about installing Drupal are available in <a href="@install_txt">INSTALL.txt</a>.', array('@drupal' => drupal_install_profile_distribution_name(), '%file' => $file, '%default_file' => $default_settings_file, '@install_txt' => base_path() . 'INSTALL.txt')),
      );
    }
    else {
      $requirements['settings file exists'] = array(
        'title'       => st('Settings file'),
        'value'       => st('The %file file exists.', array('%file' => $file)),
      );
      // If settings.php is not writable, throw an error.
      if (!$writable) {
        $requirements['settings file writable'] = array(
          'title'       => st('Settings file'),
          'value'       => st('The settings file is not writable.'),
          'severity'    => REQUIREMENT_ERROR,
          'description' => st('The @drupal installer requires write permissions to %file during the installation process. If you are unsure how to grant file permissions, consult the <a href="@handbook_url">online handbook</a>.', array('@drupal' => drupal_install_profile_distribution_name(), '%file' => $file, '@handbook_url' => 'http://drupal.org/server-permissions')),
        );
      }
      else {
        $requirements['settings file'] = array(
          'title'       => st('Settings file'),
          'value'       => st('The settings file is writable.'),
        );
      }
      if (!empty($settings_file_ownership_error)) {
        $requirements['settings file ownership'] = array(
          'title'       => st('Settings file'),
          'value'       => st('The settings file is owned by the web server.'),
          'severity'    => REQUIREMENT_ERROR,
          'description' => st('The @drupal installer failed to create a settings file with proper file ownership. Log on to your web server, remove the existing %file file, and create a new one by copying the %default_file file to %file. More details about installing Drupal are available in <a href="@install_txt">INSTALL.txt</a>. If you have problems with the file permissions on your server, consult the <a href="@handbook_url">online handbook</a>.', array('@drupal' => drupal_install_profile_distribution_name(), '%file' => $file, '%default_file' => $default_settings_file, '@install_txt' => base_path() . 'INSTALL.txt', '@handbook_url' => 'http://drupal.org/server-permissions')),
        );
      }
    }
  }
  return $requirements;
}

/**
 * Forms API array definition for site configuration.
 */
function _install_configure_form($form, &$form_state, &$install_state) {
  include_once DRUPAL_ROOT . '/includes/locale.inc';

  $form['site_information'] = array(
    '#type' => 'fieldset',
    '#title' => st('Site information'),
    '#collapsible' => FALSE,
  );
  $form['site_information']['site_name'] = array(
    '#type' => 'textfield',
    '#title' => st('Site name'),
    '#required' => TRUE,
    '#weight' => -20,
  );
  $form['site_information']['site_mail'] = array(
    '#type' => 'textfield',
    '#title' => st('Site e-mail address'),
    '#default_value' => ini_get('sendmail_from'),
    '#description' => st("Automated e-mails, such as registration information, will be sent from this address. Use an address ending in your site's domain to help prevent these e-mails from being flagged as spam."),
    '#required' => TRUE,
    '#weight' => -15,
  );
  $form['admin_account'] = array(
    '#type' => 'fieldset',
    '#title' => st('Site maintenance account'),
    '#collapsible' => FALSE,
  );

  $form['admin_account']['account']['#tree'] = TRUE;
  $form['admin_account']['account']['name'] = array('#type' => 'textfield',
    '#title' => st('Username'),
    '#maxlength' => USERNAME_MAX_LENGTH,
    '#description' => st('Spaces are allowed; punctuation is not allowed except for periods, hyphens, and underscores.'),
    '#required' => TRUE,
    '#weight' => -10,
    '#attributes' => array('class' => array('username')),
  );

  $form['admin_account']['account']['mail'] = array('#type' => 'textfield',
    '#title' => st('E-mail address'),
    '#maxlength' => EMAIL_MAX_LENGTH,
    '#required' => TRUE,
    '#weight' => -5,
  );
  $form['admin_account']['account']['pass'] = array(
    '#type' => 'password_confirm',
    '#required' => TRUE,
    '#size' => 25,
    '#weight' => 0,
  );

  $form['server_settings'] = array(
    '#type' => 'fieldset',
    '#title' => st('Server settings'),
    '#collapsible' => FALSE,
  );

  $countries = country_get_list();
  $form['server_settings']['site_default_country'] = array(
    '#type' => 'select',
    '#title' => st('Default country'),
    '#empty_value' => '',
    '#default_value' => variable_get('site_default_country', NULL),
    '#options' => $countries,
    '#description' => st('Select the default country for the site.'),
    '#weight' => 0,
  );

  $form['server_settings']['date_default_timezone'] = array(
    '#type' => 'select',
    '#title' => st('Default time zone'),
    '#default_value' => date_default_timezone_get(),
    '#options' => system_time_zones(),
    '#description' => st('By default, dates in this site will be displayed in the chosen time zone.'),
    '#weight' => 5,
    '#attributes' => array('class' => array('timezone-detect')),
  );

  $form['server_settings']['clean_url'] = array(
    '#type' => 'hidden',
    '#default_value' => 0,
    '#attributes' => array('id' => 'edit-clean-url', 'class' => array('install')),
  );

  $form['update_notifications'] = array(
    '#type' => 'fieldset',
    '#title' => st('Update notifications'),
    '#collapsible' => FALSE,
  );
  $form['update_notifications']['update_status_module'] = array(
    '#type' => 'checkboxes',
    '#options' => array(
      1 => st('Check for updates automatically'),
      2 => st('Receive e-mail notifications'),
    ),
    '#default_value' => array(1, 2),
    '#description' => st('The system will notify you when updates and important security releases are available for installed components. Anonymous information about your site is sent to <a href="@drupal">Drupal.org</a>.', array('@drupal' => 'http://drupal.org')),
    '#weight' => 15,
  );

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => st('Save and continue'),
    '#weight' => 15,
  );

  return $form;
}

/**
 * Forms API validate for the site configuration form.
 */
function install_configure_form_validate($form, &$form_state) {
  if ($error = user_validate_name($form_state['values']['account']['name'])) {
    form_error($form['admin_account']['account']['name'], $error);
  }
  if ($error = user_validate_mail($form_state['values']['account']['mail'])) {
    form_error($form['admin_account']['account']['mail'], $error);
  }
  if ($error = user_validate_mail($form_state['values']['site_mail'])) {
    form_error($form['site_information']['site_mail'], $error);
  }
}

/**
 * Forms API submit for the site configuration form.
 */
function install_configure_form_submit($form, &$form_state) {
  global $user;

  variable_set('site_name', $form_state['values']['site_name']);
  variable_set('site_mail', $form_state['values']['site_mail']);
  variable_set('date_default_timezone', $form_state['values']['date_default_timezone']);
  variable_set('site_default_country', $form_state['values']['site_default_country']);

  // Enable update.module if this option was selected.
  if ($form_state['values']['update_status_module'][1]) {
    module_enable(array('update'), FALSE);

    // Add the site maintenance account's email address to the list of
    // addresses to be notified when updates are available, if selected.
    if ($form_state['values']['update_status_module'][2]) {
      variable_set('update_notify_emails', array($form_state['values']['account']['mail']));
    }
  }

  // We precreated user 1 with placeholder values. Let's save the real values.
  $account = user_load(1);
  $merge_data = array('init' => $form_state['values']['account']['mail'], 'roles' => !empty($account->roles) ? $account->roles : array(), 'status' => 1);
  user_save($account, array_merge($form_state['values']['account'], $merge_data));
  // Load global $user and perform final login tasks.
  $user = user_load(1);
  user_login_finalize();

  if (isset($form_state['values']['clean_url'])) {
    variable_set('clean_url', $form_state['values']['clean_url']);
  }

  // Record when this install ran.
  variable_set('install_time', $_SERVER['REQUEST_TIME']);
}
