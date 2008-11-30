<?php
// $Id$

/**
 * Indicates that a module has not been installed yet.
 */
define('SCHEMA_UNINSTALLED', -1);

/**
 * Indicates that a module has been installed.
 */
define('SCHEMA_INSTALLED', 0);

/**
 * Requirement severity -- Informational message only.
 */
define('REQUIREMENT_INFO', -1);

/**
 * Requirement severity -- Requirement successfully met.
 */
define('REQUIREMENT_OK', 0);

/**
 * Requirement severity -- Warning condition; proceed but flag warning.
 */
define('REQUIREMENT_WARNING', 1);

/**
 * Requirement severity -- Error condition; abort installation.
 */
define('REQUIREMENT_ERROR', 2);

/**
 * File permission check -- File exists.
 */
define('FILE_EXIST', 1);

/**
 * File permission check -- File is readable.
 */
define('FILE_READABLE', 2);

/**
 * File permission check -- File is writable.
 */
define('FILE_WRITABLE', 4);

/**
 * File permission check -- File is executable.
 */
define('FILE_EXECUTABLE', 8);

/**
 * File permission check -- File does not exist.
 */
define('FILE_NOT_EXIST', 16);

/**
 * File permission check -- File is not readable.
 */
define('FILE_NOT_READABLE', 32);

/**
 * File permission check -- File is not writable.
 */
define('FILE_NOT_WRITABLE', 64);

/**
 * File permission check -- File is not executable.
 */
define('FILE_NOT_EXECUTABLE', 128);

/**
 * Initialize the update system by loading all installed module's .install files.
 */
function drupal_load_updates() {
  foreach (drupal_get_installed_schema_version(NULL, FALSE, TRUE) as $module => $schema_version) {
    if ($schema_version > -1) {
      module_load_install($module);
    }
  }
}

/**
 * Returns an array of available schema versions for a module.
 *
 * @param $module
 *   A module name.
 * @return
 *   If the module has updates, an array of available updates sorted by version.
 *   Otherwise, FALSE.
 */
function drupal_get_schema_versions($module) {
  $updates = array();
  $functions = get_defined_functions();
  foreach ($functions['user'] as $function) {
    if (strpos($function, $module . '_update_') === 0) {
      $version = substr($function, strlen($module . '_update_'));
      if (is_numeric($version)) {
        $updates[] = $version;
      }
    }
  }
  if (count($updates) == 0) {
    return FALSE;
  }

  // Make sure updates are run in numeric order, not in definition order.
  sort($updates, SORT_NUMERIC);

  return $updates;
}

/**
 * Returns the currently installed schema version for a module.
 *
 * @param $module
 *   A module name.
 * @param $reset
 *   Set to TRUE after modifying the system table.
 * @param $array
 *   Set to TRUE if you want to get information about all modules in the
 *   system.
 * @return
 *   The currently installed schema version.
 */
function drupal_get_installed_schema_version($module, $reset = FALSE, $array = FALSE) {
  static $versions = array();

  if ($reset) {
    $versions = array();
  }

  if (!$versions) {
    $versions = array();
    $result = db_query("SELECT name, schema_version FROM {system} WHERE type = '%s'", 'module');
    while ($row = db_fetch_object($result)) {
      $versions[$row->name] = $row->schema_version;
    }
  }

  return $array ? $versions : $versions[$module];
}

/**
 * Update the installed version information for a module.
 *
 * @param $module
 *   A module name.
 * @param $version
 *   The new schema version.
 */
function drupal_set_installed_schema_version($module, $version) {
  db_query("UPDATE {system} SET schema_version = %d WHERE name = '%s'", $version, $module);
}

/**
 * Loads the install profile definition, extracting its defined name.
 *
 * @return
 *   The name defined in the profile's _profile_details() hook.
 */
function drupal_install_profile_name() {
  global $profile;
  static $name = NULL;

  if (!isset($name)) {
    // Load profile details.
    $function = $profile . '_profile_details';
    if (function_exists($function)) {
      $details = $function();
    }
    $name = isset($details['name']) ? $details['name'] : 'Drupal';
  }

  return $name;
}

/**
 * Auto detect the base_url with PHP predefined variables.
 *
 * @param $file
 *   The name of the file calling this function so we can strip it out of
 *   the URI when generating the base_url.
 * @return
 *   The auto-detected $base_url that should be configured in settings.php
 */
function drupal_detect_baseurl($file = 'install.php') {
  global $profile;
  $proto = $_SERVER['HTTPS'] ? 'https://' : 'http://';
  $host = $_SERVER['SERVER_NAME'];
  $port = ($_SERVER['SERVER_PORT'] == 80 ? '' : ':' . $_SERVER['SERVER_PORT']);
  $uri = preg_replace("/\?.*/", '', $_SERVER['REQUEST_URI']);
  $dir = str_replace("/$file", '', $uri);

  return "$proto$host$port$dir";
}

/**
 * Detect all supported databases that are compiled into PHP.
 *
 * @return
 *  An array of database types compiled into PHP.
 */
function drupal_detect_database_types() {
  $databases = array();

  // We define a driver as a directory in /includes/database that in turn
  // contains a database.inc file.  That allows us to drop in additional drivers
  // without modifying the installer.
  // Because we have no registry yet, we need to also include the install.inc
  // file for the driver explicitly.

  foreach (file_scan_directory(DRUPAL_ROOT . '/includes/database', '/^[a-z]*$/i', '/(\.\.?|CVS)$/', 0, FALSE) as $file) {
    include_once "{$file->filename}/install.inc";
    include_once "{$file->filename}/database.inc";
    $drivers[$file->basename] = $file->filename;
  }

  foreach ($drivers as $driver => $file) {
    $class = 'DatabaseInstaller_' . $driver;
    $installer = new $class();
    if ($installer->installable()) {
      $databases[$driver] = $installer->name();
    }
  }

  // Usability: unconditionnally put the MySQL driver on top.
  if (isset($databases['mysql'])) {
    $mysql_database = $databases['mysql'];
    unset($databases['mysql']);
    $databases = array('mysql' => $mysql_database) + $databases;
  }

  return $databases;
}

abstract class DatabaseInstaller {
  protected $success = array();
  protected $tests = array(
    'testCreate' => array(
      'query' => 'CREATE TABLE drupal_install_test (id int NULL)',
      'success' => 'CREATE',
      'message' => 'Failed to create a test table on your %name database server with the command %query. %name reports the following message: %error.<ul><li>Are you sure the configured username has the necessary %name permissions to create tables in the database?</li></ul>For more help, see the <a href="http://drupal.org/node/258">Installation and upgrading handbook</a>. If you are unsure what these terms mean you should probably contact your hosting provider.',
      'fatal' => TRUE,
    ),
    'testInsert' => array(
      'query' => 'INSERT INTO drupal_install_test (id) VALUES (1)',
      'success' => 'INSERT',
      'message' => 'Failed to insert a value into a test table on your %name database server. We tried inserting a value with the command %query and %name reported the following error: %error.',
    ),
    'testUpdate' => array(
      'query' => 'UPDATE drupal_install_test SET id = 2',
      'success' => 'UPDATE',
      'message' => 'Failed to update a value in a test table on your %name database server. We tried updating a value with the command %query and %name reported the following error: %error.',
    ),
    'testDelete' => array(
      'query' => 'DELETE FROM drupal_install_test',
      'success' => 'DELETE',
      'message' => 'Failed to delete a value from a test table on your %name database server. We tried deleting a value with the command %query and %name reported the following error: %error.',
    ),
    'testDrop' => array(
      'query' => 'DROP TABLE drupal_install_test',
      'success' => 'DELETE',
      'message' => 'Failed to drop a test table from your %name database server. We tried dropping a table with the command %query and %name reported the following error %error.',
    ),
  );
  public $error = FALSE;

  protected function hasPdoDriver() {
    return in_array($this->pdoDriver, PDO::getAvailableDrivers());
  }

  public function installable() {
    return $this->hasPdoDriver();
  }

  abstract public function name();

  public function test() {
    $return = $this->testConnect();
    if ($return === FALSE) {
      return FALSE;
    }
    foreach ($this->tests as $test) {
      $return = $this->runTestQuery($test['query'], $test['success'], $test['message'], !empty($tests['fatal']));
      if ($return === FALSE) {
        return FALSE;
      }
    }
    return $this->success;
  }

  /**
   * Check if we can connect to the database.
   *
   * @return
   *  FALSE on failure.
   */
  protected function testConnect() {
    try {
      db_set_active();
      $this->success[] = 'CONNECT';
    }
    catch (Exception $e) {
      drupal_set_message(st('Failed to connect to your %name database server. %name reports the following message: %error.<ul><li>Are you sure you have the correct username and password?</li><li>Are you sure that you have typed the correct database hostname?</li><li>Are you sure that the database server is running?</li></ul>For more help, see the <a href="http://drupal.org/node/258">Installation and upgrading handbook</a>. If you are unsure what these terms mean you should probably contact your hosting provider.', array('%error' => $e->getMessage(), 'name' => $this->name())), 'error');
      return FALSE;
    }
  }

  protected function runTestQuery($query, $success, $message, $fatal = FALSE) {
    try {
      db_query($query);
      $this->success[] = $success;
    }
    catch (Exception $e) {
      drupal_set_message(st($message, array('%query' => $query, '%error' => $e->getMessage(), '%name' => $this->name())), 'error');
      $this->error = TRUE;
      if ($fatal) {
        return FALSE;
      }
    }
  }
}

/**
 * Replace values in settings.php with values in the submitted array.
 *
 * @param $settings
 *   An array of settings that need to be updated.
 */
function drupal_rewrite_settings($settings = array(), $prefix = '') {
  $default_settings = 'sites/default/default.settings.php';
  $settings_file = conf_path(FALSE, TRUE) . '/' . $prefix . 'settings.php';

  // Build list of setting names and insert the values into the global namespace.
  $keys = array();
  foreach ($settings as $setting => $data) {
    $GLOBALS[$setting] = $data['value'];
    $keys[] = $setting;
  }

  $buffer = NULL;
  $first = TRUE;
  if ($fp = fopen(DRUPAL_ROOT . '/' . $default_settings, 'r')) {
    // Step line by line through settings.php.
    while (!feof($fp)) {
      $line = fgets($fp);
      if ($first && substr($line, 0, 5) != '<?php') {
        $buffer = "<?php\n\n";
      }
      $first = FALSE;
      // Check for constants.
      if (substr($line, 0, 7) == 'define(') {
        preg_match('/define\(\s*[\'"]([A-Z_-]+)[\'"]\s*,(.*?)\);/', $line, $variable);
        if (in_array($variable[1], $keys)) {
          $setting = $settings[$variable[1]];
          $buffer .= str_replace($variable[2], " '" . $setting['value'] . "'", $line);
          unset($settings[$variable[1]]);
          unset($settings[$variable[2]]);
        }
        else {
          $buffer .= $line;
        }
      }
      // Check for variables.
      elseif (substr($line, 0, 1) == '$') {
        preg_match('/\$([^ ]*) /', $line, $variable);
        if (in_array($variable[1], $keys)) {
          // Write new value to settings.php in the following format:
          //    $'setting' = 'value'; // 'comment'
          $setting = $settings[$variable[1]];
          $buffer .= '$' . $variable[1] . " = " . var_export($setting['value'], TRUE) . ";" . (!empty($setting['comment']) ? ' // ' . $setting['comment'] . "\n" : "\n");
          unset($settings[$variable[1]]);
        }
        else {
          $buffer .= $line;
        }
      }
      else {
        $buffer .= $line;
      }
    }
    fclose($fp);

    // Add required settings that were missing from settings.php.
    foreach ($settings as $setting => $data) {
      if ($data['required']) {
        $buffer .= "\$$setting = " . var_export($data['value'], TRUE) . ";\n";
      }
    }

    $fp = fopen(DRUPAL_ROOT . '/' . $settings_file, 'w');
    if ($fp && fwrite($fp, $buffer) === FALSE) {
      drupal_set_message(st('Failed to modify %settings, please verify the file permissions.', array('%settings' => $settings_file)), 'error');
    }
  }
  else {
    drupal_set_message(st('Failed to open %settings, please verify the file permissions.', array('%settings' => $default_settings)), 'error');
  }
}

/**
 * Get list of all .install files.
 *
 * @param $module_list
 *   An array of modules to search for their .install files.
 */
function drupal_get_install_files($module_list = array()) {
  $installs = array();
  foreach ($module_list as $module) {
    $installs = array_merge($installs, drupal_system_listing('/'. $module . '.install$/', 'modules'));
  }
  return $installs;
}

/**
 * Get a list of modules required by an installation profile.
 *
 * @param profile
 *   Name of profile.
 * @param locale
 *   Name of locale used (if any).
 * @return
 *   The list of modules to install.
 */
function drupal_get_profile_modules($profile, $locale = 'en') {
  $profile_file = "./profiles/$profile/$profile.profile";
  require_once($profile_file);

  // Get a list of modules required by this profile.
  $function = $profile . '_profile_modules';
  return array_merge(drupal_required_modules(), $function(), ($locale != 'en' ? array('locale') : array()));
}

/**
 * Verify an install profile for installation.
 *
 * @param $profile
 *   Name of install profile to verify.
 * @param $locale
 *   Name of locale used (if any).
 * @return
 *   The list of modules to install.
 */
function drupal_verify_profile($profile, $locale) {
  include_once DRUPAL_ROOT . '/includes/file.inc';
  include_once DRUPAL_ROOT . '/includes/common.inc';

  $profile_file = DRUPAL_ROOT . "/profiles/$profile/$profile.profile";

  if (!isset($profile) || !file_exists($profile_file)) {
    install_no_profile_error();
  }

  $module_list = drupal_get_profile_modules($profile, $locale);

  // Get a list of modules that exist in Drupal's assorted subdirectories.
  $present_modules = array();
  foreach (drupal_system_listing('/\.module$/', 'modules', 'name', 0) as $present_module) {
    $present_modules[] = $present_module->name;
  }

  // Verify that all of the profile's required modules are present.
  $missing_modules = array_diff($module_list, $present_modules);

  $requirements = array();

  if (count($missing_modules)) {
    $modules = array();
    foreach ($missing_modules as $module) {
      $modules[] = '<span class="admin-missing">' . drupal_ucfirst($module) . '</span>';
    }
    $requirements['required_modules'] = array(
      'title'       => st('Required modules'),
      'value'       => st('Required modules not found.'),
      'severity'    => REQUIREMENT_ERROR,
      'description' => st('The following modules are required but were not found. Please move them into the appropriate modules subdirectory, such as <em>sites/all/modules</em>. Missing modules: !modules', array('!modules' => implode(', ', $modules))),
    );
  }
  return $requirements;
}

/**
 * Calls the install function for a given list of modules.
 *
 * @param $module_list
 *   The modules to install.
 */
function drupal_install_modules($module_list = array()) {
  $files = module_rebuild_cache();
  $module_list = array_flip(array_values($module_list));
  do {
    $moved = FALSE;
    foreach ($module_list as $module => $weight) {
      $file = $files[$module];
      if (isset($file->info['dependencies']) && is_array($file->info['dependencies'])) {
        foreach ($file->info['dependencies'] as $dependency) {
          if (isset($module_list[$dependency]) && $module_list[$module] < $module_list[$dependency] +1) {
            $module_list[$module] = $module_list[$dependency] +1;
            $moved = TRUE;
          }
        }
      }
    }
  } while ($moved);
  asort($module_list);
  $module_list = array_keys($module_list);
  $modules_installed = array_filter($module_list, '_drupal_install_module');
  if (!empty($modules_installed)) {
    module_invoke_all('modules_installed', $modules_installed);
  }
  module_enable($module_list);
}

/**
 * Callback to install an individual install profile module.
 *
 * Used during installation to install modules one at a time and then
 * enable them, or to install a number of modules at one time
 * from admin/build/modules.
 *
 * @param $module
 *   The machine name of the module to install.
 * @return
 *   TRUE if the module got installed.
 */
function _drupal_install_module($module) {
  if (drupal_get_installed_schema_version($module, TRUE) == SCHEMA_UNINSTALLED) {
    module_load_install($module);
    module_invoke($module, 'install');
    $versions = drupal_get_schema_versions($module);
    drupal_set_installed_schema_version($module, $versions ? max($versions) : SCHEMA_INSTALLED);
    return TRUE;
  }
}

/**
 * Manually include all files for the active database.
 *
 * Because we have no registry yet, we need to manually include the
 * necessary database include files.
 */
function drupal_install_init_database() {
  static $included = FALSE;

  if (!$included) {
    $connection_info = Database::getConnectionInfo();
    $driver = $connection_info['default']['driver'];
    require_once DRUPAL_ROOT . '/includes/database/query.inc';
    require_once DRUPAL_ROOT . '/includes/database/select.inc';
    require_once DRUPAL_ROOT . '/includes/database/schema.inc';
    foreach (glob(DRUPAL_ROOT . '/includes/database/' . $driver . '/*.inc') as $include_file) {
      require_once $include_file;
    }
    $included = TRUE;
  }
}

/**
 * Callback to install the system module.
 *
 * Separated from the installation of other modules so core system
 * functions can be made available while other modules are installed.
 */
function drupal_install_system() {
  $system_path = dirname(drupal_get_filename('module', 'system', NULL));
  require_once DRUPAL_ROOT . '/' . $system_path . '/system.install';
  drupal_install_init_database();
  module_invoke('system', 'install');

  $system_versions = drupal_get_schema_versions('system');
  $system_version = $system_versions ? max($system_versions) : SCHEMA_INSTALLED;
  db_query("INSERT INTO {system} (filename, name, type, owner, status, schema_version) VALUES('%s', '%s', '%s', '%s', %d, %d)", $system_path . '/system.module', 'system', 'module', '', 1, $system_version);
  // Now that we've installed things properly, bootstrap the full Drupal environment
  drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
  module_rebuild_cache();
}

/**
 * Calls the uninstall function and updates the system table for a given module.
 *
 * @param $module_list
 *   The modules to uninstall.
 */
function drupal_uninstall_modules($module_list = array()) {
  foreach ($module_list as $module) {
    // First, retrieve all the module's menu paths from db.
    drupal_load('module', $module);
    $paths = module_invoke($module, 'menu');

    // Uninstall the module.
    module_load_install($module);
    module_invoke($module, 'uninstall');

    // Now remove the menu links for all paths declared by this module.
    if (!empty($paths)) {
      $paths = array_keys($paths);
      // Clean out the names of load functions.
      foreach ($paths as $index => $path) {
        $parts = explode('/', $path, MENU_MAX_PARTS);
        foreach ($parts as $k => $part) {
          if (preg_match('/^%[a-z_]*$/', $part)) {
            $parts[$k] = '%';
          }
        }
        $paths[$index] = implode('/', $parts);
      }
      $placeholders = implode(', ', array_fill(0, count($paths), "'%s'"));

      $result = db_query('SELECT * FROM {menu_links} WHERE router_path IN (' . $placeholders . ') AND external = 0 ORDER BY depth DESC', $paths);
      // Remove all such items. Starting from those with the greatest depth will
      // minimize the amount of re-parenting done by menu_link_delete().
      while ($item = db_fetch_array($result)) {
        _menu_delete_item($item, TRUE);
      }
    }

    drupal_set_installed_schema_version($module, SCHEMA_UNINSTALLED);
  }

  if (!empty($module_list)) {
    // Call hook_module_uninstall to let other modules act
    module_invoke_all('modules_uninstalled', $module_list);
  }
}

/**
 * Verify the state of the specified file.
 *
 * @param $file
 *   The file to check for.
 * @param $mask
 *   An optional bitmask created from various FILE_* constants.
 * @param $type
 *   The type of file. Can be file (default), dir, or link.
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
 * Create a directory with specified permissions.
 *
 * @param $file
 *  The name of the directory to create;
 * @param $mask
 *  The permissions of the directory to create.
 * @param $message
 *  (optional) Whether to output messages. Defaults to TRUE.
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
          $mod += 444;
          break;
        case FILE_WRITABLE:
          $mod += 222;
          break;
        case FILE_EXECUTABLE:
          $mod += 111;
          break;
      }
    }
  }

  if (@mkdir($file, intval("0$mod", 8))) {
    return TRUE;
  }
  else {
    return FALSE;
  }
}

/**
 * Attempt to fix file permissions.
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
  // If PHP safe_mode is enabled the currently executing script must also
  // have the same owner.
  if (@chmod($file, $mod)) {
    return TRUE;
  }
  else {
    return FALSE;
  }
}


/**
 * Send the user to a different installer page.
 *
 * This issues an on-site HTTP redirect. Messages (and errors) are erased.
 *
 * @param $path
 *   An installer path.
 */
function install_goto($path) {
  global $base_url;
  header('Location: ' . $base_url . '/' . $path);
  header('Cache-Control: no-cache'); // Not a permanent redirect.
  exit();
}

/**
 * Functional equivalent of t(), used when some systems are not available.
 *
 * Used during the install process, when database, theme, and localization
 * system is possibly not yet available.
 *
 * @see t()
 */
function st($string, $args = array()) {
  static $locale_strings = NULL;
  global $profile, $install_locale;

  if (!isset($locale_strings)) {
    $locale_strings = array();
    $filename = 'profiles/' . $profile . '/translations/' . $install_locale . '.po';
    if (file_exists(DRUPAL_ROOT . '/' . $filename)) {
      require_once DRUPAL_ROOT . '/includes/locale.inc';
      $file = (object) array('filepath' => $filename);
      _locale_import_read_po('mem-store', $file);
      $locale_strings = _locale_import_one_string('mem-report');
    }
  }

  require_once DRUPAL_ROOT . '/includes/theme.inc';
  // Transform arguments before inserting them
  foreach ($args as $key => $value) {
    switch ($key[0]) {
      // Escaped only
      case '@':
        $args[$key] = check_plain($value);
        break;
      // Escaped and placeholder
      case '%':
      default:
        $args[$key] = '<em>' . check_plain($value) . '</em>';
        break;
      // Pass-through
      case '!':
    }
  }
  return strtr((!empty($locale_strings[$string]) ? $locale_strings[$string] : $string), $args);
}

/**
 * Check an install profile's requirements.
 *
 * @param $profile
 *   Name of install profile to check.
 * @return
 *   Array of the install profile's requirements.
 */
function drupal_check_profile($profile) {
  include_once DRUPAL_ROOT . '/includes/file.inc';

  $profile_file = DRUPAL_ROOT . "/profiles/$profile/$profile.profile";

  if (!isset($profile) || !file_exists($profile_file)) {
    install_no_profile_error();
  }

  require_once $profile_file;

  // Get a list of modules required by this profile.
  $function = $profile . '_profile_modules';
  $module_list = array_unique(array_merge(drupal_required_modules(), $function()));

  // Get a list of all .install files.
  $installs = drupal_get_install_files($module_list);

  // Collect requirement testing results
  $requirements = array();
  foreach ($installs as $install) {
    require_once DRUPAL_ROOT . '/' . $install->filename;
    $function = $install->name. '_requirements';
    if (function_exists($function)) {
      $requirements = array_merge($requirements, $function('install'));
    }
  }
  return $requirements;
}

/**
 * Extract highest severity from requirements array.
 *
 * @param $requirements
 *   An array of requirements, in the same format as is returned by
 *   hook_requirements().
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
 * Check a module's requirements.
 *
 * @param $module
 *   Machine name of module to check.
 * @return
 *   TRUE/FALSE depending on the requirements are in place.
 */
function drupal_check_module($module) {
  // Include install file
  $install = drupal_get_install_files(array($module));
  if (isset($install[$module])) {
    require_once DRUPAL_ROOT . '/' . $install[$module]->filename;

    // Check requirements
    $requirements = module_invoke($module, 'requirements', 'install');
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
  }
  return TRUE;
}
