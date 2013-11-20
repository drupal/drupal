<?php

require_once './includes/install.inc';

define('MAINTENANCE_MODE', 'install');

/**
 * The Drupal installation happens in a series of steps. We begin by verifying
 * that the current environment meets our minimum requirements. We then go
 * on to verify that settings.php is properly configured. From there we
 * connect to the configured database and verify that it meets our minimum
 * requirements. Finally we can allow the user to select an installation
 * profile and complete the installation process.
 *
 * @param $phase
 *   The installation phase we should proceed to.
 */
function install_main() {
  require_once './includes/bootstrap.inc';
  drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION);

  // This must go after drupal_bootstrap(), which unsets globals!
  global $profile, $install_locale, $conf;

  require_once './modules/system/system.install';
  require_once './includes/file.inc';

  // Ensure correct page headers are sent (e.g. caching)
  drupal_page_header();

  // Set up $language, so t() caller functions will still work.
  drupal_init_language();

  // Load module basics (needed for hook invokes).
  include_once './includes/module.inc';
  $module_list['system']['filename'] = 'modules/system/system.module';
  $module_list['filter']['filename'] = 'modules/filter/filter.module';
  module_list(TRUE, FALSE, FALSE, $module_list);
  drupal_load('module', 'system');
  drupal_load('module', 'filter');

  // Install profile chosen, set the global immediately.
  // This needs to be done before the theme cache gets 
  // initialized in drupal_maintenance_theme().
  if (!empty($_GET['profile'])) {
    $profile = preg_replace('/[^a-zA-Z_0-9]/', '', $_GET['profile']);
  }

  // Set up theme system for the maintenance page.
  drupal_maintenance_theme();

  // Check existing settings.php.
  $verify = install_verify_settings();

  if ($verify) {
    // Since we have a database connection, we use the normal cache system.
    // This is important, as the installer calls into the Drupal system for
    // the clean URL checks, so we should maintain the cache properly.
    require_once './includes/cache.inc';
    $conf['cache_inc'] = './includes/cache.inc';

    // Establish a connection to the database.
    require_once './includes/database.inc';
    db_set_active();

    // Check if Drupal is installed.
    $task = install_verify_drupal();
    if ($task == 'done') {
      install_already_done_error();
    }
  }
  else {
    // Since no persistent storage is available yet, and functions that check
    // for cached data will fail, we temporarily replace the normal cache
    // system with a stubbed-out version that short-circuits the actual
    // caching process and avoids any errors.
    require_once './includes/cache-install.inc';
    $conf['cache_inc'] = './includes/cache-install.inc';

    $task = NULL;
  }

  // No profile was passed in GET, ask the user.
  if (empty($_GET['profile'])) {
    if ($profile = install_select_profile()) {
      install_goto("install.php?profile=$profile");
    }
    else {
      install_no_profile_error();
    }
  }

  // Load the profile.
  require_once "./profiles/$profile/$profile.profile";

  // Locale selection
  if (!empty($_GET['locale'])) {
    $install_locale = preg_replace('/[^a-zA-Z_0-9\-]/', '', $_GET['locale']);
  }
  elseif (($install_locale = install_select_locale($profile)) !== FALSE) {
    install_goto("install.php?profile=$profile&locale=$install_locale");
  }

  // Tasks come after the database is set up
  if (!$task) {
    global $db_url;

    if (!$verify && !empty($db_url)) {
      // Do not install over a configured settings.php.
      install_already_done_error();
    }

    // Check the installation requirements for Drupal and this profile.
    install_check_requirements($profile, $verify);

    // Verify existence of all required modules.
    $modules = drupal_verify_profile($profile, $install_locale);

    // If any error messages are set now, it means a requirement problem.
    $messages = drupal_set_message();
    if (!empty($messages['error'])) {
      install_task_list('requirements');
      drupal_set_title(st('Requirements problem'));
      print theme('install_page', '');
      exit;
    }

    // Change the settings.php information if verification failed earlier.
    // Note: will trigger a redirect if database credentials change.
    if (!$verify) {
      install_change_settings($profile, $install_locale);
    }
    // The default lock implementation uses a database table,
    // so we cannot use it for install, but we still need
    // the API functions available.
    require_once './includes/lock-install.inc';
    $conf['lock_inc'] = './includes/lock-install.inc';
    lock_init();

    // Install system.module.
    drupal_install_system();

    // Ensure that all of Drupal's standard directories have appropriate
    // .htaccess files. These directories will have already been created by
    // this point in the installer, since Drupal creates them during the
    // install_check_requirements() task. Note that we cannot create them any
    // earlier than this, since the code below relies on system.module in order
    // to work.
    file_create_htaccess(file_directory_path());
    file_create_htaccess(file_directory_temp());

    // Save the list of other modules to install for the 'profile-install'
    // task. variable_set() can be used now that system.module is installed
    // and drupal is bootstrapped.
    variable_set('install_profile_modules', array_diff($modules, array('system')));
  }

  // The database is set up, turn to further tasks.
  install_tasks($profile, $task);
}

/**
 * Verify if Drupal is installed.
 */
function install_verify_drupal() {
  // Read the variable manually using the @ so we don't trigger an error if it fails.
  $result = @db_query("SELECT value FROM {variable} WHERE name = '%s'", 'install_task');
  if ($result) {
    return unserialize(db_result($result));
  }
}

/**
 * Verify existing settings.php
 */
function install_verify_settings() {
  global $db_prefix, $db_type, $db_url;

  // Verify existing settings (if any).
  if (!empty($db_url)) {
    // We need this because we want to run form_get_errors.
    include_once './includes/form.inc';

    $url = parse_url(is_array($db_url) ? $db_url['default'] : $db_url);
    $db_user = urldecode($url['user']);
    $db_pass = isset($url['pass']) ? urldecode($url['pass']) : NULL;
    $db_host = urldecode($url['host']);
    $db_port = isset($url['port']) ? urldecode($url['port']) : '';
    $db_path = ltrim(urldecode($url['path']), '/');
    $settings_file = './'. conf_path(FALSE, TRUE) .'/settings.php';

    $form_state = array();
    _install_settings_form_validate($db_prefix, $db_type, $db_user, $db_pass, $db_host, $db_port, $db_path, $settings_file, $form_state);
    if (!form_get_errors()) {
      return TRUE;
    }
  }
  return FALSE;
}

/**
 * Configure and rewrite settings.php.
 */
function install_change_settings($profile = 'default', $install_locale = '') {
  global $db_url, $db_type, $db_prefix;

  $url = parse_url(is_array($db_url) ? $db_url['default'] : $db_url);
  $db_user = isset($url['user']) ? urldecode($url['user']) : '';
  $db_pass = isset($url['pass']) ? urldecode($url['pass']) : '';
  $db_host = isset($url['host']) ? urldecode($url['host']) : '';
  $db_port = isset($url['port']) ? urldecode($url['port']) : '';
  $db_path = ltrim(urldecode($url['path']), '/');
  $conf_path = './'. conf_path(FALSE, TRUE);
  $settings_file = $conf_path .'/settings.php';

  // We always need this because we want to run form_get_errors.
  include_once './includes/form.inc';
  install_task_list('database');

  $output = drupal_get_form('install_settings_form', $profile, $install_locale, $settings_file, $db_url, $db_type, $db_prefix, $db_user, $db_pass, $db_host, $db_port, $db_path);
  drupal_set_title(st('Database configuration'));
  print theme('install_page', $output);
  exit;
}


/**
 * Form API array definition for install_settings.
 */
function install_settings_form(&$form_state, $profile, $install_locale, $settings_file, $db_url, $db_type, $db_prefix, $db_user, $db_pass, $db_host, $db_port, $db_path) {
  if (empty($db_host)) {
    $db_host = 'localhost';
  }
  $db_types = drupal_detect_database_types();

  // If both 'mysql' and 'mysqli' are available, we disable 'mysql':
  if (isset($db_types['mysqli'])) {
    unset($db_types['mysql']);
  }

  if (count($db_types) == 0) {
    $form['no_db_types'] = array(
      '#value' => st('Your web server does not appear to support any common database types. Check with your hosting provider to see if they offer any databases that <a href="@drupal-databases">Drupal supports</a>.', array('@drupal-databases' => 'http://drupal.org/node/270#database')),
    );
  }
  else {
    $form['basic_options'] = array(
      '#type' => 'fieldset',
      '#title' => st('Basic options'),
      '#description' => '<p>'. st('To set up your @drupal database, enter the following information.', array('@drupal' => drupal_install_profile_name())) .'</p>',
    );

    if (count($db_types) > 1) {
      $form['basic_options']['db_type'] = array(
        '#type' => 'radios',
        '#title' => st('Database type'),
        '#required' => TRUE,
        '#options' => $db_types,
        '#default_value' => ($db_type ? $db_type : current($db_types)),
        '#description' => st('The type of database your @drupal data will be stored in.', array('@drupal' => drupal_install_profile_name())),
      );
      $db_path_description = st('The name of the database your @drupal data will be stored in. It must exist on your server before @drupal can be installed.', array('@drupal' => drupal_install_profile_name()));
    }
    else {
      if (count($db_types) == 1) {
        $db_types = array_values($db_types);
        $form['basic_options']['db_type'] = array(
          '#type' => 'hidden',
          '#value' => $db_types[0],
        );
        $db_path_description = st('The name of the %db_type database your @drupal data will be stored in. It must exist on your server before @drupal can be installed.', array('%db_type' => $db_types[0], '@drupal' => drupal_install_profile_name()));
      }
    }

    // Database name
    $form['basic_options']['db_path'] = array(
      '#type' => 'textfield',
      '#title' => st('Database name'),
      '#default_value' => $db_path,
      '#size' => 45,
      '#required' => TRUE,
      '#description' => $db_path_description
    );

    // Database username
    $form['basic_options']['db_user'] = array(
      '#type' => 'textfield',
      '#title' => st('Database username'),
      '#default_value' => $db_user,
      '#size' => 45,
      '#required' => TRUE,
    );

    // Database username
    $form['basic_options']['db_pass'] = array(
      '#type' => 'password',
      '#title' => st('Database password'),
      '#default_value' => $db_pass,
      '#size' => 45,
    );

    $form['advanced_options'] = array(
      '#type' => 'fieldset',
      '#title' => st('Advanced options'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => st("These options are only necessary for some sites. If you're not sure what you should enter here, leave the default settings or check with your hosting provider.")
    );

    // Database host
    $form['advanced_options']['db_host'] = array(
      '#type' => 'textfield',
      '#title' => st('Database host'),
      '#default_value' => $db_host,
      '#size' => 45,
      // Hostnames can be 255 characters long.
      '#maxlength' => 255,
      '#required' => TRUE,
      '#description' => st('If your database is located on a different server, change this.'),
    );

    // Database port
    $form['advanced_options']['db_port'] = array(
      '#type' => 'textfield',
      '#title' => st('Database port'),
      '#default_value' => $db_port,
      '#size' => 45,
      // The maximum port number is 65536, 5 digits.
      '#maxlength' => 5,
      '#description' => st('If your database server is listening to a non-standard port, enter its number.'),
    );

    // Table prefix
    $prefix = ($profile == 'default') ? 'drupal_' : $profile .'_';
    $form['advanced_options']['db_prefix'] = array(
      '#type' => 'textfield',
      '#title' => st('Table prefix'),
      '#default_value' => $db_prefix,
      '#size' => 45,
      '#description' => st('If more than one application will be sharing this database, enter a table prefix such as %prefix for your @drupal site here.', array('@drupal' => drupal_install_profile_name(), '%prefix' => $prefix)),
    );

    $form['save'] = array(
      '#type' => 'submit',
      '#value' => st('Save and continue'),
    );

    $form['errors'] = array();
    $form['settings_file'] = array('#type' => 'value', '#value' => $settings_file);
    $form['_db_url'] = array('#type' => 'value');
    $form['#action'] = "install.php?profile=$profile". ($install_locale ? "&locale=$install_locale" : '');
    $form['#redirect'] = FALSE;
  }
  return $form;
}

/**
 * Form API validate for install_settings form.
 */
function install_settings_form_validate($form, &$form_state) {
  global $db_url;
  _install_settings_form_validate($form_state['values']['db_prefix'], $form_state['values']['db_type'], $form_state['values']['db_user'], $form_state['values']['db_pass'], $form_state['values']['db_host'], $form_state['values']['db_port'], $form_state['values']['db_path'], $form_state['values']['settings_file'], $form_state, $form);
}

/**
 * Helper function for install_settings_validate.
 */
function _install_settings_form_validate($db_prefix, $db_type, $db_user, $db_pass, $db_host, $db_port, $db_path, $settings_file, &$form_state, $form = NULL) {
  global $db_url;

  // Verify the table prefix
  if (!empty($db_prefix) && is_string($db_prefix) && !preg_match('/^[A-Za-z0-9_.]+$/', $db_prefix)) {
    form_set_error('db_prefix', st('The database table prefix you have entered, %db_prefix, is invalid. The table prefix can only contain alphanumeric characters, periods, or underscores.', array('%db_prefix' => $db_prefix)), 'error');
  }

  if (!empty($db_port) && !is_numeric($db_port)) {
    form_set_error('db_port', st('Database port must be a number.'));
  }

  // Check database type
  if (!isset($form)) {
    $_db_url = is_array($db_url) ? $db_url['default'] : $db_url;
    $db_type = substr($_db_url, 0, strpos($_db_url, '://'));
  }
  $databases = drupal_detect_database_types();
  if (!in_array($db_type, $databases)) {
    form_set_error('db_type', st("In your %settings_file file you have configured @drupal to use a %db_type server, however your PHP installation currently does not support this database type.", array('%settings_file' => $settings_file, '@drupal' => drupal_install_profile_name(), '%db_type' => $db_type)));
  }
  else {
    // Verify
    $db_url = $db_type .'://'. urlencode($db_user) . ($db_pass ? ':'. urlencode($db_pass) : '') .'@'. ($db_host ? urlencode($db_host) : 'localhost') . ($db_port ? ":$db_port" : '') .'/'. urlencode($db_path);
    if (isset($form)) {
      form_set_value($form['_db_url'], $db_url, $form_state);
    }
    $success = array();

    $function = 'drupal_test_'. $db_type;
    if (!$function($db_url, $success)) {
      if (isset($success['CONNECT'])) {
        form_set_error('db_type', st('In order for Drupal to work, and to continue with the installation process, you must resolve all permission issues reported above. We were able to verify that we have permission for the following commands: %commands. For more help with configuring your database server, see the <a href="http://drupal.org/node/258">Installation and upgrading handbook</a>. If you are unsure what any of this means you should probably contact your hosting provider.', array('%commands' => implode($success, ', '))));
      }
      else {
        form_set_error('db_type', '');
      }
    }
  }
}

/**
 * Form API submit for install_settings form.
 */
function install_settings_form_submit($form, &$form_state) {
  global $profile, $install_locale;

  // Update global settings array and save
  $settings['db_url'] = array(
    'value'    => $form_state['values']['_db_url'],
    'required' => TRUE,
  );
  $settings['db_prefix'] = array(
    'value'    => $form_state['values']['db_prefix'],
    'required' => TRUE,
  );
  drupal_rewrite_settings($settings);

  // Continue to install profile step
  install_goto("install.php?profile=$profile". ($install_locale ? "&locale=$install_locale" : ''));
}

/**
 * Find all .profile files.
 */
function install_find_profiles() {
  return file_scan_directory('./profiles', '\.profile$', array('.', '..', 'CVS'), 0, TRUE, 'name', 0);
}

/**
 * Allow admin to select which profile to install.
 *
 * @return
 *   The selected profile.
 */
function install_select_profile() {
  include_once './includes/form.inc';

  $profiles = install_find_profiles();
  // Don't need to choose profile if only one available.
  if (sizeof($profiles) == 1) {
    $profile = array_pop($profiles);
    require_once $profile->filename;
    return $profile->name;
  }
  elseif (sizeof($profiles) > 1) {
    foreach ($profiles as $profile) {
      if (!empty($_POST['profile']) && ($_POST['profile'] == $profile->name)) {
        return $profile->name;
      }
    }

    install_task_list('profile-select');

    drupal_set_title(st('Select an installation profile'));
    print theme('install_page', drupal_get_form('install_select_profile_form', $profiles));
    exit;
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
function install_select_profile_form(&$form_state, $profile_files) {
  $profiles = array();
  $names = array();

  foreach ($profile_files as $profile) {
    include_once($profile->filename);

    // Load profile details and store them for later retrieval.
    $function = $profile->name .'_profile_details';
    if (function_exists($function)) {
      $details = $function();
    }
    $profiles[$profile->name] = $details;

    // Determine the name of the profile; default to file name if defined name
    // is unspecified.
    $name = isset($details['name']) ? $details['name'] : $profile->name;
    $names[$profile->name] = $name;
  }

  // Display radio buttons alphabetically by human-readable name. 
  natcasesort($names);
  foreach ($names as $profile => $name) {
    $form['profile'][$name] = array(
      '#type' => 'radio',
      '#value' => 'default',
      '#return_value' => $profile,
      '#title' => $name,
      '#description' => isset($profiles[$profile]['description']) ? $profiles[$profile]['description'] : '',
      '#parents' => array('profile'),
    );
  }
  $form['submit'] =  array(
    '#type' => 'submit',
    '#value' => st('Save and continue'),
  );
  return $form;
}

/**
 * Find all .po files for the current profile.
 */
function install_find_locales($profilename) {
  $locales = file_scan_directory('./profiles/'. $profilename .'/translations', '\.po$', array('.', '..', 'CVS'), 0, FALSE);
  array_unshift($locales, (object) array('name' => 'en'));
  return $locales;
}

/**
 * Allow admin to select which locale to use for the current profile.
 *
 * @return
 *   The selected language.
 */
function install_select_locale($profilename) {
  include_once './includes/file.inc';
  include_once './includes/form.inc';

  // Find all available locales.
  $locales = install_find_locales($profilename);

  // If only the built-in (English) language is available,
  // and we are using the default profile, inform the user
  // that the installer can be localized. Otherwise we assume
  // the user know what he is doing.
  if (count($locales) == 1) {
    if ($profilename == 'default') {
      install_task_list('locale-select');
      drupal_set_title(st('Choose language'));
      if (!empty($_GET['localize'])) {
        $output = '<p>'. st('With the addition of an appropriate translation package, this installer is capable of proceeding in another language of your choice. To install and use Drupal in a language other than English:') .'</p>';
        $output .= '<ul><li>'. st('Determine if <a href="@translations" target="_blank">a translation of this Drupal version</a> is available in your language of choice. A translation is provided via a translation package; each translation package enables the display of a specific version of Drupal in a specific language. Not all languages are available for every version of Drupal.', array('@translations' => 'http://localize.drupal.org')) .'</li>';
        $output .= '<li>'. st('If an alternative translation package of your choice is available, download and extract its contents to your Drupal root directory.') .'</li>';
        $output .= '<li>'. st('Return to choose language using the second link below and select your desired language from the displayed list. Reloading the page allows the list to automatically adjust to the presence of new translation packages.') .'</li>';
        $output .= '</ul><p>'. st('Alternatively, to install and use Drupal in English, or to defer the selection of an alternative language until after installation, select the first link below.') .'</p>';
        $output .= '<p>'. st('How should the installation continue?') .'</p>';
        $output .= '<ul><li><a href="install.php?profile='. $profilename .'&amp;locale=en">'. st('Continue installation in English') .'</a></li><li><a href="install.php?profile='. $profilename .'">'. st('Return to choose a language') .'</a></li></ul>';
      }
      else {
        $output = '<ul><li><a href="install.php?profile='. $profilename .'&amp;locale=en">'. st('Install Drupal in English') .'</a></li><li><a href="install.php?profile='. $profilename .'&amp;localize=true">'. st('Learn how to install Drupal in other languages') .'</a></li></ul>';
      }
      print theme('install_page', $output);
      exit;
    }
    // One language, but not the default profile, assume
    // the user knows what he is doing.
    return FALSE;
  }
  else {
    // Allow profile to pre-select the language, skipping the selection.
    $function = $profilename .'_profile_details';
    if (function_exists($function)) {
      $details = $function();
      if (isset($details['language'])) {
        foreach ($locales as $locale) {
          if ($details['language'] == $locale->name) {
            return $locale->name;
          }
        }
      }
    }

    if (!empty($_POST['locale'])) {
      foreach ($locales as $locale) {
        if ($_POST['locale'] == $locale->name) {
          return $locale->name;
        }
      }
    }

    install_task_list('locale-select');

    drupal_set_title(st('Choose language'));
    print theme('install_page', drupal_get_form('install_select_locale_form', $locales));
    exit;
  }
}

/**
 * Form API array definition for language selection.
 */
function install_select_locale_form(&$form_state, $locales) {
  include_once './includes/locale.inc';
  $languages = _locale_get_predefined_list();
  foreach ($locales as $locale) {
    // Try to use verbose locale name
    $name = $locale->name;
    if (isset($languages[$name])) {
      $name = $languages[$name][0] . (isset($languages[$name][1]) ? ' '. st('(@language)', array('@language' => $languages[$name][1])) : '');
    }
    $form['locale'][$locale->name] = array(
      '#type' => 'radio',
      '#return_value' => $locale->name,
      '#default_value' => ($locale->name == 'en' ? TRUE : FALSE),
      '#title' => $name . ($locale->name == 'en' ? ' '. st('(built-in)') : ''),
      '#parents' => array('locale')
    );
  }
  $form['submit'] =  array(
    '#type' => 'submit',
    '#value' => st('Select language'),
  );
  return $form;
}

/**
 * Show an error page when there are no profiles available.
 */
function install_no_profile_error() {
  install_task_list('profile-select');
  drupal_set_title(st('No profiles available'));
  print theme('install_page', '<p>'. st('We were unable to find any installer profiles. Installer profiles tell us what modules to enable and what schema to install in the database. A profile is necessary to continue with the installation process.') .'</p>');
  exit;
}


/**
 * Show an error page when Drupal has already been installed.
 */
function install_already_done_error() {
  global $base_url;

  drupal_set_title(st('Drupal already installed'));
  print theme('install_page', st('<ul><li>To start over, you must empty your existing database.</li><li>To install to a different database, edit the appropriate <em>settings.php</em> file in the <em>sites</em> folder.</li><li>To upgrade an existing installation, proceed to the <a href="@base-url/update.php">update script</a>.</li><li>View your <a href="@base-url">existing site</a>.</li></ul>', array('@base-url' => $base_url)));
  exit;
}

/**
 * Tasks performed after the database is initialized.
 */
function install_tasks($profile, $task) {
  global $base_url, $install_locale;

  // Bootstrap newly installed Drupal, while preserving existing messages.
  $messages = isset($_SESSION['messages']) ? $_SESSION['messages'] : '';
  drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
  $_SESSION['messages'] = $messages;

  // URL used to direct page requests.
  $url = $base_url .'/install.php?locale='. $install_locale .'&profile='. $profile;

  // Build a page for final tasks.
  if (empty($task)) {
    variable_set('install_task', 'profile-install');
    $task = 'profile-install';
  }

  // We are using a list of if constructs here to allow for
  // passing from one task to the other in the same request.

  // Install profile modules.
  if ($task == 'profile-install') {
    $modules = variable_get('install_profile_modules', array());
    $files = module_rebuild_cache();
    variable_del('install_profile_modules');
    $operations = array();
    foreach ($modules as $module) {
      $operations[] = array('_install_module_batch', array($module, $files[$module]->info['name']));
    }
    $batch = array(
      'operations' => $operations,
      'finished' => '_install_profile_batch_finished',
      'title' => st('Installing @drupal', array('@drupal' => drupal_install_profile_name())),
      'error_message' => st('The installation has encountered an error.'),
    );
    // Start a batch, switch to 'profile-install-batch' task. We need to
    // set the variable here, because batch_process() redirects.
    variable_set('install_task', 'profile-install-batch');
    batch_set($batch);
    batch_process($url, $url);
  }
  // We are running a batch install of the profile's modules.
  // This might run in multiple HTTP requests, constantly redirecting
  // to the same address, until the batch finished callback is invoked
  // and the task advances to 'locale-initial-import'.
  if ($task == 'profile-install-batch') {
    include_once 'includes/batch.inc';
    $output = _batch_page();
  }

  // Import interface translations for the enabled modules.
  if ($task == 'locale-initial-import') {
    if (!empty($install_locale) && ($install_locale != 'en')) {
      include_once 'includes/locale.inc';
      // Enable installation language as default site language.
      locale_add_language($install_locale, NULL, NULL, NULL, NULL, NULL, 1, TRUE);
      // Collect files to import for this language.
      $batch = locale_batch_by_language($install_locale, '_install_locale_initial_batch_finished');
      if (!empty($batch)) {
        // Remember components we cover in this batch set.
        variable_set('install_locale_batch_components', $batch['#components']);
        // Start a batch, switch to 'locale-batch' task. We need to
        // set the variable here, because batch_process() redirects.
        variable_set('install_task', 'locale-initial-batch');
        batch_set($batch);
        batch_process($url, $url);
      }
    }
    // Found nothing to import or not foreign language, go to next task.
    $task = 'configure';
  }
  if ($task == 'locale-initial-batch') {
    include_once 'includes/batch.inc';
    include_once 'includes/locale.inc';
    $output = _batch_page();
  }

  if ($task == 'configure') {
    if (variable_get('site_name', FALSE) || variable_get('site_mail', FALSE)) {
      // Site already configured: This should never happen, means re-running
      // the installer, possibly by an attacker after the 'install_task' variable
      // got accidentally blown somewhere. Stop it now.
      install_already_done_error();
    }
    $form = drupal_get_form('install_configure_form', $url);

    if (!variable_get('site_name', FALSE) && !variable_get('site_mail', FALSE)) {
      // Not submitted yet: Prepare to display the form.
      $output = $form;
      drupal_set_title(st('Configure site'));

      // Warn about settings.php permissions risk
      $settings_dir = './'. conf_path();
      $settings_file = $settings_dir .'/settings.php';
      if (!drupal_verify_install_file($settings_file, FILE_EXIST|FILE_READABLE|FILE_NOT_WRITABLE) || !drupal_verify_install_file($settings_dir, FILE_NOT_WRITABLE, 'dir')) {
        drupal_set_message(st('All necessary changes to %dir and %file have been made, so you should remove write permissions to them now in order to avoid security risks. If you are unsure how to do so, please consult the <a href="@handbook_url">on-line handbook</a>.', array('%dir' => $settings_dir, '%file' => $settings_file, '@handbook_url' => 'http://drupal.org/getting-started')), 'error');
      }
      else {
        drupal_set_message(st('All necessary changes to %dir and %file have been made. They have been set to read-only for security.', array('%dir' => $settings_dir, '%file' => $settings_file)));
      }

      // Add JavaScript validation.
      _user_password_dynamic_validation();
      drupal_add_js(drupal_get_path('module', 'system') .'/system.js', 'module');
      // We add these strings as settings because JavaScript translation does not
      // work on install time.
      drupal_add_js(array('copyFieldValue' => array('edit-site-mail' => array('edit-account-mail')), 'cleanURL' => array('success' => st('Your server has been successfully tested to support this feature.'), 'failure' => st('Your system configuration does not currently support this feature. The <a href="http://drupal.org/node/15365">handbook page on Clean URLs</a> has additional troubleshooting information.'), 'testing' => st('Testing clean URLs...'))), 'setting');
      drupal_add_js('
// Global Killswitch
if (Drupal.jsEnabled) {
  $(document).ready(function() {
    Drupal.cleanURLsInstallCheck();
    Drupal.setDefaultTimezone();
  });
}', 'inline');
      // Build menu to allow clean URL check.
      menu_rebuild();
    }

    else {
      $task = 'profile';
    }
  }

  // If found an unknown task or the 'profile' task, which is
  // reserved for profiles, hand over the control to the profile,
  // so it can run any number of custom tasks it defines.
  if (!in_array($task, install_reserved_tasks())) {
    $function = $profile .'_profile_tasks';
    if (function_exists($function)) {
      // The profile needs to run more code, maybe even more tasks.
      // $task is sent through as a reference and may be changed!
      $output = $function($task, $url);
    }

    // If the profile doesn't move on to a new task we assume
    // that it is done.
    if ($task == 'profile') {
      $task = 'profile-finished';
    }
  }

  // Profile custom tasks are done, so let the installer regain
  // control and proceed with importing the remaining translations.
  if ($task == 'profile-finished') {
    if (!empty($install_locale) && ($install_locale != 'en')) {
      include_once 'includes/locale.inc';
      // Collect files to import for this language. Skip components
      // already covered in the initial batch set.
      $batch = locale_batch_by_language($install_locale, '_install_locale_remaining_batch_finished', variable_get('install_locale_batch_components', array()));
      // Remove temporary variable.
      variable_del('install_locale_batch_components');
      if (!empty($batch)) {
        // Start a batch, switch to 'locale-remaining-batch' task. We need to
        // set the variable here, because batch_process() redirects.
        variable_set('install_task', 'locale-remaining-batch');
        batch_set($batch);
        batch_process($url, $url);
      }
    }
    // Found nothing to import or not foreign language, go to next task.
    $task = 'finished';
  }
  if ($task == 'locale-remaining-batch') {
    include_once 'includes/batch.inc';
    include_once 'includes/locale.inc';
    $output = _batch_page();
  }

  // Display a 'finished' page to user.
  if ($task == 'finished') {
    drupal_set_title(st('@drupal installation complete', array('@drupal' => drupal_install_profile_name())));
    $messages = drupal_set_message();
    $output = '<p>'. st('Congratulations, @drupal has been successfully installed.', array('@drupal' => drupal_install_profile_name())) .'</p>';
    $output .= '<p>'. (isset($messages['error']) ? st('Please review the messages above before continuing on to <a href="@url">your new site</a>.', array('@url' => url(''))) : st('You may now visit <a href="@url">your new site</a>.', array('@url' => url('')))) .'</p>';
    $task = 'done';
  }

  // The end of the install process. Remember profile used.
  if ($task == 'done') {
    // Rebuild menu to get content type links registered by the profile,
    // and possibly any other menu items created through the tasks.
    menu_rebuild();

    // Register actions declared by any modules.
    actions_synchronize();

    // Randomize query-strings on css/js files, to hide the fact that
    // this is a new install, not upgraded yet.
    _drupal_flush_css_js();

    variable_set('install_profile', $profile);
  }

  // Set task for user, and remember the task in the database.
  install_task_list($task);
  variable_set('install_task', $task);

  // Output page, if some output was required. Otherwise it is possible
  // that we are printing a JSON page and theme output should not be there.
  if (isset($output)) {
    print theme('maintenance_page', $output);
  }
}

/**
 * Batch callback for batch installation of modules.
 */
function _install_module_batch($module, $module_name, &$context) {
  _drupal_install_module($module);
  // We enable the installed module right away, so that the module will be
  // loaded by drupal_bootstrap in subsequent batch requests, and other
  // modules possibly depending on it can safely perform their installation
  // steps.
  module_enable(array($module));
  $context['results'][] = $module;
  $context['message'] = st('Installed %module module.', array('%module' => $module_name));
}

/**
 * Finished callback for the modules install batch.
 *
 * Advance installer task to language import.
 */
function _install_profile_batch_finished($success, $results) {
  variable_set('install_task', 'locale-initial-import');
}

/**
 * Finished callback for the first locale import batch.
 *
 * Advance installer task to the configure screen.
 */
function _install_locale_initial_batch_finished($success, $results) {
  variable_set('install_task', 'configure');
}

/**
 * Finished callback for the second locale import batch.
 *
 * Advance installer task to the finished screen.
 */
function _install_locale_remaining_batch_finished($success, $results) {
  variable_set('install_task', 'finished');
}

/**
 * The list of reserved tasks to run in the installer.
 */
function install_reserved_tasks() {
  return array('configure', 'profile-install', 'profile-install-batch', 'locale-initial-import', 'locale-initial-batch', 'profile-finished', 'locale-remaining-batch', 'finished', 'done');
}

/**
 * Check installation requirements and report any errors.
 */
function install_check_requirements($profile, $verify) {

  // If Drupal is not set up already, we need to create a settings file.
  if (!$verify) {
    $writable = FALSE;
    $conf_path = './'. conf_path(FALSE, TRUE);
    $settings_file = $conf_path .'/settings.php';
    $file = $conf_path;
    $exists = FALSE;
    // Verify that the directory exists.
    if (drupal_verify_install_file($conf_path, FILE_EXIST, 'dir')) {
      // Check to make sure a settings.php already exists.
      $file = $settings_file;
      if (drupal_verify_install_file($settings_file, FILE_EXIST)) {
        $exists = TRUE;
        // If it does, make sure it is writable.
        $writable = drupal_verify_install_file($settings_file, FILE_READABLE|FILE_WRITABLE);
      }
    }
    if (!$exists) {
      drupal_set_message(st('The @drupal installer requires that you create a settings file as part of the installation process.
<ol>
<li>Copy the %default_file file to %file.</li>
<li>Change file permissions so that it is writable by the web server. If you are unsure how to grant file permissions, please consult the <a href="@handbook_url">on-line handbook</a>.</li>
</ol>
More details about installing Drupal are available in INSTALL.txt.', array('@drupal' => drupal_install_profile_name(), '%file' => $file, '%default_file' => $conf_path .'/default.settings.php', '@handbook_url' => 'http://drupal.org/server-permissions')), 'error');
    }
    elseif (!$writable) {
      drupal_set_message(st('The @drupal installer requires write permissions to %file during the installation process. If you are unsure how to grant file permissions, please consult the <a href="@handbook_url">on-line handbook</a>.', array('@drupal' => drupal_install_profile_name(), '%file' => $file, '@handbook_url' => 'http://drupal.org/server-permissions')), 'error');
    }
  }

  // Check the other requirements.
  $requirements = drupal_check_profile($profile);
  $severity = drupal_requirements_severity($requirements);

  // If there are issues, report them.
  if ($severity == REQUIREMENT_ERROR) {

    foreach ($requirements as $requirement) {
      if (isset($requirement['severity']) && $requirement['severity'] == REQUIREMENT_ERROR) {
        $message = $requirement['description'];
        if (isset($requirement['value']) && $requirement['value']) {
          $message .= ' ('. st('Currently using !item !version', array('!item' => $requirement['title'], '!version' => $requirement['value'])) .')';
        }
        drupal_set_message($message, 'error');
      }
    }
  }
  if ($severity == REQUIREMENT_WARNING) {

    foreach ($requirements as $requirement) {
      if (isset($requirement['severity']) && $requirement['severity'] == REQUIREMENT_WARNING) {
        $message = $requirement['description'];
        if (isset($requirement['value']) && $requirement['value']) {
          $message .= ' ('. st('Currently using !item !version', array('!item' => $requirement['title'], '!version' => $requirement['value'])) .')';
        }
        drupal_set_message($message, 'warning');
      }
    }
  } 
}

/**
 * Add the installation task list to the current page.
 */
function install_task_list($active = NULL) {
  // Default list of tasks.
  $tasks = array(
    'profile-select'        => st('Choose profile'),
    'locale-select'         => st('Choose language'),
    'requirements'          => st('Verify requirements'),
    'database'              => st('Set up database'),
    'profile-install-batch' => st('Install profile'),
    'locale-initial-batch'  => st('Set up translations'),
    'configure'             => st('Configure site'),
  );

  $profiles = install_find_profiles();
  $profile = isset($_GET['profile']) && isset($profiles[$_GET['profile']]) ? $_GET['profile'] : '.';
  $locales = install_find_locales($profile);

  // If we have only one profile, remove 'Choose profile'
  // and rename 'Install profile'.
  if (count($profiles) == 1) {
    unset($tasks['profile-select']);
    $tasks['profile-install-batch'] = st('Install site');
  }

  // Add tasks defined by the profile.
  if ($profile) {
    $function = $profile .'_profile_task_list';
    if (function_exists($function)) {
      $result = $function();
      if (is_array($result)) {
        $tasks += $result;
      }
    }
  }

  if (count($locales) < 2 || empty($_GET['locale']) || $_GET['locale'] == 'en') {
    // If not required, remove translation import from the task list.
    unset($tasks['locale-initial-batch']);
  }
  else {
    // If required, add remaining translations import task.
    $tasks += array('locale-remaining-batch' => st('Finish translations'));
  }

  // Add finished step as the last task.
  $tasks += array(
    'finished'     => st('Finished')
  );

  // Let the theming function know that 'finished' and 'done'
  // include everything, so every step is completed.
  if (in_array($active, array('finished', 'done'))) {
    $active = NULL;
  }
  drupal_set_content('left', theme_task_list($tasks, $active));
}

/**
 * Form API array definition for site configuration.
 */
function install_configure_form(&$form_state, $url) {

  $form['intro'] = array(
    '#value' => st('To configure your website, please provide the following information.'),
    '#weight' => -10,
  );
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
    '#description' => st("The <em>From</em> address in automated e-mails sent during registration and new password requests, and other notifications. (Use an address ending in your site's domain to help prevent this e-mail being flagged as spam.)"),
    '#required' => TRUE,
    '#weight' => -15,
  );
  $form['admin_account'] = array(
    '#type' => 'fieldset',
    '#title' => st('Administrator account'),
    '#collapsible' => FALSE,
  );
  $form['admin_account']['account']['#tree'] = TRUE;
  $form['admin_account']['markup'] = array(
    '#value' => '<p class="description">'. st('The administrator account has complete access to the site; it will automatically be granted all permissions and can perform any administrative activity. This will be the only account that can perform certain activities, so keep its credentials safe.') .'</p>',
    '#weight' => -10,
  );

  $form['admin_account']['account']['name'] = array('#type' => 'textfield',
    '#title' => st('Username'),
    '#maxlength' => USERNAME_MAX_LENGTH,
    '#description' => st('Spaces are allowed; punctuation is not allowed except for periods, hyphens, and underscores.'),
    '#required' => TRUE,
    '#weight' => -10,
  );

  $form['admin_account']['account']['mail'] = array('#type' => 'textfield',
    '#title' => st('E-mail address'),
    '#maxlength' => EMAIL_MAX_LENGTH,
    '#description' => st('All e-mails from the system will be sent to this address. The e-mail address is not made public and will only be used if you wish to receive a new password or wish to receive certain news or notifications by e-mail.'),
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
  $form['server_settings']['date_default_timezone'] = array(
    '#type' => 'select',
    '#title' => st('Default time zone'),
    '#default_value' => 0,
    '#options' => _system_zonelist(),
    '#description' => st('By default, dates in this site will be displayed in the chosen time zone.'),
    '#weight' => 5,
  );

  $form['server_settings']['clean_url'] = array(
    '#type' => 'radios',
    '#title' => st('Clean URLs'),
    '#default_value' => 0,
    '#options' => array(0 => st('Disabled'), 1 => st('Enabled')),
    '#description' => st('This option makes Drupal emit "clean" URLs (i.e. without <code>?q=</code> in the URL).'),
    '#disabled' => TRUE,
    '#prefix' => '<div id="clean-url" class="install">',
    '#suffix' => '</div>',
    '#weight' => 10,
  );

  $form['server_settings']['update_status_module'] = array(
    '#type' => 'checkboxes',
    '#title' => st('Update notifications'),
    '#options' => array(1 => st('Check for updates automatically')),
    '#default_value' => array(1),
    '#description' => st('With this option enabled, Drupal will notify you when new releases are available. This will significantly enhance your site\'s security and is <strong>highly recommended</strong>. This requires your site to periodically send anonymous information on its installed components to <a href="@drupal">drupal.org</a>. For more information please see the <a href="@update">update notification information</a>.', array('@drupal' => 'http://drupal.org', '@update' => 'http://drupal.org/handbook/modules/update')),
    '#weight' => 15,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => st('Save and continue'),
    '#weight' => 15,
  );
  $form['#action'] = $url;
  $form['#redirect'] = FALSE;

  // Allow the profile to alter this form. $form_state isn't available
  // here, but to conform to the hook_form_alter() signature, we pass
  // an empty array.
  $hook_form_alter = $_GET['profile'] .'_form_alter';
  if (function_exists($hook_form_alter)) {
    $hook_form_alter($form, array(), 'install_configure');
  }
  return $form;
}

/**
 * Form API validate for the site configuration form.
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
 * Form API submit for the site configuration form.
 */
function install_configure_form_submit($form, &$form_state) {
  global $user;

  variable_set('site_name', $form_state['values']['site_name']);
  variable_set('site_mail', $form_state['values']['site_mail']);
  variable_set('date_default_timezone', $form_state['values']['date_default_timezone']);

  // Enable update.module if this option was selected.
  if ($form_state['values']['update_status_module'][1]) {
    drupal_install_modules(array('update'));
  }

  // Turn this off temporarily so that we can pass a password through.
  variable_set('user_email_verification', FALSE);
  $form_state['old_values'] = $form_state['values'];
  $form_state['values'] = $form_state['values']['account'];

  // We precreated user 1 with placeholder values. Let's save the real values.
  $account = user_load(1);
  $merge_data = array('init' => $form_state['values']['mail'], 'roles' => array(), 'status' => 1);
  user_save($account, array_merge($form_state['values'], $merge_data));
  // Log in the first user.
  user_authenticate($form_state['values']);
  $form_state['values'] = $form_state['old_values'];
  unset($form_state['old_values']);
  variable_set('user_email_verification', TRUE);

  if (isset($form_state['values']['clean_url'])) {
    variable_set('clean_url', $form_state['values']['clean_url']);
  }
  // The user is now logged in, but has no session ID yet, which
  // would be required later in the request, so remember it.
  $user->sid = session_id();

  // Record when this install ran.
  variable_set('install_time', time());
}

// Start the installer.
install_main();
