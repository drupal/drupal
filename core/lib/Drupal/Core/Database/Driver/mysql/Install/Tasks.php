<?php

namespace Drupal\Core\Database\Driver\mysql\Install;

use Drupal\Core\Database\Install\Tasks as InstallTasks;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Database\DatabaseNotFoundException;

/**
 * Specifies installation tasks for MySQL and equivalent databases.
 */
class Tasks extends InstallTasks {

  /**
   * Minimum required MySQLnd version.
   */
  const MYSQLND_MINIMUM_VERSION = '5.0.9';

  /**
   * Minimum required libmysqlclient version.
   */
  const LIBMYSQLCLIENT_MINIMUM_VERSION = '5.5.3';

  /**
   * The PDO driver name for MySQL and equivalent databases.
   *
   * @var string
   */
  protected $pdoDriver = 'mysql';

  /**
   * Constructs a \Drupal\Core\Database\Driver\mysql\Install\Tasks object.
   */
  public function __construct() {
    $this->tasks[] = array(
      'arguments' => array(),
      'function' => 'ensureInnoDbAvailable',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function name() {
    return t('MySQL, MariaDB, Percona Server, or equivalent');
  }

  /**
   * {@inheritdoc}
   */
  public function minimumVersion() {
    return '5.5.3';
  }

  /**
   * {@inheritdoc}
   */
  protected function connect() {
    try {
      // This doesn't actually test the connection.
      db_set_active();
      // Now actually do a check.
      try {
        Database::getConnection();
      }
      catch (\Exception $e) {
        // Detect utf8mb4 incompability.
        if ($e->getCode() == Connection::UNSUPPORTED_CHARSET || ($e->getCode() == Connection::SQLSTATE_SYNTAX_ERROR && $e->errorInfo[1] == Connection::UNKNOWN_CHARSET)) {
          $this->fail(t('Your MySQL server and PHP MySQL driver must support utf8mb4 character encoding. Make sure to use a database system that supports this (such as MySQL/MariaDB/Percona 5.5.3 and up), and that the utf8mb4 character set is compiled in. See the <a href=":documentation" target="_blank">MySQL documentation</a> for more information.', array(':documentation' => 'https://dev.mysql.com/doc/refman/5.0/en/cannot-initialize-character-set.html')));
          $info = Database::getConnectionInfo();
          $info_copy = $info;
          // Set a flag to fall back to utf8. Note: this flag should only be
          // used here and is for internal use only.
          $info_copy['default']['_dsn_utf8_fallback'] = TRUE;
          // In order to change the Database::$databaseInfo array, we need to
          // remove the active connection, then re-add it with the new info.
          Database::removeConnection('default');
          Database::addConnectionInfo('default', 'default', $info_copy['default']);
          // Connect with the new database info, using the utf8 character set so
          // that we can run the checkEngineVersion test.
          Database::getConnection();
          // Revert to the old settings.
          Database::removeConnection('default');
          Database::addConnectionInfo('default', 'default', $info['default']);
        }
        else {
          // Rethrow the exception.
          throw $e;
        }
      }
      $this->pass('Drupal can CONNECT to the database ok.');
    }
    catch (\Exception $e) {
      // Attempt to create the database if it is not found.
      if ($e->getCode() == Connection::DATABASE_NOT_FOUND) {
        // Remove the database string from connection info.
        $connection_info = Database::getConnectionInfo();
        $database = $connection_info['default']['database'];
        unset($connection_info['default']['database']);

        // In order to change the Database::$databaseInfo array, need to remove
        // the active connection, then re-add it with the new info.
        Database::removeConnection('default');
        Database::addConnectionInfo('default', 'default', $connection_info['default']);

        try {
          // Now, attempt the connection again; if it's successful, attempt to
          // create the database.
          Database::getConnection()->createDatabase($database);
        }
        catch (DatabaseNotFoundException $e) {
          // Still no dice; probably a permission issue. Raise the error to the
          // installer.
          $this->fail(t('Database %database not found. The server reports the following message when attempting to create the database: %error.', array('%database' => $database, '%error' => $e->getMessage())));
        }
      }
      else {
        // Database connection failed for some other reason than the database
        // not existing.
        $this->fail(t('Failed to connect to your database server. The server reports the following message: %error.<ul><li>Is the database server running?</li><li>Does the database exist or does the database user have sufficient privileges to create the database?</li><li>Have you entered the correct database name?</li><li>Have you entered the correct username and password?</li><li>Have you entered the correct database hostname?</li></ul>', array('%error' => $e->getMessage())));
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormOptions(array $database) {
    $form = parent::getFormOptions($database);
    if (empty($form['advanced_options']['port']['#default_value'])) {
      $form['advanced_options']['port']['#default_value'] = '3306';
    }

    return $form;
  }

  /**
   * Ensure that InnoDB is available.
   */
  function ensureInnoDbAvailable() {
    $engines = Database::getConnection()->query('SHOW ENGINES')->fetchAllKeyed();
    if (isset($engines['MyISAM']) && $engines['MyISAM'] == 'DEFAULT' && !isset($engines['InnoDB'])) {
      $this->fail(t('The MyISAM storage engine is not supported.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkEngineVersion() {
    parent::checkEngineVersion();

    // Ensure that the MySQL driver supports utf8mb4 encoding.
    $version = Database::getConnection()->clientVersion();
    if (FALSE !== strpos($version, 'mysqlnd')) {
      // The mysqlnd driver supports utf8mb4 starting at version 5.0.9.
      $version = preg_replace('/^\D+([\d.]+).*/', '$1', $version);
      if (version_compare($version, self::MYSQLND_MINIMUM_VERSION, '<')) {
        $this->fail(t("The MySQLnd driver version %version is less than the minimum required version. Upgrade to MySQLnd version %mysqlnd_minimum_version or up, or alternatively switch mysql drivers to libmysqlclient version %libmysqlclient_minimum_version or up.", array('%version' => $version, '%mysqlnd_minimum_version' => self::MYSQLND_MINIMUM_VERSION, '%libmysqlclient_minimum_version' => self::LIBMYSQLCLIENT_MINIMUM_VERSION)));
      }
    }
    else {
      // The libmysqlclient driver supports utf8mb4 starting at version 5.5.3.
      if (version_compare($version, self::LIBMYSQLCLIENT_MINIMUM_VERSION, '<')) {
        $this->fail(t("The libmysqlclient driver version %version is less than the minimum required version. Upgrade to libmysqlclient version %libmysqlclient_minimum_version or up, or alternatively switch mysql drivers to MySQLnd version %mysqlnd_minimum_version or up.", array('%version' => $version, '%libmysqlclient_minimum_version' => self::LIBMYSQLCLIENT_MINIMUM_VERSION, '%mysqlnd_minimum_version' => self::MYSQLND_MINIMUM_VERSION)));
      }
    }
  }

}
