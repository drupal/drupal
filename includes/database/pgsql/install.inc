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
      'function' => 'initializeDatabase',
      'arguments' => array(),
    );
  }

  public function name() {
    return 'PostgreSQL';
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
    } catch (Exception $e) {
      $this->fail(st('Drupal could not determine the encoding of the database was set to UTF-8'));
    }
  }

  /**
   * Check PHP version.
   *
   * There is a bug in PHP versions < 5.2.11 and < 5.3.1 that prevents
   * PostgreSQL from inserting integer values into numeric columns that exceed
   * the PHP_INT_MAX value (value varies dependant on 32 or 64 bit CPU).
   */
  function checkPHPVersion() {
    try {
      $txn = db_transaction();
      db_query("CREATE TABLE test_php_version ( test_int INT NOT NULL )");
      // See http://bugs.php.net/bug.php?id=48924 as to why this query may 
      // fail. The error will throw an Exception so there is no need to test to
      // see if the row inserted or not.
      db_query("INSERT INTO test_php_version ( test_int ) VALUES (:big_int)", array(':big_int' => PHP_INT_MAX + 1));
      db_query("DROP TABLE test_php_version"); 
      $this->pass(st('PHP is at the correct version to run on PostgreSQL.'));
    }
    catch (Exception $e) {
      // Failing is not fatal but the user should still be warned of the
      // limitations of running PostgreSQL on the current version of PHP.
      $text  = 'The version of PHP you are using has known issues with PostgreSQL. You can see more at ';
      $text .= l('http://drupal.org/node/515310', 'http://drupal.org/node/515310') . '. ';
      $text .= 'We suggest you upgrade PHP to 5.2.11, 5.3.1 or greater. Failing to do so may result in serious data corruption later.';
      drupal_set_message(st($text), 'warning');
    }
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

