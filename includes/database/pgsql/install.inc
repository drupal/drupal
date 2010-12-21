<?php
// $Id$

/**
 * @file
 * Install functions for PostgreSQL embedded database engine.
 */


// PostgreSQL specific install functions

class DatabaseTasks_pgsql extends DatabaseTasks {
  protected $pdoDriver = 'pgsql';

  public function __construct() {
    $this->tasks[] = array(
      'function' => 'checkEncoding',
      'arguments' => array(),
    );
    $this->tasks[] = array(
      'function' => 'checkPHPVersion',
      'arguments' => array(),
    );
    $this->tasks[] = array(
      'function' => 'checkBinaryOutput',
      'arguments' => array(),
    );
    $this->tasks[] = array(
      'function' => 'initializeDatabase',
      'arguments' => array(),
    );
  }

  public function name() {
    return st('PostgreSQL');
  }

  public function minimumVersion() {
    return '8.3';
  }

  /**
   * Check encoding is UTF8.
   */
  protected function checkEncoding() {
    try {
      if (db_query('SHOW server_encoding')->fetchField() == 'UTF8') {
        $this->pass(st('Database is encoded in UTF-8'));
      }
      else {
        $replacements = array(
          '%encoding' => 'UTF8',
          '%driver' => $this->name(),
          '!link' => '<a href="INSTALL.pgsql.txt">INSTALL.pgsql.txt</a>'
        );
        $text  = 'The %driver database must use %encoding encoding to work with Drupal.';
        $text .= 'Recreate the database with %encoding encoding. See !link for more details.';
        $this->fail(st($text, $replacements));
      }
    }
    catch (Exception $e) {
      $this->fail(st('Drupal could not determine the encoding of the database was set to UTF-8'));
    }
  }

  /**
   * Check PHP version.
   *
   * There are two bugs in PDO_pgsql affecting Drupal:
   *
   * - in versions < 5.2.7, PDO_pgsql refuses to insert an empty string into
   *   a NOT NULL BLOB column. See: http://bugs.php.net/bug.php?id=46249
   * - in versions < 5.2.11 and < 5.3.1 that prevents inserting integer values
   *   into numeric columns that exceed the PHP_INT_MAX value.
   *   See: http://bugs.php.net/bug.php?id=48924
   */
  function checkPHPVersion() {
    if (!version_compare(PHP_VERSION, '5.2.11', '>=') || (version_compare(PHP_VERSION, '5.3.0', '>=') && !version_compare(PHP_VERSION, '5.3.1', '>='))) {
      $this->fail(st('The version of PHP you are using has known issues with PostgreSQL. You need to upgrade PHP to 5.2.11, 5.3.1 or greater.'));
    };
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
        catch (Exception $e) {
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
            '!query' => "<code>" . $query . "</code>",
          );
          $this->fail(st("The %setting setting is currently set to '%current_value', but needs to be '%needed_value'. Change this by running the following query: !query", $replacements));
        }
      }
    }
  }

  /**
   * Verify that a binary data roundtrip returns the original string.
   */
  protected function checkBinaryOutputSuccess() {
    $bytea_output = db_query("SELECT 'encoding'::bytea AS output")->fetchField();
    return ($bytea_output == 'encoding');
  }

  /**
   * Make PostgreSQL Drupal friendly.
   */
  function initializeDatabase() {
    // We create some functions using global names instead of prefixing them
    // like we do with table names. This is so that we don't double up if more
    // than one instance of Drupal is running on a single database. We therefore
    // avoid trying to create them again in that case.

    try {
      // Create functions.
      db_query('CREATE OR REPLACE FUNCTION "greatest"(numeric, numeric) RETURNS numeric AS
        \'SELECT CASE WHEN (($1 > $2) OR ($2 IS NULL)) THEN $1 ELSE $2 END;\'
        LANGUAGE \'sql\''
      );
      db_query('CREATE OR REPLACE FUNCTION "greatest"(numeric, numeric, numeric) RETURNS numeric AS
        \'SELECT greatest($1, greatest($2, $3));\'
        LANGUAGE \'sql\''
      );
      // Don't use {} around pg_proc table.
      if (!db_query("SELECT COUNT(*) FROM pg_proc WHERE proname = 'rand'")->fetchField()) {
        db_query('CREATE OR REPLACE FUNCTION "rand"() RETURNS float AS
          \'SELECT random();\'
          LANGUAGE \'sql\''
        );
      }

      db_query('CREATE OR REPLACE FUNCTION "substring_index"(text, text, integer) RETURNS text AS
        \'SELECT array_to_string((string_to_array($1, $2)) [1:$3], $2);\'
        LANGUAGE \'sql\''
      );

      // Using || to concatenate in Drupal is not recommeneded because there are
      // database drivers for Drupal that do not support the syntax, however
      // they do support CONCAT(item1, item2) which we can replicate in
      // PostgreSQL. PostgreSQL requires the function to be defined for each
      // different argument variation the function can handle.
      db_query('CREATE OR REPLACE FUNCTION "concat"(anynonarray, anynonarray) RETURNS text AS
        \'SELECT CAST($1 AS text) || CAST($2 AS text);\'
        LANGUAGE \'sql\'
      ');
      db_query('CREATE OR REPLACE FUNCTION "concat"(text, anynonarray) RETURNS text AS
        \'SELECT $1 || CAST($2 AS text);\'
        LANGUAGE \'sql\'
      ');
      db_query('CREATE OR REPLACE FUNCTION "concat"(anynonarray, text) RETURNS text AS
        \'SELECT CAST($1 AS text) || $2;\'
        LANGUAGE \'sql\'
      ');
      db_query('CREATE OR REPLACE FUNCTION "concat"(text, text) RETURNS text AS
        \'SELECT $1 || $2;\'
        LANGUAGE \'sql\'
      ');

      $this->pass(st('PostgreSQL has initialized itself.'));
    }
    catch (Exception $e) {
      $this->fail(st('Drupal could not be correctly setup with the existing database. Revise any errors.'));
    }
  }
}

