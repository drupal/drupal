<?php

/**
* @file
* API functions for installing modules and themes.
*/

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

  // Reset the static cache of module schema versions.
  drupal_get_installed_schema_version(NULL, TRUE);
}

/**
 * Loads the installation profile, extracting its defined distribution name.
 *
 * @return
 *   The distribution name defined in the profile's .info file. Defaults to
 *   "Drupal" if none is explicitly provided by the installation profile.
 *
 * @see install_profile_info()
 */
function drupal_install_profile_distribution_name() {
  // During installation, the profile information is stored in the global
  // installation state (it might not be saved anywhere yet).
  if (drupal_installation_attempted()) {
    global $install_state;
    return $install_state['profile_info']['distribution_name'];
  }
  // At all other times, we load the profile via standard methods.
  else {
    $profile = drupal_get_profile();
    $info = system_get_info('module', $profile);
    return $info['distribution_name'];
  }
}

/**
 * Detects the base URL using the PHP $_SERVER variables.
 *
 * @param $file
 *   The name of the file calling this function so we can strip it out of
 *   the URI when generating the base_url.
 *
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
 * Returns all supported database installer objects that are compiled into PHP.
 *
 * @return
 *  An array of database installer objects compiled into PHP.
 */
function drupal_get_database_types() {
  $databases = array();

  // We define a driver as a directory in /includes/database that in turn
  // contains a database.inc file. That allows us to drop in additional drivers
  // without modifying the installer.
  // Because we have no registry yet, we need to also include the install.inc
  // file for the driver explicitly.
  require_once DRUPAL_ROOT . '/includes/database/database.inc';
  foreach (file_scan_directory(DRUPAL_ROOT . '/includes/database', '/^[a-z]*$/i', array('recurse' => FALSE)) as $file) {
    if (file_exists($file->uri . '/database.inc') && file_exists($file->uri . '/install.inc')) {
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
      'function'    => 'checkEngineVersion',
      'arguments'   => array(),
    ),
    array(
      'arguments'   => array(
        'CREATE TABLE {drupal_install_test} (id int NULL)',
        'Drupal can use CREATE TABLE database commands.',
        'Failed to <strong>CREATE</strong> a test table on your database server with the command %query. The server reports the following message: %error.<p>Are you sure the configured username has the necessary permissions to create tables in the database?</p>',
        TRUE,
      ),
    ),
    array(
      'arguments'   => array(
        'INSERT INTO {drupal_install_test} (id) VALUES (1)',
        'Drupal can use INSERT database commands.',
        'Failed to <strong>INSERT</strong> a value into a test table on your database server. We tried inserting a value with the command %query and the server reported the following error: %error.',
      ),
    ),
    array(
      'arguments'   => array(
        'UPDATE {drupal_install_test} SET id = 2',
        'Drupal can use UPDATE database commands.',
        'Failed to <strong>UPDATE</strong> a value in a test table on your database server. We tried updating a value with the command %query and the server reported the following error: %error.',
      ),
    ),
    array(
      'arguments'   => array(
        'DELETE FROM {drupal_install_test}',
        'Drupal can use DELETE database commands.',
        'Failed to <strong>DELETE</strong> a value from a test table on your database server. We tried deleting a value with the command %query and the server reported the following error: %error.',
      ),
    ),
    array(
      'arguments'   => array(
        'DROP TABLE {drupal_install_test}',
        'Drupal can use DROP TABLE database commands.',
        'Failed to <strong>DROP</strong> a test table from your database server. We tried dropping a table with the command %query and the server reported the following error %error.',
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

  /**
   * Return the human-readable name of the driver.
   */
  abstract public function name();

  /**
   * Return the minimum required version of the engine.
   *
   * @return
   *   A version string. If not NULL, it will be checked against the version
   *   reported by the Database engine using version_compare().
   */
  public function minimumVersion() {
    return NULL;
  }

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
      $message = 'Resolve all issues below to continue the installation. For help configuring your database server, see the <a href="http://drupal.org/getting-started/install">installation handbook</a>, or contact your hosting provider.' . $message;
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
      $this->fail(st('Failed to connect to your database server. The server reports the following message: %error.<ul><li>Is the database server running?</li><li>Does the database exist, and have you entered the correct database name?</li><li>Have you entered the correct username and password?</li><li>Have you entered the correct database hostname?</li></ul>', array('%error' => $e->getMessage())));
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

  /**
   * Check the engine version.
   */
  protected function checkEngineVersion() {
    if ($this->minimumVersion() && version_compare(Database::getConnection()->version(), $this->minimumVersion(), '<')) {
      $this->fail(st("The database version %version is less than the minimum required version %minimum_version.", array('%version' => Database::getConnection()->version(), '%minimum_version' => $this->minimumVersion())));
    }
  }

  /**
   * Return driver specific configuration options.
   *
   * @param $database
   *  An array of driver specific configuration options.
   *
   * @return
   *   The options form array.
   */
  public function getFormOptions($database) {
    $form['database'] = array(
      '#type' => 'textfield',
      '#title' => st('Database name'),
      '#default_value' => empty($database['database']) ? '' : $database['database'],
      '#size' => 45,
      '#required' => TRUE,
      '#description' => st('The name of the database your @drupal data will be stored in. It must exist on your server before @drupal can be installed.', array('@drupal' => drupal_install_profile_distribution_name())),
    );

    $form['username'] = array(
      '#type' => 'textfield',
      '#title' => st('Database username'),
      '#default_value' => empty($database['username']) ? '' : $database['username'],
      '#required' => TRUE,
      '#size' => 45,
    );

    $form['password'] = array(
      '#type' => 'password',
      '#title' => st('Database password'),
      '#default_value' => empty($database['password']) ? '' : $database['password'],
      '#required' => FALSE,
      '#size' => 45,
    );

    $form['advanced_options'] = array(
      '#type' => 'fieldset',
      '#title' => st('Advanced options'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => st("These options are only necessary for some sites. If you're not sure what you should enter here, leave the default settings or check with your hosting provider."),
      '#weight' => 10,
    );

    $profile = drupal_get_profile();
    $db_prefix = ($profile == 'standard') ? 'drupal_' : $profile . '_';
    $form['advanced_options']['db_prefix'] = array(
      '#type' => 'textfield',
      '#title' => st('Table prefix'),
      '#default_value' => '',
      '#size' => 45,
      '#description' => st('If more than one application will be sharing this database, enter a table prefix such as %prefix for your @drupal site here.', array('@drupal' => drupal_install_profile_distribution_name(), '%prefix' => $db_prefix)),
      '#weight' => 10,
    );

    $form['advanced_options']['host'] = array(
      '#type' => 'textfield',
      '#title' => st('Database host'),
      '#default_value' => empty($database['host']) ? 'localhost' : $database['host'],
      '#size' => 45,
      // Hostnames can be 255 characters long.
      '#maxlength' => 255,
      '#required' => TRUE,
      '#description' => st('If your database is located on a different server, change this.'),
    );

    $form['advanced_options']['port'] = array(
      '#type' => 'textfield',
      '#title' => st('Database port'),
      '#default_value' => empty($database['port']) ? '' : $database['port'],
      '#size' => 45,
      // The maximum port number is 65536, 5 digits.
      '#maxlength' => 5,
      '#description' => st('If your database server is listening to a non-standard port, enter its number.'),
    );

    return $form;
  }

  /**
   * Validates driver specific configuration settings.
   *
   * Checks to ensure correct basic database settings and that a proper
   * connection to the database can be established.
   *
   * @param $database
   *   An array of driver specific configuration options.
   *
   * @return
   *   An array of driver configuration errors, keyed by form element name.
   */
  public function validateDatabaseSettings($database) {
    $errors = array();

    // Verify the table prefix.
    if (!empty($database['prefix']) && is_string($database['prefix']) && !preg_match('/^[A-Za-z0-9_.]+$/', $database['prefix'])) {
      $errors[$database['driver'] . '][advanced_options][db_prefix'] = st('The database table prefix you have entered, %prefix, is invalid. The table prefix can only contain alphanumeric characters, periods, or underscores.', array('%prefix' => $database['prefix']));
    }

    // Verify the database port.
    if (!empty($database['port']) && !is_numeric($database['port'])) {
      $errors[$database['driver'] . '][advanced_options][port'] =  st('Database port must be a number.');
    }

    return $errors;
  }

}

/**
 * Exception thrown if the database installer fails.
 */
class DatabaseTaskException extends Exception {
}

/**
 * Replaces values in settings.php with values in the submitted array.
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
    else {
      // The existing settings.php file might have been included already. In
      // case an opcode cache is enabled, the rewritten contents of the file
      // will not be reflected in this process. Ensure to invalidate the file
      // in case an opcode cache is enabled.
      drupal_clear_opcode_cache(DRUPAL_ROOT . '/' . $settings_file);
    }
  }
  else {
    throw new Exception(st('Failed to open %settings. Verify the file permissions.', array('%settings' => $default_settings)));
  }
}

/**
 * Verifies an installation profile for installation.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
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
  foreach (drupal_system_listing('/^' . DRUPAL_PHP_FUNCTION_PATTERN . '\.module$/', 'modules', 'name', 0) as $present_module) {
    $present_modules[] = $present_module->name;
  }

  // The installation profile is also a module, which needs to be installed
  // after all the other dependencies have been installed.
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
 * Installs the system module.
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
 * Uninstalls a given list of disabled modules.
 *
 * @param string[] $module_list
 *   The modules to uninstall. It is the caller's responsibility to ensure that
 *   all modules in this list have already been disabled before this function
 *   is called.
 * @param bool $uninstall_dependents
 *   (optional) If TRUE, the function will check that all modules which depend
 *   on the passed-in module list either are already uninstalled or contained in
 *   the list, and it will ensure that the modules are uninstalled in the
 *   correct order. This incurs a significant performance cost, so use FALSE if
 *   you know $module_list is already complete and in the correct order.
 *   Defaults to TRUE.
 *
 * @return bool
 *   Returns TRUE if the operation succeeds or FALSE if it aborts due to an
 *   unsafe condition, namely, $uninstall_dependents is TRUE and a module in
 *   $module_list has dependents which are not already uninstalled and not also
 *   included in $module_list).
 *
 * @see module_disable()
 * @see module_enable()
 */
function drupal_uninstall_modules($module_list = array(), $uninstall_dependents = TRUE) {
  if ($uninstall_dependents) {
    // Get all module data so we can find dependents and sort.
    $module_data = system_rebuild_module_data();
    // Create an associative array with weights as values.
    $module_list = array_flip(array_values($module_list));

    $profile = drupal_get_profile();
    while (list($module) = each($module_list)) {
      if (!isset($module_data[$module]) || drupal_get_installed_schema_version($module) == SCHEMA_UNINSTALLED) {
        // This module doesn't exist or is already uninstalled. Skip it.
        unset($module_list[$module]);
        continue;
      }
      $module_list[$module] = $module_data[$module]->sort;

      // If the module has any dependents which are not already uninstalled and
      // not included in the passed-in list, abort. It is not safe to uninstall
      // them automatically because uninstalling a module is a destructive
      // operation.
      foreach (array_keys($module_data[$module]->required_by) as $dependent) {
        if (!isset($module_list[$dependent]) && drupal_get_installed_schema_version($dependent) != SCHEMA_UNINSTALLED && $dependent != $profile) {
          return FALSE;
        }
      }
    }

    // Sort the module list by pre-calculated weights.
    asort($module_list);
    $module_list = array_keys($module_list);
  }

  foreach ($module_list as $module) {
    // Uninstall the module.
    module_load_install($module);
    module_invoke($module, 'uninstall');
    drupal_uninstall_schema($module);

    watchdog('system', '%module module uninstalled.', array('%module' => $module), WATCHDOG_INFO);
    drupal_set_installed_schema_version($module, SCHEMA_UNINSTALLED);
  }

  if (!empty($module_list)) {
    // Let other modules react.
    module_invoke_all('modules_uninstalled', $module_list);
  }

  return TRUE;
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
 * Sends the user to a different installer page.
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
  $query = array_merge(drupal_get_query_parameters(), $query);
  if (!empty($query)) {
    $uri .= '?' . drupal_http_build_query($query);
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
 * Translates a string when some systems are not available.
 *
 * Used during the install process, when database, theme, and localization
 * system is possibly not yet available.
 *
 * Use t() if your code will never run during the Drupal installation phase.
 * Use st() if your code will only run during installation and never any other
 * time. Use get_t() if your code could run in either circumstance.
 *
 * @see t()
 * @see get_t()
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
      // If the given locale was selected, there should be at least one .po file
      // with its name ending in {$install_state['parameters']['locale']}.po
      // This might or might not be the entire filename. It is also possible
      // that multiple files end with the same extension, even if unlikely.
      $po_files = file_scan_directory('./profiles/' . $install_state['parameters']['profile'] . '/translations', '/'. $install_state['parameters']['locale'] .'\.po$/', array('recurse' => FALSE));
      if (count($po_files)) {
        require_once DRUPAL_ROOT . '/includes/locale.inc';
        foreach ($po_files as $po_file) {
          _locale_import_read_po('mem-store', $po_file);
        }
        $locale_strings = _locale_import_one_string('mem-report');
      }
    }
  }

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
 * Checks an installation profile's requirements.
 *
 * @param $profile
 *   Name of installation profile to check.
 * @return
 *   Array of the installation profile's requirements.
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
 * Retrieves information about an installation profile from its .info file.
 *
 * The information stored in a profile .info file is similar to that stored in
 * a normal Drupal module .info file. For example:
 * - name: The real name of the installation profile for display purposes.
 * - description: A brief description of the profile.
 * - dependencies: An array of shortnames of other modules that this install
 *   profile requires.
 *
 * Additional, less commonly-used information that can appear in a profile.info
 * file but not in a normal Drupal module .info file includes:
 * - distribution_name: The name of the Drupal distribution that is being
 *   installed, to be shown throughout the installation process. Defaults to
 *   'Drupal'.
 * - exclusive: If the install profile is intended to be the only eligible
 *   choice in a distribution, setting exclusive = TRUE will auto-select it
 *   during installation, and the install profile selection screen will be
 *   skipped. If more than one profile is found where exclusive = TRUE then
 *   this property will have no effect and the profile selection screen will
 *   be shown as normal with all available profiles shown.
 *
 * Note that this function does an expensive file system scan to get info file
 * information for dependencies. If you only need information from the info
 * file itself, use system_get_info().
 *
 * Example of .info file:
 * @code
 *    name = Minimal
 *    description = Start fresh, with only a few modules enabled.
 *    dependencies[] = block
 *    dependencies[] = dblog
 * @endcode
 *
 * @param $profile
 *   Name of profile.
 * @param $locale
 *   Name of locale used (if any).
 *
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
      'hidden' => FALSE,
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
  db_installer_object($driver)->runTasks();
  return TRUE;
}

/**
 * Returns a database installer object.
 *
 * @param $driver
 *   The name of the driver.
 */
function db_installer_object($driver) {
  Database::loadDriverFile($driver, array('install.inc'));
  $task_class = 'DatabaseTasks_' . $driver;
  return new $task_class();
}
