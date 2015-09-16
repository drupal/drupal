<?php

/**
 * @file
 * Contains \Drupal\Core\Database\Driver\pgsql\Install\Tasks.
 */

namespace Drupal\Core\Database\Driver\pgsql\Install;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Install\Tasks as InstallTasks;
use Drupal\Core\Database\Driver\pgsql\Connection;
use Drupal\Core\Database\DatabaseNotFoundException;

/**
 * Specifies installation tasks for PostgreSQL databases.
 */
class Tasks extends InstallTasks {

  /**
   * {@inheritdoc}
   */
  protected $pdoDriver = 'pgsql';

  /**
   * Constructs a \Drupal\Core\Database\Driver\pgsql\Install\Tasks object.
   */
  public function __construct() {
    $this->tasks[] = array(
      'function' => 'checkEncoding',
      'arguments' => array(),
    );
    $this->tasks[] = array(
      'function' => 'checkBinaryOutput',
      'arguments' => array(),
    );
    $this->tasks[] = array(
      'function' => 'checkStandardConformingStrings',
      'arguments' => array(),
    );
    $this->tasks[] = array(
      'function' => 'initializeDatabase',
      'arguments' => array(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function name() {
    return t('PostgreSQL');
  }

  /**
   * {@inheritdoc}
   */
  public function minimumVersion() {
    return '9.1.2';
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
          $this->fail(t('Database %database not found. The server reports the following message when attempting to create the database: %error.', array('%database' => $database, '%error' => $e->getMessage())));
        }
      }
      else {
        // Database connection failed for some other reason than the database
        // not existing.
        $this->fail(t('Failed to connect to your database server. The server reports the following message: %error.<ul><li>Is the database server running?</li><li>Does the database exist, and have you entered the correct database name?</li><li>Have you entered the correct username and password?</li><li>Have you entered the correct database hostname?</li></ul>', array('%error' => $e->getMessage())));
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Check encoding is UTF8.
   */
  protected function checkEncoding() {
    try {
      if (db_query('SHOW server_encoding')->fetchField() == 'UTF8') {
        $this->pass(t('Database is encoded in UTF-8'));
      }
      else {
        $this->fail(t('The %driver database must use %encoding encoding to work with Drupal. Recreate the database with %encoding encoding. See <a href="INSTALL.pgsql.txt">INSTALL.pgsql.txt</a> for more details.', array(
          '%encoding' => 'UTF8',
          '%driver' => $this->name(),
        )));
      }
    }
    catch (\Exception $e) {
      $this->fail(t('Drupal could not determine the encoding of the database was set to UTF-8'));
    }
  }

  /**
   * Check Binary Output.
   *
   * Unserializing does not work on Postgresql 9 when bytea_output is 'hex'.
   */
  function checkBinaryOutput() {
    // PostgreSQL < 9 doesn't support bytea_output, so verify we are running
    // at least PostgreSQL 9.
    $database_connection = Database::getConnection();
    if (version_compare($database_connection->version(), '9') >= 0) {
      if (!$this->checkBinaryOutputSuccess()) {
        // First try to alter the database. If it fails, raise an error telling
        // the user to do it themselves.
        $connection_options = $database_connection->getConnectionOptions();
        // It is safe to include the database name directly here, because this
        // code is only called when a connection to the database is already
        // established, thus the database name is guaranteed to be a correct
        // value.
        $query = "ALTER DATABASE \"" . $connection_options['database'] . "\" SET bytea_output = 'escape';";
        try {
          db_query($query);
        }
        catch (\Exception $e) {
          // Ignore possible errors when the user doesn't have the necessary
          // privileges to ALTER the database.
        }

        // Close the database connection so that the configuration parameter
        // is applied to the current connection.
        db_close();

        // Recheck, if it fails, finally just rely on the end user to do the
        // right thing.
        if (!$this->checkBinaryOutputSuccess()) {
          $replacements = array(
            '%setting' => 'bytea_output',
            '%current_value' => 'hex',
            '%needed_value' => 'escape',
            '@query' => $query,
          );
          $this->fail(t("The %setting setting is currently set to '%current_value', but needs to be '%needed_value'. Change this by running the following query: <code>@query</code>", $replacements));
        }
      }
    }
  }

  /**
   * Verify that a binary data roundtrip returns the original string.
   */
  protected function checkBinaryOutputSuccess() {
    $bytea_output = db_query("SHOW bytea_output")->fetchField();
    return ($bytea_output == 'escape');
  }

  /**
   * Ensures standard_conforming_strings setting is 'on'.
   *
   * When standard_conforming_strings setting is 'on' string literals ('...')
   * treat backslashes literally, as specified in the SQL standard. This allows
   * Drupal to convert between bytea, text and varchar columns.
   */
  public function checkStandardConformingStrings() {
    $database_connection = Database::getConnection();
    if (!$this->checkStandardConformingStringsSuccess()) {
      // First try to alter the database. If it fails, raise an error telling
      // the user to do it themselves.
      $connection_options = $database_connection->getConnectionOptions();
      // It is safe to include the database name directly here, because this
      // code is only called when a connection to the database is already
      // established, thus the database name is guaranteed to be a correct
      // value.
      $query = "ALTER DATABASE \"" . $connection_options['database'] . "\" SET standard_conforming_strings = 'on';";
      try {
        $database_connection->query($query);
      }
      catch (\Exception $e) {
        // Ignore possible errors when the user doesn't have the necessary
        // privileges to ALTER the database.
      }

      // Close the database connection so that the configuration parameter
      // is applied to the current connection.
      Database::closeConnection();

      // Recheck, if it fails, finally just rely on the end user to do the
      // right thing.
      if (!$this->checkStandardConformingStringsSuccess()) {
        $replacements = array(
          '%setting' => 'standard_conforming_strings',
          '%current_value' => 'off',
          '%needed_value' => 'on',
          '@query' => $query,
        );
        $this->fail(t("The %setting setting is currently set to '%current_value', but needs to be '%needed_value'. Change this by running the following query: <code>@query</code>", $replacements));
      }
    }
  }

  /**
   * Verifies the standard_conforming_strings setting.
   */
  protected function checkStandardConformingStringsSuccess() {
    $standard_conforming_strings = Database::getConnection()->query("SHOW standard_conforming_strings")->fetchField();
    return ($standard_conforming_strings == 'on');
  }

  /**
   * Make PostgreSQL Drupal friendly.
   */
  function initializeDatabase() {
    // We create some functions using global names instead of prefixing them
    // like we do with table names. This is so that we don't double up if more
    // than one instance of Drupal is running on a single database. We therefore
    // avoid trying to create them again in that case.
    // At the same time checking for the existence of the function fixes
    // concurrency issues, when both try to update at the same time.
    try {
      // Don't use {} around pg_proc table.
      if (!db_query("SELECT COUNT(*) FROM pg_proc WHERE proname = 'rand'")->fetchField()) {
        db_query('CREATE OR REPLACE FUNCTION "rand"() RETURNS float AS
          \'SELECT random();\'
          LANGUAGE \'sql\''
        );
      }

      if (!db_query("SELECT COUNT(*) FROM pg_proc WHERE proname = 'substring_index'")->fetchField()) {
        db_query('CREATE OR REPLACE FUNCTION "substring_index"(text, text, integer) RETURNS text AS
          \'SELECT array_to_string((string_to_array($1, $2)) [1:$3], $2);\'
          LANGUAGE \'sql\''
        );
      }

      $this->pass(t('PostgreSQL has initialized itself.'));
    }
    catch (\Exception $e) {
      $this->fail(t('Drupal could not be correctly setup with the existing database due to the following error: @error.', ['@error' => $e->getMessage()]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormOptions(array $database) {
    $form = parent::getFormOptions($database);
    if (empty($form['advanced_options']['port']['#default_value'])) {
      $form['advanced_options']['port']['#default_value'] = '5432';
    }
    return $form;
  }
}
