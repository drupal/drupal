<?php
// $Id$

/**
 * @file
 * SQLite specific install functions
 */

class DatabaseTasks_sqlite extends DatabaseTasks {
  protected $pdoDriver = 'sqlite';
  public function name() {
    return 'SQLite';
  }
  protected function minimumVersion() {
    return DRUPAL_MINIMUM_SQLITE;
  }
}

