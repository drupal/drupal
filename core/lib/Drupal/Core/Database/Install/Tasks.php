<?php

/**
 * @file
 * Contains \Drupal\Core\Database\Install\Tasks.
 */

namespace Drupal\Core\Database\Install;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Database\Database;

/**
 * Database installer structure.
 *
 * Defines basic Drupal requirements for databases.
 */
abstract class Tasks {

  /**
   * The name of the PDO driver this database type requires.
   *
   * @var string
   */
  protected $pdoDriver;

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
    return in_array($this->pdoDriver, \PDO::getAvailableDrivers());
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
          throw new TaskException(t("Failed to run all tasks against the database server. The task %task wasn't found.", array('%task' => $task['function'])));
        }
      }
    }
    // Check for failed results and compile message
    $message = '';
    foreach ($this->results as $result => $success) {
      if (!$success) {
        $message = SafeMarkup::isSafe($result) ? $result : SafeMarkup::checkPlain($result);
      }
    }
    if (!empty($message)) {
      $message = SafeMarkup::set('Resolve all issues below to continue the installation. For help configuring your database server, see the <a href="https://www.drupal.org/getting-started/install">installation handbook</a>, or contact your hosting provider.' . $message);
      throw new TaskException($message);
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
    catch (\Exception $e) {
      $this->fail(t('Failed to connect to your database server. The server reports the following message: %error.<ul><li>Is the database server running?</li><li>Does the database exist, and have you entered the correct database name?</li><li>Have you entered the correct username and password?</li><li>Have you entered the correct database hostname?</li></ul>', array('%error' => $e->getMessage())));
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Run SQL tests to ensure the database can execute commands with the current user.
   */
  protected function runTestQuery($query, $pass, $fail, $fatal = FALSE) {
    try {
      Database::getConnection()->query($query);
      $this->pass(t($pass));
    }
    catch (\Exception $e) {
      $this->fail(t($fail, array('%query' => $query, '%error' => $e->getMessage(), '%name' => $this->name())));
      return !$fatal;
    }
  }

  /**
   * Check the engine version.
   */
  protected function checkEngineVersion() {
    if ($this->minimumVersion() && version_compare(Database::getConnection()->version(), $this->minimumVersion(), '<')) {
      $this->fail(t("The database version %version is less than the minimum required version %minimum_version.", array('%version' => Database::getConnection()->version(), '%minimum_version' => $this->minimumVersion())));
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
  public function getFormOptions(array $database) {
    $form['database'] = array(
      '#type' => 'textfield',
      '#title' => t('Database name'),
      '#default_value' => empty($database['database']) ? '' : $database['database'],
      '#size' => 45,
      '#required' => TRUE,
      '#states' => array(
        'required' => array(
          ':input[name=driver]' => array('value' => $this->pdoDriver),
        ),
      ),
    );

    $form['username'] = array(
      '#type' => 'textfield',
      '#title' => t('Database username'),
      '#default_value' => empty($database['username']) ? '' : $database['username'],
      '#size' => 45,
      '#required' => TRUE,
      '#states' => array(
        'required' => array(
          ':input[name=driver]' => array('value' => $this->pdoDriver),
        ),
      ),
    );

    $form['password'] = array(
      '#type' => 'password',
      '#title' => t('Database password'),
      '#default_value' => empty($database['password']) ? '' : $database['password'],
      '#required' => FALSE,
      '#size' => 45,
    );

    $form['advanced_options'] = array(
      '#type' => 'details',
      '#title' => t('Advanced options'),
      '#weight' => 10,
    );

    $profile = drupal_get_profile();
    $db_prefix = ($profile == 'standard') ? 'drupal_' : $profile . '_';
    $form['advanced_options']['prefix'] = array(
      '#type' => 'textfield',
      '#title' => t('Table name prefix'),
      '#default_value' => empty($database['prefix']) ? '' : $database['prefix'],
      '#size' => 45,
      '#description' => t('If more than one application will be sharing this database, a unique table name prefix – such as %prefix – will prevent collisions.', array('%prefix' => $db_prefix)),
      '#weight' => 10,
    );

    $form['advanced_options']['host'] = array(
      '#type' => 'textfield',
      '#title' => t('Host'),
      '#default_value' => empty($database['host']) ? 'localhost' : $database['host'],
      '#size' => 45,
      // Hostnames can be 255 characters long.
      '#maxlength' => 255,
      '#required' => TRUE,
    );

    $form['advanced_options']['port'] = array(
      '#type' => 'number',
      '#title' => t('Port number'),
      '#default_value' => empty($database['port']) ? '' : $database['port'],
      '#min' => 0,
      '#max' => 65535,
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
      $errors[$database['driver'] . '][prefix'] = t('The database table prefix you have entered, %prefix, is invalid. The table prefix can only contain alphanumeric characters, periods, or underscores.', array('%prefix' => $database['prefix']));
    }

    return $errors;
  }

}
