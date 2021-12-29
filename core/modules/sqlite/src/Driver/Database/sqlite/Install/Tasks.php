<?php

namespace Drupal\sqlite\Driver\Database\sqlite\Install;

use Drupal\Core\Database\Database;
use Drupal\sqlite\Driver\Database\sqlite\Connection;
use Drupal\Core\Database\DatabaseNotFoundException;
use Drupal\Core\Database\Install\Tasks as InstallTasks;

/**
 * Specifies installation tasks for SQLite databases.
 */
class Tasks extends InstallTasks {

  /**
   * Minimum required SQLite version.
   *
   * Use to build sqlite library with json1 option for JSON datatype support.
   * @see https://www.sqlite.org/json1.html
   */
  const SQLITE_MINIMUM_VERSION = '3.26';

  /**
   * {@inheritdoc}
   */
  protected $pdoDriver = 'sqlite';

  /**
   * {@inheritdoc}
   */
  public function name() {
    return t('SQLite');
  }

  /**
   * {@inheritdoc}
   */
  public function minimumVersion() {
    return static::SQLITE_MINIMUM_VERSION;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormOptions(array $database) {
    $form = parent::getFormOptions($database);

    // Remove the options that only apply to client/server style databases.
    unset($form['username'], $form['password'], $form['advanced_options']['host'], $form['advanced_options']['port']);

    // Make the text more accurate for SQLite.
    $form['database']['#title'] = t('Database file');
    $form['database']['#description'] = t('The absolute path to the file where @drupal data will be stored. This must be writable by the web server and should exist outside of the web root.', ['@drupal' => drupal_install_profile_distribution_name()]);
    $default_database = \Drupal::getContainer()->getParameter('site.path') . '/files/.ht.sqlite';
    $form['database']['#default_value'] = empty($database['database']) ? $default_database : $database['database'];
    return $form;
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

        // We cannot use \Drupal::service('file_system')->getTempDirectory()
        // here because we haven't yet successfully connected to the database.
        $connection_info['default']['database'] = \Drupal::service('file_system')->tempnam(sys_get_temp_dir(), 'sqlite');

        // In order to change the Database::$databaseInfo array, need to remove
        // the active connection, then re-add it with the new info.
        Database::removeConnection('default');
        Database::addConnectionInfo('default', 'default', $connection_info['default']);

        try {
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
          $this->fail(t('Failed to open or create database file %database. The database engine reports the following message when attempting to create the database: %error.', ['%database' => $database, '%error' => $e->getMessage()]));
        }
      }
      else {
        // Database connection failed for some other reason than a non-existent
        // database.
        $this->fail(t('Failed to connect to database. The database engine reports the following message: %error.<ul><li>Does the database file exist?</li><li>Does web server have permission to write to the database file?</li>Does the web server have permission to write to the directory the database file should be created in?</li></ul>', ['%error' => $e->getMessage()]));
        return FALSE;
      }
    }
    return TRUE;
  }

}
