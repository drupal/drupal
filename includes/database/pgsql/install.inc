<?php
// $Id: install.inc,v 1.6 2010/01/30 07:59:24 dries Exp $

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

      // Don't use {} around pg_proc table.
      if (!db_query("SELECT COUNT(*) FROM pg_proc WHERE proname = 'concat'")->fetchField()) {
        db_query('CREATE OR REPLACE FUNCTION "concat"(text, text) RETURNS text AS
          \'SELECT $1 || $2;\'
          LANGUAGE \'sql\''
        );
      }
      db_query('CREATE OR REPLACE FUNCTION "if"(boolean, text, text) RETURNS text AS
        \'SELECT CASE WHEN $1 THEN $2 ELSE $3 END;\'
        LANGUAGE \'sql\''
      );
      db_query('CREATE OR REPLACE FUNCTION "if"(boolean, integer, integer) RETURNS integer AS
        \'SELECT CASE WHEN $1 THEN $2 ELSE $3 END;\'
        LANGUAGE \'sql\''
      );

      db_query('CREATE OR REPLACE FUNCTION "substring_index"(text, text, integer) RETURNS text AS
        \'SELECT array_to_string((string_to_array($1, $2)) [1:$3], $2);\'
        LANGUAGE \'sql\''
      );
      $this->pass(st('PostgreSQL has initialized itself.'));
    }
    catch (Exception $e) {
      $this->fail(st('Drupal could not be correctly setup with the existing database. Revise any errors.'));
    }
  }
}

