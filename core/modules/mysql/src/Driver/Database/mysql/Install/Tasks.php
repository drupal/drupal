<?php

namespace Drupal\mysql\Driver\Database\mysql\Install;

use Drupal\Core\Database\ConnectionNotDefinedException;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Install\Tasks as InstallTasks;
use Drupal\mysql\Driver\Database\mysql\Connection;
use Drupal\Core\Database\DatabaseNotFoundException;

/**
 * Specifies installation tasks for MySQL and equivalent databases.
 */
class Tasks extends InstallTasks {

  /**
   * Minimum required MySQL version.
   *
   * 5.7.8 is the minimum version that supports the JSON datatype.
   * @see https://dev.mysql.com/doc/refman/5.7/en/json.html
   */
  const MYSQL_MINIMUM_VERSION = '5.7.8';

  /**
   * Minimum required MariaDB version.
   *
   * 10.3.7 is the first stable (GA) release in the 10.3 series.
   * @see https://mariadb.com/kb/en/changes-improvements-in-mariadb-103/#list-of-all-mariadb-103-releases
   */
  const MARIADB_MINIMUM_VERSION = '10.3.7';

  /**
   * The PDO driver name for MySQL and equivalent databases.
   *
   * @var string
   */
  protected $pdoDriver = 'mysql';

  /**
   * Constructs a \Drupal\mysql\Driver\Database\mysql\Install\Tasks object.
   */
  public function __construct() {
    $this->tasks[] = [
      'arguments' => [],
      'function' => 'ensureInnoDbAvailable',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function name() {
    try {
      if (!$this->isConnectionActive() || !$this->getConnection() instanceof Connection) {
        throw new ConnectionNotDefinedException('The database connection is not active or not a MySql connection');
      }
      if ($this->getConnection()->isMariaDb()) {
        return $this->t('MariaDB');
      }
      return $this->t('MySQL, Percona Server, or equivalent');
    }
    catch (ConnectionNotDefinedException $e) {
      return $this->t('MySQL, MariaDB, Percona Server, or equivalent');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function minimumVersion() {
    if ($this->getConnection()->isMariaDb()) {
      return static::MARIADB_MINIMUM_VERSION;
    }
    return static::MYSQL_MINIMUM_VERSION;
  }

  /**
   * {@inheritdoc}
   */
  protected function connect() {
    try {
      // This doesn't actually test the connection.
      Database::setActiveConnection();
      // Now actually do a check.
      Database::getConnection();
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
          Database::closeConnection();

          // Now, restore the database config.
          Database::removeConnection('default');
          $connection_info['default']['database'] = $database;
          Database::addConnectionInfo('default', 'default', $connection_info['default']);

          // Check the database connection.
          Database::getConnection();
          $this->pass('Drupal can CONNECT to the database ok.');
        }
        catch (DatabaseNotFoundException $e) {
          // Still no dice; probably a permission issue. Raise the error to the
          // installer.
          $this->fail(t('Database %database not found. The server reports the following message when attempting to create the database: %error.', ['%database' => $database, '%error' => $e->getMessage()]));
        }
      }
      else {
        // Database connection failed for some other reason than a non-existent
        // database.
        $this->fail(t('Failed to connect to your database server. The server reports the following message: %error.<ul><li>Is the database server running?</li><li>Does the database exist or does the database user have sufficient privileges to create the database?</li><li>Have you entered the correct database name?</li><li>Have you entered the correct username and password?</li><li>Have you entered the correct database hostname and port number?</li></ul>', ['%error' => $e->getMessage()]));
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
    $form['advanced_options']['isolation_level'] = [
      '#type' => 'select',
      '#title' => $this->t('Transaction isolation level'),
      '#options' => [
        'READ COMMITTED' => $this->t('READ COMMITTED'),
        'REPEATABLE READ' => $this->t('REPEATABLE READ'),
        '' => $this->t('Use database default'),
      ],
      '#default_value' => $database['isolation_level'] ?? 'READ COMMITTED',
      '#description' => $this->t('The recommended database transaction level for Drupal is "READ COMMITTED". For more information, see the <a href=":performance_doc">setting MySQL transaction isolation level</a> page.', [
        ':performance_doc' => 'https://www.drupal.org/docs/system-requirements/setting-the-mysql-transaction-isolation-level',
      ]),
    ];

    return $form;
  }

  /**
   * Ensure that InnoDB is available.
   */
  public function ensureInnoDbAvailable() {
    $engines = Database::getConnection()->query('SHOW ENGINES')->fetchAllKeyed();
    if (isset($engines['MyISAM']) && $engines['MyISAM'] == 'DEFAULT' && !isset($engines['InnoDB'])) {
      $this->fail(t('The MyISAM storage engine is not supported.'));
    }
  }

}
