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
        $text .= 'Please recreate the database with %encoding encoding. See !link for more details.';
        $this->fail(st($text, $replacements));
      }
    } catch (Exception $e) {
      $this->fail(st('Drupal could not determine the encoding of the database was set to UTF-8'));
    }
  }
}

