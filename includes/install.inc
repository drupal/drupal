<?php
// $Id: install.inc,v 1.135 2010/05/18 18:11:13 dries Exp $

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
  $updates = &drupal_static(__FUNCTION__, NULL);
  if (!isset($updates[$module])) {
    $updates = array();

    foreach (module_list() as $loaded_module) {
      $updates[$loaded_module] = array();
    }

    // Prepare regular expression to match all possible defined hook_update_N().
    $regexp = '/^(?P<module>.+)_update_(?P<version>\d+)$/';
    $functions = get_defined_functions();
    // Narrow this down to functions ending with an integer, since all
    // hook_update_N() functions end this way, and there are other
    // possible functions which match '_update_'. We use preg_grep() here
    // instead of foreaching through all defined functions, since the loop
    // through all PHP functions can take significant page execution time
    // and this function is called on every administrative page via
    // system_requirements().
    foreach (preg_grep('/_\d+$/', $functions['user']) as $function) {
      // If this function is a module update function, add it to the list of
      // module updates.
      if (preg_match($regexp, $function, $matches)) {
        $updates[$matches['module']][] = $matches['version'];
      }
    }
    // Ensure that updates are applied in numerical order.
    foreach ($updates as &$module_updates) {
      sort($module_updates, SORT_NUMERIC);
    }
  }
  return empty($updates[$module]) ? FALSE : $updates[$module];
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
 *   The currently installed schema version, or SCHEMA_UNINSTALLED if the
 *   module is not installed.
 */
function drupal_get_installed_schema_version($module, $reset = FALSE, $array = FALSE) {
  static $versions = array();

  if ($reset) {
    $versions = array();
  }

  if (!$versions) {
    $versions = array();
    $result = db_query("SELECT name, schema_version FROM {system} WHERE type = :type", array(':type' => 'module'));
    foreach ($result as $row) {
      $versions[$row->name] = $row->schema_version;
    }
  }

  if ($array) {
    return $versions;
  }
  else {
    return isset($versions[$module]) ? $versions[$module] : SCHEMA_UNINSTALLED;
  }
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
  db_update('system')
    ->fields(array('schema_version' => $version))
    ->condition('name', $module)
    ->execute();
}

/**
 * Loads the install profile, extracting its defined distribution name.
 *
 * @return
 *   The distribution name defined in the profile's .info file. Defaults to
 *   "Drupal" if none is explicitly provided by the install profile.
 *
 * @see install_profile_info()
 */
function drupal_install_profile_distribution_name() {
  global $install_state;
  return $install_state['profile_info']['distribution_name'];
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
  // contains a database.inc file. That allows us to drop in additional drivers
  // without modifying the installer.
  // Because we have no registry yet, we need to also include the install.inc
  // file for the driver explicitly.
  require_once DRUPAL_ROOT . '/includes/database/database.inc';
  spl_autoload_register('db_autoload');
  foreach (file_scan_directory(DRUPAL_ROOT . '/includes/database', '/^[a-z]*$/i', array('recurse' => FALSE)) as $file) {
    $drivers[$file->filename] = $file->uri;
  }

  foreach ($drivers as $driver => $file) {
    $class = 'DatabaseTasks_' . $driver;
    $installer = new $class();
    if ($installer->installable()) {
      $databases[$driver] = $installer->name();
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
 * Database installer structure.
 *
 * Defines basic Drupal requirements for databases.
 */
abstract class DatabaseTasks {

  /**
   * Structure that describes each task to run.
   *
   * @var array
   *
   * Each value of the tasks array is an associative array defining the function
   * to call (optional) and any arguments to be passed to the function.
   */
  protected $tasks = array(
    array(
      'arguments'   => array(
        'CREATE TABLE drupal_install_test (id int NULL)',
        'Drupal can use CREATE TABLE database commands.',
        'Failed to <strong>CREATE</strong> a test table on your %name database server with the command %query. %name reports the following message: %error.<p>Are you sure the configured username has the necessary %name permissions to create tables in the database?</p>',
        TRUE,
      ),
    ),
    array(
      'arguments'   => array(
        'INSERT INTO drupal_install_test (id) VALUES (1)',
        'Drupal can use INSERT database commands.',
        'Failed to <strong>INSERT</strong> a value into a test table on your %name database server. We tried inserting a value with the command %query and %name reported the following error: %error.',
      ),
    ),
    array(
      'arguments'   => array(
        'UPDATE drupal_install_test SET id = 2',
        'Drupal can use UPDATE database commands.',
        'Failed to <strong>UPDATE</strong> a value in a test table on your %name database server. We tried updating a value with the command %query and %name reported the following error: %error.',
      ),
    ),
    array(
      'arguments'   => array(
        'DELETE FROM drupal_install_test',
        'Drupal can use DELETE database commands.',
        'Failed to <strong>DELETE</strong> a value from a test table on your %name database server. We tried deleting a value with the command %query and %name reported the following error: %error.',
      ),
    ),
    array(
      'arguments'   => array(
        'DROP TABLE drupal_install_test',
        'Drupal can use DROP TABLE database commands.',
        'Failed to <strong>DROP</strong> a test table from your %name database server. We tried dropping a table with the command %query and %name reported the following error %error.',
      ),
    ),
  );
  /**
   * Results from tasks.
   *
   * @var array
   */
  protected $results = array();

  /**
   * Ensure the PDO driver is supported by the version of PHP in use.
   */
  protected function hasPdoDriver() {
    return in_array($this->pdoDriver, PDO::getAvailableDrivers());
  }

  /**
   * Assert test as failed.
   */
  protected function fail($message) {
    $this->results[$message] = FALSE;
  }

  /**
   * Assert test as a pass.
   */
  protected function pass($message) {
    $this->results[$message] = TRUE;
  }

  /**
   * Check whether Drupal is installable on the database.
   */
  public function installable() {
    return $this->hasPdoDriver() && empty($this->error);
  }

  abstract public function name();

  /**
   * Run database tasks and tests to see if Drupal can run on the database.
   */
  public function runTasks() {
    // We need to establish a connection before we can run tests.
    if ($this->connect()) {
      foreach ($this->tasks as $task) {
        if (!isset($task['function'])) {
          $task['function'] = 'runTestQuery';
        }
        if (method_exists($this, $task['function'])) {
          // Returning false is fatal. No other tasks can run.
          if (FALSE === call_user_func_array(array($this, $task['function']), $task['arguments'])) {
            break;
          }
        }
        else {
          throw new DatabaseTaskException(st("Failed to run all tasks against the database server. The task %task wasn't found.", array('%task' => $task['function'])));
        }
      }
    }
    // Check for failed results and compile message
    $message = '';
    foreach ($this->results as $result => $success) {
      if (!$success) {
        $message .= '<p class="error">' . $result  . '</p>';
      }
    }
    if (!empty($message)) {
      $message = '<p>In order for Drupal to work, and to continue with the installation process, you must resolve all issues reported below. For more help with configuring your database server, see the <a href="http://drupal.org/getting-started/install">installation handbook</a>. If you are unsure what any of this means you should probably contact your hosting provider.</p>' . $message;
      throw new DatabaseTaskException($message);
    }
  }

  /**
   * Check if we can connect to the database.
   */
  protected function connect() {
    try {
      // This doesn't actually test the connection.
      db_set_active();
      // Now actually do a check.
      Database::getConnection();
      $this->pass('Drupal can CONNECT to the database ok.');
    }
    catch (Exception $e) {
      $this->fail(st('Failed to connect to your %name database server. %name reports the following message: %error.<ul><li>Are you sure you have the correct username and password?</li><li>Are you sure that you have typed the correct database hostname?</li><li>Are you sure that the database server is running?</li></ul>For more help, see the <a href="http://drupal.org/getting-started/install">installation handbook</a>. If you are unsure what these terms mean you should probably contact your hosting provider.', array('%error' => $e->getMessage(), '%name' => $this->name())));
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Run SQL tests to ensure the database can execute commands with the current user.
   */
  protected function runTestQuery($query, $pass, $fail, $fatal = FALSE) {
    try {
      db_query($query);
      $this->pass(st($pass));
    }
    catch (Exception $e) {
      $this->fail(st($fail, array('%query' => $query, '%error' => $e->getMessage(), '%name' => $this->name())));
      return !$fatal;
    }
  }
}
/**
 * @class Exception class used to throw error if the DatabaseInstaller fails.
 */
class DatabaseTaskException extends Exception {
}

/**
 * Replace values in settings.php with values in the submitted array.
 *
 * @param $settings
 *   An array of settings that need to be updated.
 */
function drupal_rewrite_settings($settings = array(), $prefix = '') {
  $default_settings = 'sites/default/default.settings.php';
  drupal_static_reset('conf_path');
  $settings_file = conf_path(FALSE) . '/' . $prefix . 'settings.php';

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
      throw new Exception(st('Failed to modify %settings. Verify the file permissions.', array('%settings' => $settings_file)));
    }
  }
  else {
    throw new Exception(st('Failed to open %settings. Verify the file permissions.', array('%settings' => $default_settings)));
  }
}

/**
 * Verify an install profile for installation.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 * @return
 *   The list of modules to install.
 */
function drupal_verify_profile($install_state) {
  $profile = $install_state['parameters']['profile'];
  $locale = $install_state['parameters']['locale'];

  include_once DRUPAL_ROOT . '/includes/file.inc';
  include_once DRUPAL_ROOT . '/includes/common.inc';

  $profile_file = DRUPAL_ROOT . "/profiles/$profile/$profile.profile";

  if (!isset($profile) || !file_exists($profile_file)) {
    throw new Exception(install_no_profile_error());
  }
  $info = $install_state['profile_info'];

  // Get a list of modules that exist in Drupal's assorted subdirectories.
  $present_modules = array();
  foreach (drupal_system_listing('/\.module$/', 'modules', 'name', 0) as $present_module) {
    $present_modules[] = $present_module->name;
  }

  // The install profile is also a module, which needs to be installed after all the other dependencies
  // have been installed.
  $present_modules[] = drupal_get_profile();

  // Verify that all of the profile's required modules are present.
  $missing_modules = array_diff($info['dependencies'], $present_modules);

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
      'description' => st('The following modules are required but were not found. Move them into the appropriate modules subdirectory, such as <em>sites/all/modules</em>. Missing modules: !modules', array('!modules' => implode(', ', $modules))),
    );
  }
  return $requirements;
}

/**
 * Callback to install the system module.
 *
 * Separated from the installation of other modules so core system
 * functions can be made available while other modules are installed.
 */
function drupal_install_system() {
  $system_path = drupal_get_path('module', 'system');
  require_once DRUPAL_ROOT . '/' . $system_path . '/system.install';
  module_invoke('system', 'install');

  $system_versions = drupal_get_schema_versions('system');
  $system_version = $system_versions ? max($system_versions) : SCHEMA_INSTALLED;
  db_insert('system')
    ->fields(array('filename', 'name', 'type', 'owner', 'status', 'schema_version', 'bootstrap'))
    ->values(array(
        'filename' => $system_path . '/system.module',
        'name' => 'system',
        'type' => 'module',
        'owner' => '',
        'status' => 1,
        'schema_version' => $system_version,
        'bootstrap' => 0,
      ))
    ->execute();
  system_rebuild_module_data();
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
    drupal_uninstall_schema($module);

    watchdog('system', '%module module uninstalled.', array('%module' => $module), WATCHDOG_INFO);

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

      $result = db_select('menu_links')
        ->fields('menu_links')
        ->condition('router_path', $paths, 'IN')
        ->condition('external', 0)
        ->orderBy('depth')
        ->execute();
      // Remove all such items. Starting from those with the greatest depth will
      // minimize the amount of re-parenting done by menu_link_delete().
      foreach ($result as $item) {
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

  if (@drupal_mkdir($file, intval("0$mod", 8))) {
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
  include_once DRUPAL_ROOT . '/includes/common.inc';
  header('Location: ' . $base_url . '/' . $path);
  header('Cache-Control: no-cache'); // Not a permanent redirect.
  drupal_exit();
}

/**
 * Functional equivalent of t(), used when some systems are not available.
 *
 * Used during the install process, when database, theme, and localization
 * system is possibly not yet available.
 *
 * @see t()
 * @ingroup sanitization
 */
function st($string, array $args = array(), array $options = array()) {
  static $locale_strings = NULL;
  global $install_state;

  if (empty($options['context'])) {
    $options['context'] = '';
  }

  if (!isset($locale_strings)) {
    $locale_strings = array();
    if (isset($install_state['parameters']['profile']) && isset($install_state['parameters']['locale'])) {
      $filename = 'profiles/' . $install_state['parameters']['profile'] . '/translations/' . $install_state['parameters']['locale'] . '.po';
      if (file_exists(DRUPAL_ROOT . '/' . $filename)) {
        require_once DRUPAL_ROOT . '/includes/locale.inc';
        $file = (object) array('uri' => $filename);
        _locale_import_read_po('mem-store', $file);
        $locale_strings = _locale_import_one_string('mem-report');
      }
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
  return strtr((!empty($locale_strings[$options['context']][$string]) ? $locale_strings[$options['context']][$string] : $string), $args);
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
  module_load_install($module);
  if (module_hook($module, 'requirements')) {
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

/**
 * Retrieve info about an install profile from its .info file.
 *
 * The information stored in a profile .info file is similar to that stored in
 * a normal Drupal module .info file. For example:
 * - name: The real name of the install profile for display purposes.
 * - description: A brief description of the profile.
 * - dependencies: An array of shortnames of other modules this install profile requires.
 *
 * Additional, less commonly-used information that can appear in a profile.info
 * file but not in a normal Drupal module .info file includes:
 * - distribution_name: The name of the Drupal distribution that is being
 *   installed, to be shown throughout the installation process. Defaults to
 *   'Drupal'.
 *
 * Example of .info file:
 * @code
 *    name = Minimal
 *    description = Start fresh, with only a few modules enabled.
 *    dependencies[] = block
 *    dependencies[] = dblog
 * @endcode
 *
 * @param profile
 *   Name of profile.
 * @param locale
 *   Name of locale used (if any).
 * @return
 *   The info array.
 */
function install_profile_info($profile, $locale = 'en') {
  $cache = &drupal_static(__FUNCTION__, array());

  if (!isset($cache[$profile])) {
    // Set defaults for module info.
    $defaults = array(
      'dependencies' => array(),
      'description' => '',
      'distribution_name' => 'Drupal',
      'version' => NULL,
      'php' => DRUPAL_MINIMUM_PHP,
    );
    $info = drupal_parse_info_file("profiles/$profile/$profile.info") + $defaults;
    $info['dependencies'] = array_unique(array_merge(
      drupal_required_modules(),
      $info['dependencies'],
      ($locale != 'en' && !empty($locale) ? array('locale') : array()))
    );

    // drupal_required_modules() includes the current profile as a dependency.
    // Since a module can't depend on itself we remove that element of the array.
    array_shift($info['dependencies']);

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
  $task_class = 'DatabaseTasks_' . $driver;
  $DatabaseTasks = new $task_class();
  $DatabaseTasks->runTasks();
  return true;
}
