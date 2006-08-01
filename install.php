<?php
// $Id: install.php,v 1.3 2006/08/01 14:59:21 dries Exp $

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
  global $profile;
  require_once './includes/bootstrap.inc';
  drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION);
  require_once './modules/system/system.install';

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
    _install_no_profile_error();
  }
  // Load the profile.
  require_once "./profiles/$profile.profile";

  // Change the settings.php information if verification failed earlier.
  if (!$verify) {
    install_change_settings();
  }

  // Perform actual installation defined in the profile.
  drupal_install_profile($profile);

  // Warn about settings.php permissions risk
  $settings_file = './'. conf_path() .'/settings.php';
  if (!drupal_verify_install_file($settings_file, FILE_EXIST|FILE_READABLE|FILE_NOT_WRITABLE)) {
    drupal_set_message(st('All necessary changes to %file have been made, so you should now remove write permissions to this file. Failure to remove write permissions to this file is a security risk.', array('%file' => $settings_file)), 'error');
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

    $url = parse_url($db_url);
    $db_user = urldecode($url['user']);
    $db_pass = urldecode($url['pass']);
    $db_host = urldecode($url['host']);
    $db_path = ltrim(urldecode($url['path']), '/');
    $settings_file = './'. conf_path() .'/settings.php';

    _install_settings_validate($db_prefix, $db_type, $db_user, $db_pass, $db_host, $db_path, $settings_file);
    if (!form_get_errors()) {
      return TRUE;
    }
  }
  return FALSE;
}

/**
 * Configure and rewrite settings.php.
 */
function install_change_settings() {
  global $profile, $db_url, $db_type, $db_prefix;

  $url = parse_url($db_url);
  $db_user = urldecode($url['user']);
  $db_pass = urldecode($url['pass']);
  $db_host = urldecode($url['host']);
  $db_path = ltrim(urldecode($url['path']), '/');
  $settings_file = './'. conf_path() .'/settings.php';

  // We always need this because we want to run form_get_errors.
  include_once './includes/form.inc';

  // The existing database settings are not working, so we need write access
  // to settings.php to change them.
  if (!drupal_verify_install_file($settings_file, FILE_EXIST|FILE_READABLE|FILE_WRITABLE)) {
    drupal_maintenance_theme();
    drupal_set_message(st('The Drupal installer requires write permissions to %file during the installation process.', array('%file' => $settings_file)), 'error');

    drupal_set_title('Drupal database setup');
    print theme('install_page', '');
    exit;
  }

  // Don't fill in placeholders
  if ($db_url == 'mysql://username:password@localhost/databasename') {
    $db_user = $db_pass = $db_path = '';
  }



  $db_types = drupal_detect_database_types();
  if (count($db_types) == 0) {
    $form['no_db_types'] = array(
      '#type' => 'markup',
      '#value' => 'Your web server does not appear to support any common database types. Check with your hosting provider to see if they offer any databases that <a href="http://drupal.org/node/270#database">Drupal supports</a>.',
    );
  }
  else {
    $form['basic_options'] = array(
      '#type' => 'fieldset',
      '#title' => 'Basic options',
      '#description' => st('<p>To set up your %drupal database, enter the following information.</p>', array('%drupal' => drupal_install_profile_name())),
    );

    if (count($db_types) > 1) {
      // Database type
      $form['basic_options']['db_type'] = array(
        '#type' => 'radios',
        '#title' => 'Database type',
        '#required' => TRUE,
        '#options' => drupal_detect_database_types(),
        '#default_value' => $db_type,
        '#description' => st('The type of database your %drupal data will be stored in.', array('%drupal' => drupal_install_profile_name())),
      );
      $db_path_description = st('The name of the database your %drupal data will be stored in. It must exist on your server before %drupal can be installed.', array('%drupal' => drupal_install_profile_name()));
    }
    else {
      if (count($db_types) == 1) {
        $db_types = array_values($db_types);
        $form['basic_options']['db_type'] = array(
          '#type' => 'hidden',
          '#value' => $db_types[0],
        );
        $db_path_description = st('The name of the %db_type database your %drupal data will be stored in. It must exist on your server before %drupal can be installed.', array('%db_type' => $db_types[0], '%drupal' => drupal_install_profile_name()));
      }
    }

    // Database name
    $form['basic_options']['db_path'] = array(
      '#type' => 'textfield',
      '#title' => 'Database name',
      '#default_value' => $db_path,
      '#size' => 45,
      '#maxlength' => 45,
      '#required' => TRUE,
      '#description' => $db_path_description
    );

    // Database username
    $form['basic_options']['db_user'] = array(
      '#type' => 'textfield',
      '#title' => 'Database username',
      '#default_value' => $db_user,
      '#size' => 45,
      '#maxlength' => 45,
      '#required' => TRUE,
    );

    // Database username
    $form['basic_options']['db_pass'] = array(
      '#type' => 'password',
      '#title' => 'Database password',
      '#default_value' => $db_pass,
      '#size' => 45,
      '#maxlength' => 45,
      '#required' => TRUE,
    );

    $form['advanced_options'] = array(
      '#type' => 'fieldset',
      '#title' => 'Advanced options',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => st('These options are only necessary for some sites. If you\'re not sure what you should enter here, leave the default settings or check with your hosting provider.')
    );

    // Database host
    $form['advanced_options']['db_host'] = array(
      '#type' => 'textfield',
      '#title' => 'Database host',
      '#default_value' => $db_host,
      '#size' => 45,
      '#maxlength' => 45,
      '#required' => TRUE,
      '#description' => st('If your database is located on a different server, change this.', array('%drupal' => drupal_install_profile_name())),
    );

    // Database prefix
    $form['advanced_options']['db_prefix'] = array(
      '#type' => 'textfield',
      '#title' => 'Database prefix',
      '#default_value' => $db_prefix,
      '#size' => 45,
      '#maxlength' => 45,
      '#required' => FALSE,
      '#description' => st('If more than one %drupal web site will be sharing this database, enter a table prefix for your %drupal site here.', array('%drupal' => drupal_install_profile_name())),
    );

    $form['save'] = array(
      '#type' => 'submit',
      '#value' => 'Save configuration',
    );

    $form['errors'] = array();
    $form['settings_file'] = array('#type' => 'value', '#value' => $settings_file);
    $form['_db_url'] = array('#type' => 'value');
    $form['#action'] = "install.php?profile=$profile";
    $form['#redirect'] = NULL;
    drupal_maintenance_theme();
  }
  $output = drupal_get_form('install_settings', $form);
  drupal_set_title('Database configuration');
  print theme('install_page', $output);
  exit;
}

/**
 * Form API validate for install_settings form.
 */
function install_settings_validate($form_id, $form_values, $form) {
  global $db_url;
  _install_settings_validate($form_values['db_prefix'], $form_values['db_type'], $form_values['db_user'], $form_values['db_pass'], $form_values['db_host'], $form_values['db_path'], $form_values['settings_file'], $form);
}

/**
 * Helper function for install_settings_validate.
 */
function _install_settings_validate($db_prefix, $db_type, $db_user, $db_pass, $db_host, $db_path, $settings_file, $form = NULL) {
  global $db_url;

  // Check for default username/password
  if ($db_user == 'username' && $db_pass == 'password') {
    form_set_error('db_user', st('You have configured %drupal to use the default username and password. This is not allowed for security reasons.', array('%drupal' => drupal_install_profile_name())));
  }

  // Verify database prefix
  if (!empty($db_prefix) && preg_match('/[^A-Za-z0-9_]/', $db_prefix)) {
    form_set_error('db_prefix', st('The database prefix you have entered, %db_prefix, is invalid. The database prefix can only contain alphanumeric characters and underscores.', array('%db_prefix' => $db_prefix)), 'error');
  }

  // Check database type
  if (!isset($form)) {
    $db_type = substr($db_url, 0, strpos($db_url, '://'));
  }
  $databases = drupal_detect_database_types();
  if (!in_array($db_type, $databases)) {
    form_set_error('db_type', st("In your %settings_file file you have configured Drupal to use a %db_type server, however your PHP installation currently does not support this database type.", array('%settings_file' => $settings_file, '%db_type' => $db_type)));
  }
  else {
    // Verify
    $db_url = $db_type .'://'. urlencode($db_user) .($db_pass ? ':'. urlencode($db_pass) : '') .'@'. ($db_host ? urlencode($db_host) : 'localhost') .'/'. urlencode($db_path);
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
function install_settings_submit($form_id, $form_values) {
  global $profile;

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
  install_goto("install.php?profile=$profile");
}

/**
 * Find all .profile files and allow admin to select which to install.
 *
 * @return
 *   The selected profile.
 */
function install_select_profile() {
  include_once './includes/file.inc';
  include_once './includes/form.inc';

  $profiles = file_scan_directory('./profiles', '\.profile$', array('.', '..', 'CVS'), 0, TRUE, 'name', 0);
  // Don't need to choose profile if only one available.
  if (sizeof($profiles) == 1) {
    $profile = array_pop($profiles);
    require_once $profile->filename;
    return $profile->name;
  }
  elseif (sizeof($profiles) > 1) {
    drupal_maintenance_theme();
    $form = '';
    foreach ($profiles as $profile) {
      include_once($profile->filename);
      if ($_POST['edit']['profile'] == $profile->name) {
        return $profile->name;
      }
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
      '#value' => 'Save configuration',
    );

    drupal_set_title('Select an installation profile');
    print theme('install_page', drupal_get_form('install_select_profile', $form));
    exit;
  }
}

/**
 * Show an error page when there are no profiles available.
 */
function install_no_profile_error() {
  drupal_maintenance_theme();
  drupal_set_title('No profiles available');
  print theme('install_page', '<p>We were unable to find any installer profiles. Installer profiles tell us what modules to enable and what schema to install in the database. A profile is necessary to continue with the installation process.</p>');
  exit;
}


/**
 * Show an error page when Drupal has already been installed.
 */
function install_already_done_error() {
  drupal_maintenance_theme();
  drupal_set_title('Drupal already installed');
  print theme('install_page', '<p>Drupal has already been installed on this site. To start over, you must empty your existing database. To install to a different database, edit the appropriate <em>settings.php</em> file in the <em>sites</em> folder.</p>');
  exit;
}

/**
 * Page displayed when the installation is complete. Called from install.php.
 */
function install_complete($profile) {
  global $base_url;

  // Bootstrap newly installed Drupal, while preserving existing messages.
  $messages = $_SESSION['messages'];
  drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
  $_SESSION['messages'] = $messages;

  // Build final page.
  drupal_maintenance_theme();
  drupal_set_title(st('%drupal installation complete', array('%drupal' => drupal_install_profile_name())));
  $output = st('<p>Congratulations, %drupal has been successfully installed.</p>', array('%drupal' => drupal_install_profile_name()));

  // Show profile finalization info.
  $function = $profile .'_profile_final';
  if (function_exists($function)) {
    // More steps required
    $output .= $function();
  }
  else {
    // No more steps
    $msg = drupal_set_message() ? 'Please review the messages above before continuing on to <a href="%url">your new site</a>.' : 'You may now visit <a href="%url">your new site</a>.';
    $output .= strtr('<p>'. $msg .'</p>', array('%url' => url('')));
  }

  // Output page.
  print theme('maintenance_page', $output);
}

install_main();
