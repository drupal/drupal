<?php

/**
 * @file
 * API functions for installing modules and themes.
 */

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\OpCodeCache;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Site\Settings;

/**
 * Requirement severity -- Informational message only.
 */
const REQUIREMENT_INFO = -1;

/**
 * Requirement severity -- Requirement successfully met.
 */
const REQUIREMENT_OK = 0;

/**
 * Requirement severity -- Warning condition; proceed but flag warning.
 */
const REQUIREMENT_WARNING = 1;

/**
 * Requirement severity -- Error condition; abort installation.
 */
const REQUIREMENT_ERROR = 2;

/**
 * File permission check -- File exists.
 */
const FILE_EXIST = 1;

/**
 * File permission check -- File is readable.
 */
const FILE_READABLE = 2;

/**
 * File permission check -- File is writable.
 */
const FILE_WRITABLE = 4;

/**
 * File permission check -- File is executable.
 */
const FILE_EXECUTABLE = 8;

/**
 * File permission check -- File does not exist.
 */
const FILE_NOT_EXIST = 16;

/**
 * File permission check -- File is not readable.
 */
const FILE_NOT_READABLE = 32;

/**
 * File permission check -- File is not writable.
 */
const FILE_NOT_WRITABLE = 64;

/**
 * File permission check -- File is not executable.
 */
const FILE_NOT_EXECUTABLE = 128;

/**
 * Loads .install files for installed modules to initialize the update system.
 */
function drupal_load_updates() {
  foreach (drupal_get_installed_schema_version(NULL, FALSE, TRUE) as $module => $schema_version) {
    if ($schema_version > -1) {
      module_load_install($module);
    }
  }
}

/**
 * Loads the installation profile, extracting its defined distribution name.
 *
 * @return
 *   The distribution name defined in the profile's .info.yml file. Defaults to
 *   "Drupal" if none is explicitly provided by the installation profile.
 *
 * @see install_profile_info()
 */
function drupal_install_profile_distribution_name() {
  // During installation, the profile information is stored in the global
  // installation state (it might not be saved anywhere yet).
  $info = array();
  if (drupal_installation_attempted()) {
    global $install_state;
    if (isset($install_state['profile_info'])) {
      $info = $install_state['profile_info'];
    }
  }
  // At all other times, we load the profile via standard methods.
  else {
    $profile = drupal_get_profile();
    $info = system_get_info('module', $profile);
  }
  return isset($info['distribution']['name']) ? $info['distribution']['name'] : 'Drupal';
}

/**
 * Detects all supported databases that are compiled into PHP.
 *
 * @return
 *  An array of database types compiled into PHP.
 */
function drupal_detect_database_types() {
  $databases = drupal_get_database_types();

  foreach ($databases as $driver => $installer) {
    $databases[$driver] = $installer->name();
  }

  return $databases;
}

/**
 * Returns all supported database driver installer objects.
 *
 * @return \Drupal\Core\Database\Install\Tasks[]
 *   An array of available database driver installer objects.
 */
function drupal_get_database_types() {
  $databases = array();
  $drivers = array();

  // The internal database driver name is any valid PHP identifier.
  $mask = '/^' . DRUPAL_PHP_FUNCTION_PATTERN . '$/';
  $files = file_scan_directory(DRUPAL_ROOT . '/core/lib/Drupal/Core/Database/Driver', $mask, array('recurse' => FALSE));
  if (is_dir(DRUPAL_ROOT . '/drivers/lib/Drupal/Driver/Database')) {
    $files += file_scan_directory(DRUPAL_ROOT . '/drivers/lib/Drupal/Driver/Database/', $mask, array('recurse' => FALSE));
  }
  foreach ($files as $file) {
    if (file_exists($file->uri . '/Install/Tasks.php')) {
      $drivers[$file->filename] = $file->uri;
    }
  }
  foreach ($drivers as $driver => $file) {
    $installer = db_installer_object($driver);
    if ($installer->installable()) {
      $databases[$driver] = $installer;
    }
  }

  // Usability: unconditionally put the MySQL driver on top.
  if (isset($databases['mysql'])) {
    $mysql_database = $databases['mysql'];
    unset($databases['mysql']);
    $databases = array('mysql' => $mysql_database) + $databases;
  }

  return $databases;
}

/**
 * Replaces values in settings.php with values in the submitted array.
 *
 * This function replaces values in place if possible, even for
 * multidimensional arrays. This way the old settings do not linger,
 * overridden and also the doxygen on a value remains where it should be.
 *
 * @param $settings
 *   An array of settings that need to be updated. Multidimensional arrays
 *   are dumped up to a stdClass object. The object can have value, required
 *   and comment properties.
 *   @code
 *   $settings['config_directories'] = array(
 *     CONFIG_ACTIVE_DIRECTORY => (object) array(
 *       'value' => 'config_hash/active'
 *       'required' => TRUE,
 *     ),
 *     CONFIG_STAGING_DIRECTORY => (object) array(
 *       'value' => 'config_hash/staging',
 *       'required' => TRUE,
 *     ),
 *   );
 *   @endcode
 *   gets dumped as:
 *   @code
 *   $config_directories['active'] = 'config_hash/active';
 *   $config_directories['staging'] = 'config_hash/staging'
 *   @endcode
 */
function drupal_rewrite_settings($settings = array(), $settings_file = NULL) {
  if (!isset($settings_file)) {
    $settings_file = conf_path(FALSE) . '/settings.php';
  }
  // Build list of setting names and insert the values into the global namespace.
  $variable_names = array();
  $settings_settings = array();
  foreach ($settings as $setting => $data) {
    if ($setting != 'settings') {
      _drupal_rewrite_settings_global($GLOBALS[$setting], $data);
    }
    else {
      _drupal_rewrite_settings_global($settings_settings, $data);
    }
    $variable_names['$'. $setting] = $setting;
  }
  $contents = file_get_contents(DRUPAL_ROOT . '/' . $settings_file);
  if ($contents !== FALSE) {
    // Initialize the contents for the settings.php file if it is empty.
    if (trim($contents) === '') {
      $contents = "<?php\n";
    }
    // Step through each token in settings.php and replace any variables that
    // are in the passed-in array.
    $buffer = '';
    $state = 'default';
    foreach (token_get_all($contents) as $token) {
      if (is_array($token)) {
        list($type, $value) = $token;
      }
      else {
        $type = -1;
        $value = $token;
      }
      // Do not operate on whitespace.
      if (!in_array($type, array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT))) {
        switch ($state) {
          case 'default':
            if ($type === T_VARIABLE && isset($variable_names[$value])) {
              // This will be necessary to unset the dumped variable.
              $parent = &$settings;
              // This is the current index in parent.
              $index = $variable_names[$value];
              // This will be necessary for descending into the array.
              $current = &$parent[$index];
              $state = 'candidate_left';
            }
            break;
          case 'candidate_left':
            if ($value == '[') {
              $state = 'array_index';
            }
            if ($value == '=') {
              $state = 'candidate_right';
            }
            break;
          case 'array_index':
            if (_drupal_rewrite_settings_is_array_index($type, $value)) {
              $index = trim($value, '\'"');
              $state = 'right_bracket';
            }
            else {
              // $a[foo()] or $a[$bar] or something like that.
              throw new Exception('invalid array index');
            }
            break;
          case 'right_bracket':
            if ($value == ']') {
              if (isset($current[$index])) {
                // If the new settings has this index, descend into it.
                $parent = &$current;
                $current = &$parent[$index];
                $state = 'candidate_left';
              }
              else {
                // Otherwise, jump back to the default state.
                $state = 'wait_for_semicolon';
              }
            }
            else {
              // $a[1 + 2].
              throw new Exception('] expected');
            }
            break;
          case 'candidate_right':
            if (_drupal_rewrite_settings_is_simple($type, $value)) {
              $value = _drupal_rewrite_settings_dump_one($current);
              // Unsetting $current would not affect $settings at all.
              unset($parent[$index]);
              // Skip the semicolon because _drupal_rewrite_settings_dump_one() added one.
              $state = 'semicolon_skip';
            }
            else {
              $state = 'wait_for_semicolon';
            }
            break;
          case 'wait_for_semicolon':
            if ($value == ';') {
              $state = 'default';
            }
            break;
          case 'semicolon_skip':
            if ($value == ';') {
              $value = '';
              $state = 'default';
            }
            else {
              // If the expression was $a = 1 + 2; then we replaced 1 and
              // the + is unexpected.
              throw new Exception('Unexpected token after replacing value.');
            }
            break;
        }
      }
      $buffer .= $value;
    }
    foreach ($settings as $name => $setting) {
      $buffer .= _drupal_rewrite_settings_dump($setting, '$' . $name);
    }

    // Write the new settings file.
    if (file_put_contents(DRUPAL_ROOT . '/' . $settings_file, $buffer) === FALSE) {
      throw new Exception(t('Failed to modify %settings. Verify the file permissions.', array('%settings' => $settings_file)));
    }
    else {
      // In case any $settings variables were written, import them into the
      // Settings singleton.
      if (!empty($settings_settings)) {
        $old_settings = Settings::getAll();
        new Settings($settings_settings + $old_settings);
      }
      // The existing settings.php file might have been included already. In
      // case an opcode cache is enabled, the rewritten contents of the file
      // will not be reflected in this process. Ensure to invalidate the file
      // in case an opcode cache is enabled.
      OpCodeCache::invalidate(DRUPAL_ROOT . '/' . $settings_file);
    }
  }
  else {
    throw new Exception(t('Failed to open %settings. Verify the file permissions.', array('%settings' => $settings_file)));
  }
}

/**
 * Helper for drupal_rewrite_settings().
 *
 * Checks whether this token represents a scalar or NULL.
 *
 * @param int $type
 *   The token type
 *   @see token_name().
 * @param string $value
 *   The value of the token.
 *
 * @return bool
 *   TRUE if this token represents a scalar or NULL.
 */
function _drupal_rewrite_settings_is_simple($type, $value) {
  $is_integer = $type == T_LNUMBER;
  $is_float = $type == T_DNUMBER;
  $is_string = $type == T_CONSTANT_ENCAPSED_STRING;
  $is_boolean_or_null = $type == T_STRING && in_array(strtoupper($value), array('TRUE', 'FALSE', 'NULL'));
  return $is_integer || $is_float || $is_string || $is_boolean_or_null;
}


/**
 * Helper for drupal_rewrite_settings().
 *
 * Checks whether this token represents a valid array index: a number or a
 * string.
 *
 * @param int $type
 *   The token type
 *   @see token_name().
 *
 * @return bool
 *   TRUE if this token represents a number or a string.
 */
function _drupal_rewrite_settings_is_array_index($type) {
  $is_integer = $type == T_LNUMBER;
  $is_float = $type == T_DNUMBER;
  $is_string = $type == T_CONSTANT_ENCAPSED_STRING;
  return $is_integer || $is_float || $is_string;
}

/**
 * Helper for drupal_rewrite_settings().
 *
 * Makes the new settings global.
 *
 * @param array|null $ref
 *   A reference to a nested index in $GLOBALS.
 * @param array|object $variable
 *   The nested value of the setting being copied.
 */
function _drupal_rewrite_settings_global(&$ref, $variable) {
  if (is_object($variable)) {
    $ref = $variable->value;
  }
  else {
    foreach ($variable as $k => $v) {
      _drupal_rewrite_settings_global($ref[$k], $v);
    }
  }
}

/**
 * Helper for drupal_rewrite_settings().
 *
 * Dump the relevant value properties.
 *
 * @param array|object $variable
 *   The container for variable values.
 * @param string $variable_name
 *   Name of variable.
 * @return string
 *   A string containing valid PHP code of the variable suitable for placing
 *   into settings.php.
 */
function _drupal_rewrite_settings_dump($variable, $variable_name) {
  $return = '';
  if (is_object($variable)) {
    if (!empty($variable->required)) {
      $return .= _drupal_rewrite_settings_dump_one($variable, "$variable_name = ", "\n");
    }
  }
  else {
    foreach ($variable as $k => $v) {
      $return .= _drupal_rewrite_settings_dump($v, $variable_name . "['" . $k . "']");
    }
  }
  return $return;
}


/**
 * Helper for drupal_rewrite_settings().
 *
 * Dump the value of a value property and adds the comment if it exists.
 *
 * @param object $variable
 *   A stdClass object with at least a value property.
 * @param string $prefix
 *   A string to prepend to the variable's value.
 * @param string $suffix
 *   A string to append to the variable's value.
 * @return string
 *   A string containing valid PHP code of the variable suitable for placing
 *   into settings.php.
 */
function _drupal_rewrite_settings_dump_one(\stdClass $variable, $prefix = '', $suffix = '') {
  $return = $prefix . var_export($variable->value, TRUE) . ';';
  if (!empty($variable->comment)) {
    $return .= ' // ' . $variable->comment;
  }
  $return .= $suffix;
  return $return;
}

/**
 * Creates the config directory and ensures it is operational.
 *
 * @see install_settings_form_submit()
 * @see update_prepare_d8_bootstrap()
 */
function drupal_install_config_directories() {
  global $config_directories;

  // Add a randomized config directory name to settings.php, unless it was
  // manually defined in the existing already.
  if (empty($config_directories)) {
    $config_directories_hash = Crypt::randomBytesBase64(55);
    $settings['config_directories'] = array(
      CONFIG_ACTIVE_DIRECTORY => (object) array(
        'value' => conf_path() . '/files/config_' . $config_directories_hash . '/active',
        'required' => TRUE,
      ),
      CONFIG_STAGING_DIRECTORY => (object) array(
        'value' => conf_path() . '/files/config_' . $config_directories_hash . '/staging',
        'required' => TRUE,
      ),
    );
    // Rewrite settings.php, which also sets the value as global variable.
    drupal_rewrite_settings($settings);
  }

  // Ensure the config directories exist or can be created, and are writable.
  foreach (array(CONFIG_ACTIVE_DIRECTORY, CONFIG_STAGING_DIRECTORY) as $config_type) {
    // This should never fail, since if the config directory was specified in
    // settings.php it will have already been created and verified earlier, and
    // if it wasn't specified in settings.php, it is created here inside the
    // public files directory, which has already been verified to be writable
    // itself. But if it somehow fails anyway, the installation cannot proceed.
    // Bail out using a similar error message as in system_requirements().
    if (!install_ensure_config_directory($config_type)) {
      throw new Exception(t('The directory %directory could not be created or could not be made writable. To proceed with the installation, either create the directory and modify its permissions manually or ensure that the installer has the permissions to create it automatically. For more information, see the <a href="@handbook_url">online handbook</a>.', array(
        '%directory' => config_get_config_directory($config_type),
        '@handbook_url' => 'http://drupal.org/server-permissions',
      )));
    }

    // Put a README.txt into each config directory. This is required so that
    // they can later be added to git. Since these directories are auto-
    // created, we have to write out the README rather than just adding it
    // to the drupal core repo.
    switch ($config_type) {
      case CONFIG_ACTIVE_DIRECTORY:
        $text = 'If you change the configuration system to use file storage instead of the database for the active Drupal site configuration, this directory will contain the active configuration. By default, this directory will be empty. If you are using files to store the active configuration, and you want to move it between environments, files from this directory should be placed in the staging directory on the target server. To make this configuration active, visit admin/config/development/configuration/sync on the target server.';
        break;
      case CONFIG_STAGING_DIRECTORY:
        $text = 'This directory contains configuration to be imported into your Drupal site. To make this configuration active, visit admin/config/development/configuration/sync.';
        break;
    }
    $text .=  ' For information about deploying configuration between servers, see http://drupal.org/documentation/administer/config';
    file_put_contents(config_get_config_directory($config_type) . '/README.txt', $text);
  }
}

/**
 * Checks whether a config directory exists and is writable.
 *
 * This partially duplicates install_ensure_config_directory(), but is required
 * since the installer would create the config directory too early in the
 * installation process otherwise (e.g., when only visiting install.php when
 * there is a settings.php already, but not actually executing the
 * installation).
 *
 * @param string $type
 *   Type of config directory to return. Drupal core provides 'active' and
 *   'staging'.
 *
 * @return bool
 *   TRUE if the config directory exists and is writable.
 */
function install_verify_config_directory($type) {
  global $config_directories;
  if (!isset($config_directories[$type])) {
    return FALSE;
  }
  // config_get_config_directory() throws an exception when the passed $type
  // does not exist in $config_directories. This can happen if there is a
  // prepared settings.php that defines $config_directories already.
  try {
    $config_directory = config_get_config_directory($type);
    if (is_dir($config_directory) && is_writable($config_directory)) {
      return TRUE;
    }
  }
  catch (\Exception $e) {
  }
  return FALSE;
}

/**
 * Ensures that the config directory exists and is writable, or can be made so.
 *
 * @param string $type
 *   Type of config directory to return. Drupal core provides 'active' and
 *   'staging'.
 *
 * @return bool
 *   TRUE if the config directory exists and is writable.
 */
function install_ensure_config_directory($type) {
  // The config directory must be defined in settings.php.
  global $config_directories;
  if (!isset($config_directories[$type])) {
    return FALSE;
  }
  // The logic here is similar to that used by system_requirements() for other
  // directories that the installer creates.
  else {
    $config_directory = config_get_config_directory($type);
    return file_prepare_directory($config_directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
  }
}

/**
 * Verifies that all dependencies are met for a given installation profile.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @return
 *   The list of modules to install.
 */
function drupal_verify_profile($install_state) {
  include_once __DIR__ . '/file.inc';
  include_once __DIR__ . '/common.inc';

  $profile = $install_state['parameters']['profile'];
  if (!isset($profile) || !isset($install_state['profiles'][$profile])) {
    throw new Exception(install_no_profile_error());
  }
  $info = $install_state['profile_info'];

  // Get the list of available modules for the selected installation profile.
  $listing = new ExtensionDiscovery();
  $present_modules = array();
  foreach ($listing->scan('module') as $present_module) {
    $present_modules[] = $present_module->getName();
  }

  // The installation profile is also a module, which needs to be installed
  // after all the other dependencies have been installed.
  $present_modules[] = $profile;

  // Verify that all of the profile's required modules are present.
  $missing_modules = array_diff($info['dependencies'], $present_modules);

  $requirements = array();

  if (count($missing_modules)) {
    $modules = array();
    foreach ($missing_modules as $module) {
      $modules[] = '<span class="admin-missing">' . drupal_ucfirst($module) . '</span>';
    }
    $requirements['required_modules'] = array(
      'title'       => t('Required modules'),
      'value'       => t('Required modules not found.'),
      'severity'    => REQUIREMENT_ERROR,
      'description' => t('The following modules are required but were not found. Move them into the appropriate modules subdirectory, such as <em>/modules</em>. Missing modules: !modules', array('!modules' => implode(', ', $modules))),
    );
  }
  return $requirements;
}

/**
 * Installs the system module.
 *
 * Separated from the installation of other modules so core system
 * functions can be made available while other modules are installed.
 *
 * @param array $install_state
 *   An array of information about the current installation state. This is used
 *   to set the default language.
 */
function drupal_install_system($install_state) {
  // Remove the service provider of the early installer.
  unset($GLOBALS['conf']['container_service_providers']['InstallerServiceProvider']);

  $request = \Drupal::request();
  // Reboot into a full production environment to continue the installation.
  /** @var \Drupal\Core\Installer\InstallerKernel $kernel */
  $kernel = \Drupal::service('kernel');
  $kernel->shutdown();
  // Have installer rebuild from the disk, rather then building from scratch.
  $kernel->rebuildContainer(FALSE);
  $kernel->prepareLegacyRequest($request);

  // Install base system configuration.
  \Drupal::service('config.installer')->installDefaultConfig('core', 'core');

  // Install System module and rebuild the newly available routes.
  $kernel->getContainer()->get('module_handler')->install(array('system'), FALSE);
  \Drupal::service('router.builder')->rebuild();

  // DrupalKernel::prepareLegacyRequest() above calls into
  // DrupalKernel::bootCode(), which primes file_get_stream_wrappers()'s static
  // list of custom stream wrappers that are based on the currently enabled
  // list of modules (none).
  // @todo Custom stream wrappers of a new module have to be registered as soon
  //   as the module is installed/enabled. Fix either ModuleHandler::install()
  //   and/or DrupalKernel::updateModules().
  // @see https://drupal.org/node/2028109
  drupal_static_reset('file_get_stream_wrappers');

  // Ensure default language is saved.
  if (isset($install_state['parameters']['langcode'])) {
    \Drupal::config('system.site')
      ->set('langcode', $install_state['parameters']['langcode'])
      ->save();
  }
}

/**
 * Verifies the state of the specified file.
 *
 * @param $file
 *   The file to check for.
 * @param $mask
 *   An optional bitmask created from various FILE_* constants.
 * @param $type
 *   The type of file. Can be file (default), dir, or link.
 *
 * @return
 *   TRUE on success or FALSE on failure. A message is set for the latter.
 */
function drupal_verify_install_file($file, $mask = NULL, $type = 'file') {
  $return = TRUE;
  // Check for files that shouldn't be there.
  if (isset($mask) && ($mask & FILE_NOT_EXIST) && file_exists($file)) {
    return FALSE;
  }
  // Verify that the file is the type of file it is supposed to be.
  if (isset($type) && file_exists($file)) {
    $check = 'is_' . $type;
    if (!function_exists($check) || !$check($file)) {
      $return = FALSE;
    }
  }

  // Verify file permissions.
  if (isset($mask)) {
    $masks = array(FILE_EXIST, FILE_READABLE, FILE_WRITABLE, FILE_EXECUTABLE, FILE_NOT_READABLE, FILE_NOT_WRITABLE, FILE_NOT_EXECUTABLE);
    foreach ($masks as $current_mask) {
      if ($mask & $current_mask) {
        switch ($current_mask) {
          case FILE_EXIST:
            if (!file_exists($file)) {
              if ($type == 'dir') {
                drupal_install_mkdir($file, $mask);
              }
              if (!file_exists($file)) {
                $return = FALSE;
              }
            }
            break;
          case FILE_READABLE:
            if (!is_readable($file) && !drupal_install_fix_file($file, $mask)) {
              $return = FALSE;
            }
            break;
          case FILE_WRITABLE:
            if (!is_writable($file) && !drupal_install_fix_file($file, $mask)) {
              $return = FALSE;
            }
            break;
          case FILE_EXECUTABLE:
            if (!is_executable($file) && !drupal_install_fix_file($file, $mask)) {
              $return = FALSE;
            }
            break;
          case FILE_NOT_READABLE:
            if (is_readable($file) && !drupal_install_fix_file($file, $mask)) {
              $return = FALSE;
            }
            break;
          case FILE_NOT_WRITABLE:
            if (is_writable($file) && !drupal_install_fix_file($file, $mask)) {
              $return = FALSE;
            }
            break;
          case FILE_NOT_EXECUTABLE:
            if (is_executable($file) && !drupal_install_fix_file($file, $mask)) {
              $return = FALSE;
            }
            break;
        }
      }
    }
  }
  return $return;
}

/**
 * Creates a directory with the specified permissions.
 *
 * @param $file
 *  The name of the directory to create;
 * @param $mask
 *  The permissions of the directory to create.
 * @param $message
 *  (optional) Whether to output messages. Defaults to TRUE.
 *
 * @return
 *  TRUE/FALSE whether or not the directory was successfully created.
 */
function drupal_install_mkdir($file, $mask, $message = TRUE) {
  $mod = 0;
  $masks = array(FILE_READABLE, FILE_WRITABLE, FILE_EXECUTABLE, FILE_NOT_READABLE, FILE_NOT_WRITABLE, FILE_NOT_EXECUTABLE);
  foreach ($masks as $m) {
    if ($mask & $m) {
      switch ($m) {
        case FILE_READABLE:
          $mod |= 0444;
          break;
        case FILE_WRITABLE:
          $mod |= 0222;
          break;
        case FILE_EXECUTABLE:
          $mod |= 0111;
          break;
      }
    }
  }

  if (@drupal_mkdir($file, $mod)) {
    return TRUE;
  }
  else {
    return FALSE;
  }
}

/**
 * Attempts to fix file permissions.
 *
 * The general approach here is that, because we do not know the security
 * setup of the webserver, we apply our permission changes to all three
 * digits of the file permission (i.e. user, group and all).
 *
 * To ensure that the values behave as expected (and numbers don't carry
 * from one digit to the next) we do the calculation on the octal value
 * using bitwise operations. This lets us remove, for example, 0222 from
 * 0700 and get the correct value of 0500.
 *
 * @param $file
 *  The name of the file with permissions to fix.
 * @param $mask
 *  The desired permissions for the file.
 * @param $message
 *  (optional) Whether to output messages. Defaults to TRUE.
 *
 * @return
 *  TRUE/FALSE whether or not we were able to fix the file's permissions.
 */
function drupal_install_fix_file($file, $mask, $message = TRUE) {
  // If $file does not exist, fileperms() issues a PHP warning.
  if (!file_exists($file)) {
    return FALSE;
  }

  $mod = fileperms($file) & 0777;
  $masks = array(FILE_READABLE, FILE_WRITABLE, FILE_EXECUTABLE, FILE_NOT_READABLE, FILE_NOT_WRITABLE, FILE_NOT_EXECUTABLE);

  // FILE_READABLE, FILE_WRITABLE, and FILE_EXECUTABLE permission strings
  // can theoretically be 0400, 0200, and 0100 respectively, but to be safe
  // we set all three access types in case the administrator intends to
  // change the owner of settings.php after installation.
  foreach ($masks as $m) {
    if ($mask & $m) {
      switch ($m) {
        case FILE_READABLE:
          if (!is_readable($file)) {
            $mod |= 0444;
          }
          break;
        case FILE_WRITABLE:
          if (!is_writable($file)) {
            $mod |= 0222;
          }
          break;
        case FILE_EXECUTABLE:
          if (!is_executable($file)) {
            $mod |= 0111;
          }
          break;
        case FILE_NOT_READABLE:
          if (is_readable($file)) {
            $mod &= ~0444;
          }
          break;
        case FILE_NOT_WRITABLE:
          if (is_writable($file)) {
            $mod &= ~0222;
          }
          break;
        case FILE_NOT_EXECUTABLE:
          if (is_executable($file)) {
            $mod &= ~0111;
          }
          break;
      }
    }
  }

  // chmod() will work if the web server is running as owner of the file.
  if (@chmod($file, $mod)) {
    return TRUE;
  }
  else {
    return FALSE;
  }
}

/**
 * Sends the user to a different installer page.
 *
 * This issues an on-site HTTP redirect. Messages (and errors) are erased.
 *
 * @param $path
 *   An installer path.
 */
function install_goto($path) {
  global $base_url;
  $headers = array(
    // Not a permanent redirect.
    'Cache-Control' => 'no-cache',
  );
  $response = new RedirectResponse($base_url . '/' . $path, 302, $headers);
  $response->send();
}

/**
 * Returns the URL of the current script, with modified query parameters.
 *
 * This function can be called by low-level scripts (such as install.php and
 * update.php) and returns the URL of the current script. Existing query
 * parameters are preserved by default, but new ones can optionally be merged
 * in.
 *
 * This function is used when the script must maintain certain query parameters
 * over multiple page requests in order to work correctly. In such cases (for
 * example, update.php, which requires the 'continue=1' parameter to remain in
 * the URL throughout the update process if there are any requirement warnings
 * that need to be bypassed), using this function to generate the URL for links
 * to the next steps of the script ensures that the links will work correctly.
 *
 * @param $query
 *   (optional) An array of query parameters to merge in to the existing ones.
 *
 * @return
 *   The URL of the current script, with query parameters modified by the
 *   passed-in $query. The URL is not sanitized, so it still needs to be run
 *   through check_url() if it will be used as an HTML attribute value.
 *
 * @see drupal_requirements_url()
 */
function drupal_current_script_url($query = array()) {
  $uri = $_SERVER['SCRIPT_NAME'];
  $query = array_merge(UrlHelper::filterQueryParameters(\Drupal::request()->query->all()), $query);
  if (!empty($query)) {
    $uri .= '?' . UrlHelper::buildQuery($query);
  }
  return $uri;
}

/**
 * Returns a URL for proceeding to the next page after a requirements problem.
 *
 * This function can be called by low-level scripts (such as install.php and
 * update.php) and returns a URL that can be used to attempt to proceed to the
 * next step of the script.
 *
 * @param $severity
 *   The severity of the requirements problem, as returned by
 *   drupal_requirements_severity().
 *
 * @return
 *   A URL for attempting to proceed to the next step of the script. The URL is
 *   not sanitized, so it still needs to be run through check_url() if it will
 *   be used as an HTML attribute value.
 *
 * @see drupal_current_script_url()
 */
function drupal_requirements_url($severity) {
  $query = array();
  // If there are no errors, only warnings, append 'continue=1' to the URL so
  // the user can bypass this screen on the next page load.
  if ($severity == REQUIREMENT_WARNING) {
    $query['continue'] = 1;
  }
  return drupal_current_script_url($query);
}

/**
 * Checks an installation profile's requirements.
 *
 * @param string $profile
 *   Name of installation profile to check.
 * @param array $install_state
 *   The current state in the install process.
 *
 * @return array
 *   Array of the installation profile's requirements.
 */
function drupal_check_profile($profile, array $install_state) {
  include_once __DIR__ . '/file.inc';

  if (!isset($profile) || !isset($install_state['profiles'][$profile])) {
    throw new Exception(install_no_profile_error());
  }

  $info = install_profile_info($profile);

  // Collect requirement testing results.
  $requirements = array();
  foreach ($info['dependencies'] as $module) {
    module_load_install($module);
    $function = $module . '_requirements';
    if (function_exists($function)) {
      $requirements = array_merge($requirements, $function('install'));
    }
  }
  return $requirements;
}

/**
 * Extracts the highest severity from the requirements array.
 *
 * @param $requirements
 *   An array of requirements, in the same format as is returned by
 *   hook_requirements().
 *
 * @return
 *   The highest severity in the array.
 */
function drupal_requirements_severity(&$requirements) {
  $severity = REQUIREMENT_OK;
  foreach ($requirements as $requirement) {
    if (isset($requirement['severity'])) {
      $severity = max($severity, $requirement['severity']);
    }
  }
  return $severity;
}

/**
 * Checks a module's requirements.
 *
 * @param $module
 *   Machine name of module to check.
 *
 * @return
 *   TRUE or FALSE, depending on whether the requirements are met.
 */
function drupal_check_module($module) {
  module_load_install($module);
  // Check requirements
  $requirements = \Drupal::moduleHandler()->invoke($module, 'requirements', array('install'));
  if (is_array($requirements) && drupal_requirements_severity($requirements) == REQUIREMENT_ERROR) {
    // Print any error messages
    foreach ($requirements as $requirement) {
      if (isset($requirement['severity']) && $requirement['severity'] == REQUIREMENT_ERROR) {
        $message = $requirement['description'];
        if (isset($requirement['value']) && $requirement['value']) {
          $message .= ' (' . t('Currently using !item !version', array('!item' => $requirement['title'], '!version' => $requirement['value'])) . ')';
        }
        drupal_set_message($message, 'error');
      }
    }
    return FALSE;
  }
  return TRUE;
}

/**
 * Retrieves information about an installation profile from its .info.yml file.
 *
 * The information stored in a profile .info.yml file is similar to that stored
 * in a normal Drupal module .info.yml file. For example:
 * - name: The real name of the installation profile for display purposes.
 * - description: A brief description of the profile.
 * - dependencies: An array of shortnames of other modules that this install
 *   profile requires.
 *
 * Additional, less commonly-used information that can appear in a
 * profile.info.yml file but not in a normal Drupal module .info.yml file
 * includes:
 *
 * - distribution: Existence of this key denotes that the installation profile
 *   is intended to be the only eligible choice in a distribution and will be
 *   auto-selected during installation, whereas the installation profile
 *   selection screen will be skipped. If more than one distribution profile is
 *   found then the first one discovered will be selected.
 *   The following subproperties may be set:
 *   - name: The name of the distribution that is being installed, to be shown
 *     throughout the installation process. If omitted,
 *     drupal_install_profile_distribution_name() defaults to 'Drupal'.
 *   - install: Optional parameters to override the installer:
 *     - theme: The machine name of a theme to use in the installer instead of
 *       Drupal's default installer theme.
 *
 * Note that this function does an expensive file system scan to get info file
 * information for dependencies. If you only need information from the info
 * file itself, use system_get_info().
 *
 * Example of .info.yml file:
 * @code
 *    name = Minimal
 *    description = Start fresh, with only a few modules enabled.
 *    dependencies[] = block
 *    dependencies[] = dblog
 * @endcode
 *
 * @param $profile
 *   Name of profile.
 * @param $langcode
 *   Language code (if any).
 *
 * @return
 *   The info array.
 */
function install_profile_info($profile, $langcode = 'en') {
  $cache = &drupal_static(__FUNCTION__, array());

  if (!isset($cache[$profile])) {
    // Set defaults for module info.
    $defaults = array(
      'dependencies' => array(),
      'themes' => array('stark'),
      'description' => '',
      'version' => NULL,
      'hidden' => FALSE,
      'php' => DRUPAL_MINIMUM_PHP,
    );
    $profile_file = drupal_get_path('profile', $profile) . "/$profile.info.yml";
    $info = \Drupal::service('info_parser')->parse($profile_file);
    $info += $defaults;

    // drupal_required_modules() includes the current profile as a dependency.
    // Remove that dependency, since a module cannot depend on itself.
    $required = array_diff(drupal_required_modules(), array($profile));

    $locale = !empty($langcode) && $langcode != 'en' ? array('locale') : array();

    $info['dependencies'] = array_unique(array_merge($required, $info['dependencies'], $locale));

    $cache[$profile] = $info;
  }
  return $cache[$profile];
}

/**
 * Ensures the environment for a Drupal database on a predefined connection.
 *
 * This will run tasks that check that Drupal can perform all of the functions
 * on a database, that Drupal needs. Tasks include simple checks like CREATE
 * TABLE to database specific functions like stored procedures and client
 * encoding.
 */
function db_run_tasks($driver) {
  db_installer_object($driver)->runTasks();
  return TRUE;
}

/**
 * Returns a database installer object.
 *
 * @param $driver
 *   The name of the driver.
 *
 * @return \Drupal\Core\Database\Install\Tasks
 *   A class defining the requirements and tasks for installing the database.
 */
function db_installer_object($driver) {
  // We cannot use Database::getConnection->getDriverClass() here, because
  // the connection object is not yet functional.
  $task_class = "Drupal\\Core\\Database\\Driver\\{$driver}\\Install\\Tasks";
  if (class_exists($task_class)) {
    return new $task_class();
  }
  else {
    $task_class = "Drupal\\Driver\\Database\\{$driver}\\Install\\Tasks";
    return new $task_class();
  }
}
