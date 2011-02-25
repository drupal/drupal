<?php

require_once './includes/install.inc';

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
  global $profile, $install_locale;
  require_once './modules/system/system.install';
  require_once './includes/file.inc';

  // Ensure correct page headers are sent (e.g. caching)
  drupal_page_header();

  // Check existing settings.php.
  $verify = install_verify_settings();

  // Drupal may already be installed.
  if ($verify) {
    // Establish a connection to the database.
    require_once './includes/database.inc';
    db_set_active();
    // Check if Drupal is installed.
    if (install_verify_drupal()) {
      install_already_done_error();
    }
  }

  // Load module basics (needed for hook invokes).
  include_once './includes/module.inc';
  $module_list['system']['filename'] = 'modules/system/system.module';
  $module_list['filter']['filename'] = 'modules/filter/filter.module';
  module_list(TRUE, FALSE, FALSE, $module_list);
  drupal_load('module', 'system');
  drupal_load('module', 'filter');

  // Decide which profile to use.
  if (!empty($_GET['profile'])) {
    $profile = preg_replace('/[^a-zA-Z_0-9]/', '', $_GET['profile']);
  }
  elseif ($profile = install_select_profile()) {
    install_goto("install.php?profile=$profile");
  }
  else {
    install_no_profile_error();
  }

  // Locale selection
  if (!empty($_GET['locale'])) {
    $install_locale = preg_replace('/[^a-zA-Z_0-9]/', '', $_GET['locale']);
  }
  elseif (($install_locale = install_select_locale($profile)) !== FALSE) {
    install_goto("install.php?profile=$profile&locale=$install_locale");
  }

  // Load the profile.
  require_once "./profiles/$profile/$profile.profile";

  // Check the installation requirements for Drupal and this profile.
  install_check_requirements($profile);

  // Change the settings.php information if verification failed earlier.
  // Note: will trigger a redirect if database credentials change.
  if (!$verify) {
    install_change_settings($profile, $install_locale);
  }

  // Verify existence of all required modules.
  $modules = drupal_verify_profile($profile, $install_locale);
  if (!$modules) {
    install_missing_modules_error($profile);
  }

  // Perform actual installation defined in the profile.
  drupal_install_profile($profile, $modules);

  // Warn about settings.php permissions risk
  $settings_file = './'. conf_path() .'/settings.php';
  if (!drupal_verify_install_file($settings_file, FILE_EXIST|FILE_READABLE|FILE_NOT_WRITABLE)) {
    drupal_set_message(st('All necessary changes to %file have been made, so you should now remove write permissions to this file. Failure to remove write permissions to this file is a security risk.', array('%file' => $settings_file)), 'error');
  }
  else {
    drupal_set_message(st('All necessary changes to %file have been made. It has been set to read-only for security.', array('%file' => $settings_file)));
  }

  // Show end page.
  install_complete($profile);
}

/**
 * Verify if Drupal is installed.
 */
function install_verify_drupal() {
  $result = @db_query("SELECT name FROM {system} WHERE name = 'system'");
  return $result && db_result($result) == 'system';
}

/**
 * Verify existing settings.php
 */
function install_verify_settings() {
  global $db_prefix, $db_type, $db_url;

  // Verify existing settings (if any).
  if ($_SERVER['REQUEST_METHOD'] == 'GET' && $db_url != 'mysql://username:password@localhost/databasename') {
    // We need this because we want to run form_get_errors.
    include_once './includes/form.inc';

    $url = parse_url(is_array($db_url) ? $db_url['default'] : $db_url);
    $db_user = urldecode($url['user']);
    $db_pass = urldecode($url['pass']);
    $db_host = urldecode($url['host']);
    $db_port = isset($url['port']) ? urldecode($url['port']) : '';
    $db_path = ltrim(urldecode($url['path']), '/');
    $settings_file = './'. conf_path() .'/settings.php';

    _install_settings_form_validate($db_prefix, $db_type, $db_user, $db_pass, $db_host, $db_port, $db_path, $settings_file);
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
  $db_user = urldecode($url['user']);
  $db_pass = urldecode($url['pass']);
  $db_host = urldecode($url['host']);
  $db_port = isset($url['port']) ? urldecode($url['port']) : '';
  $db_path = ltrim(urldecode($url['path']), '/');
  $settings_file = './'. conf_path() .'/settings.php';

  // We always need this because we want to run form_get_errors.
  include_once './includes/form.inc';
  drupal_maintenance_theme();

  // Don't fill in placeholders
  if ($db_url == 'mysql://username:password@localhost/databasename') {
    $db_user = $db_pass = $db_path = '';
  }
  elseif (!empty($db_url)) {
    // Do not install over a configured settings.php.
    install_already_done_error();
  }

  // The existing database settings are not working, so we need write access
  // to settings.php to change them.
  if (!drupal_verify_install_file($settings_file, FILE_EXIST|FILE_READABLE|FILE_WRITABLE)) {
    drupal_set_message(st('The @drupal installer requires write permissions to %file during the installation process.', array('@drupal' => drupal_install_profile_name(), '%file' => $settings_file)), 'error');

    drupal_set_title(st('Drupal database setup'));
    print theme('install_page', '');
    exit;
  }

  $output = drupal_get_form('install_settings_form', $profile, $install_locale, $settings_file, $db_url, $db_type, $db_prefix, $db_user, $db_pass, $db_host, $db_port, $db_path);
  drupal_set_title(st('Database configuration'));
  print theme('install_page', $output);
  exit;
}


/**
 * Form API array definition for install_settings.
 */
function install_settings_form($profile, $install_locale, $settings_file, $db_url, $db_type, $db_prefix, $db_user, $db_pass, $db_host, $db_port, $db_path) {
  $db_types = drupal_detect_database_types();
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
      // Database type
      $db_types = drupal_detect_database_types();
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
      '#maxlength' => 45,
      '#required' => TRUE,
      '#description' => $db_path_description
    );

    // Database username
    $form['basic_options']['db_user'] = array(
      '#type' => 'textfield',
      '#title' => st('Database username'),
      '#default_value' => $db_user,
      '#size' => 45,
      '#maxlength' => 45,
      '#required' => TRUE,
    );

    // Database username
    $form['basic_options']['db_pass'] = array(
      '#type' => 'password',
      '#title' => st('Database password'),
      '#default_value' => $db_pass,
      '#size' => 45,
      '#maxlength' => 45,
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
      '#maxlength' => 45,
      '#required' => TRUE,
      '#description' => st('If your database is located on a different server, change this.'),
    );

    // Database port
    $form['advanced_options']['db_port'] = array(
      '#type' => 'textfield',
      '#title' => st('Database port'),
      '#default_value' => $db_port,
      '#size' => 45,
      '#maxlength' => 45,
      '#description' => st('If your database server is listening to a non-standard port, enter its number.'),
    );

    // Table prefix
    $form['advanced_options']['db_prefix'] = array(
      '#type' => 'textfield',
      '#title' => st('Table prefix'),
      '#default_value' => $db_prefix,
      '#size' => 45,
      '#maxlength' => 45,
      '#description' => st('If more than one @drupal web site will be sharing this database, enter a table prefix for your @drupal site here.', array('@drupal' => drupal_install_profile_name())),
    );

    $form['save'] = array(
      '#type' => 'submit',
      '#value' => st('Save configuration'),
    );

    $form['errors'] = array();
    $form['settings_file'] = array('#type' => 'value', '#value' => $settings_file);
    $form['_db_url'] = array('#type' => 'value');
    $form['#action'] = "install.php?profile=$profile" . ($install_locale ? "&locale=$install_locale" : '');
    $form['#redirect'] = NULL;
  }
  return $form;
}
/**
 * Form API validate for install_settings form.
 */
function install_settings_form_validate($form_id, $form_values, $form) {
  global $db_url;
  _install_settings_form_validate($form_values['db_prefix'], $form_values['db_type'], $form_values['db_user'], $form_values['db_pass'], $form_values['db_host'], $form_values['db_port'], $form_values['db_path'], $form_values['settings_file'], $form);
}

/**
 * Helper function for install_settings_validate.
 */
function _install_settings_form_validate($db_prefix, $db_type, $db_user, $db_pass, $db_host, $db_port, $db_path, $settings_file, $form = NULL) {
  global $db_url;

  // Check for default username/password
  if ($db_user == 'username' && $db_pass == 'password') {
    form_set_error('db_user', st('You have configured @drupal to use the default username and password. This is not allowed for security reasons.', array('@drupal' => drupal_install_profile_name())));
  }

  // Verify the table prefix
  if (!empty($db_prefix) && is_string($db_prefix) && !preg_match('/^[A-Za-z0-9_]+$/', $db_prefix)) {
    form_set_error('db_prefix', st('The database table prefix you have entered, %db_prefix, is invalid. The table prefix can only contain alphanumeric characters or underscores.', array('%db_prefix' => $db_prefix)), 'error');
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
    $db_url = $db_type .'://'. urlencode($db_user) .($db_pass ? ':'. urlencode($db_pass) : '') .'@'. ($db_host ? urlencode($db_host) : 'localhost'). ($db_port ? ":$db_port" : '') .'/'. urlencode($db_path);
    if (isset($form)) {
      form_set_value($form['_db_url'], $db_url);
    }
    $success = array();

    $function = 'drupal_test_'. $db_type;
    if (!$function($db_url, $success)) {
      if (isset($success['CONNECT'])) {
        form_set_error('db_type', st('In order for Drupal to work and to proceed with the installation process you must resolve all permission issues reported above. We were able to verify that we have permission for the following commands: %commands. For more help with configuring your database server, see the <a href="http://drupal.org/node/258">Installation and upgrading handbook</a>. If you are unsure what any of this means you should probably contact your hosting provider.', array('%commands' => implode($success, ', '))));
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
function install_settings_form_submit($form_id, $form_values) {
  global $profile, $install_locale;

  // Update global settings array and save
  $settings['db_url'] = array(
    'value'    => $form_values['_db_url'],
    'required' => TRUE,
  );
  $settings['db_prefix'] = array(
    'value'    => $form_values['db_prefix'],
    'required' => TRUE,
  );
  drupal_rewrite_settings($settings);

  // Continue to install profile step
  install_goto("install.php?profile=$profile" . ($install_locale ? "&locale=$install_locale" : ''));
}

/**
 * Find all .profile files and allow admin to select which to install.
 *
 * @return
 *   The selected profile.
 */
function install_select_profile() {
  include_once './includes/form.inc';

  $profiles = file_scan_directory('./profiles', '\.profile$', array('.', '..', 'CVS'), 0, TRUE, 'name', 0);
  // Don't need to choose profile if only one available.
  if (sizeof($profiles) == 1) {
    $profile = array_pop($profiles);
    require_once $profile->filename;
    return $profile->name;
  }
  elseif (sizeof($profiles) > 1) {
    foreach ($profiles as $profile) {
      if ($_POST['profile'] == $profile->name) {
        return $profile->name;
      }
    }

    drupal_maintenance_theme();

    drupal_set_title(st('Select an installation profile'));
    print theme('install_page', drupal_get_form('install_select_profile_form', $profiles));
    exit;
  }
}

function install_select_profile_form($profiles) {
  foreach ($profiles as $profile) {
    include_once($profile->filename);
    // Load profile details.
    $function = $profile->name .'_profile_details';
    if (function_exists($function)) {
      $details = $function();
    }
    // If set, used defined name. Otherwise use file name.
    $name = isset($details['name']) ? $details['name'] : $profile->name;
    $form['profile'][$name] = array(
      '#type' => 'radio',
      '#value' => 'default',
      '#return_value' => $profile->name,
      '#title' => $name,
      '#description' => isset($details['description']) ? $details['description'] : '',
      '#parents' => array('profile'),
    );
  }
  $form['submit'] =  array(
    '#type' => 'submit',
    '#value' => st('Save configuration'),
  );
  return $form;
}

/**
 * Find all .po files for the current profile and allow admin to select which to use.
 *
 * @return
 *   The selected language.
 */
function install_select_locale($profilename) {
  include_once './includes/file.inc';
  include_once './includes/form.inc';

  // Collect possible locales, add default
  $locales = file_scan_directory('./profiles/' . $profilename, '\.po$', array('.', '..', 'CVS'), 0, FALSE);
  array_unshift($locales, (object) array('name' => 'en'));

  // Don't need to choose locale if only one (English) is available.
  if (sizeof($locales) == 1) {
    return FALSE;
  } else {
    foreach ($locales as $locale) {
      if ($_POST['locale'] == $locale->name) {
        return $locale->name;
      }
    }

    drupal_maintenance_theme();

    drupal_set_title(st('Choose your preferred language'));
    print theme('install_page', drupal_get_form('install_select_locale_form', $locales));
    exit;
  }
}

function install_select_locale_form($locales) {
  include_once './includes/locale.inc';
  $languages = _locale_get_iso639_list();
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
    '#value' => st('Save configuration'),
  );
  return $form;
}

/**
 * Show an error page when there are no profiles available.
 */
function install_no_profile_error() {
  drupal_maintenance_theme();
  drupal_set_title(st('No profiles available'));
  print theme('install_page', '<p>'. st('We were unable to find any installer profiles. Installer profiles tell us what modules to enable and what schema to install in the database. A profile is necessary to continue with the installation process.') .'</p>');
  exit;
}


/**
 * Show an error page when Drupal has already been installed.
 */
function install_already_done_error() {
  global $base_url;

  drupal_maintenance_theme();
  drupal_set_title(st('Drupal already installed'));
  print theme('install_page', st('<ul><li>To start over, you must empty your existing database and replace the appropriate <em>settings.php</em> with an unmodified copy.</li><li>To install to a different database, edit the appropriate <em>settings.php</em> file in the <em>sites</em> folder.</li><li>To upgrade an existing installation, proceed to the <a href="@base-url/update.php">update script</a>.</li></ul>', array('@base-url' => $base_url)));
  exit;
}

/**
 * Show an error page when Drupal is missing required modules.
 */
function install_missing_modules_error($profile) {
  global $base_url;

  drupal_maintenance_theme();
  drupal_set_title(st('Modules missing'));
  print theme('install_page', '<p>'. st('One or more required modules are missing. Please check the error messages and <a href="!url">try again</a>.', array('!url' => "install.php?profile=$profile")) .'</p>');
  exit;
}

/**
 * Page displayed when the installation is complete. Called from install.php.
 */
function install_complete($profile) {
  global $base_url;
  $output = '';
  // Store install profile for later use.
  variable_set('install_profile', $profile);

  // Bootstrap newly installed Drupal, while preserving existing messages.
  $messages = $_SESSION['messages'];
  drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
  $_SESSION['messages'] = $messages;

  // Build final page.
  drupal_maintenance_theme();
  drupal_set_title(st('@drupal installation complete', array('@drupal' => drupal_install_profile_name())));
  $output .= '<p>'. st('Congratulations, @drupal has been successfully installed.', array('@drupal' => drupal_install_profile_name())) .'</p>';

  // Show profile finalization info.
  $function = $profile .'_profile_final';
  if (function_exists($function)) {
    // More steps required
    $profile_message = $function();
  }

  // If the profile returned a welcome message, use that instead of default.
  if (isset($profile_message)) {
    $output .= $profile_message;
  }
  else {
    // No more steps
    $output .= '<p>' . (drupal_set_message() ? st('Please review the messages above before continuing on to <a href="@url">your new site</a>.', array('@url' => url(''))) : st('You may now visit <a href="@url">your new site</a>.', array('@url' => url('')))) . '</p>';
  }
  // Output page.
  print theme('maintenance_page', $output);
}

/**
 * Page to check installation requirements and report any errors.
 */
function install_check_requirements($profile) {
  $requirements = drupal_check_profile($profile);
  $severity = drupal_requirements_severity($requirements);

  // If there are issues, report them.
  if ($severity == REQUIREMENT_ERROR) {
    drupal_maintenance_theme();

    foreach ($requirements as $requirement) {
      if (isset($requirement['severity']) && $requirement['severity'] == REQUIREMENT_ERROR) {
        drupal_set_message($requirement['description'] .' ('. st('Currently using !item !version', array('!item' => $requirement['title'], '!version' => $requirement['value'])) .')', 'error');
      }
    }

    drupal_set_title(st('Incompatible environment'));
    print theme('install_page', '');
    exit;
  }
}

install_main();
