<?php

/**
 * @file
 * Definition of Drupal\Core\Database\Driver\mysql\Install\Tasks
 */

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
      'arguments' => array(
        'SET NAMES utf8mb4',
        'The %name database server supports utf8mb4 character encoding.',
        'The %name database server must support utf8mb4 character encoding to work with Drupal. Make sure to use a database server that supports utf8mb4 character encoding, such as MySQL/MariaDB/Percona versions 5.5.3 and up.',
      ),
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
}
