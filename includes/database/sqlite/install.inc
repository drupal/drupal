<?php
// $Id: install.inc,v 1.3 2010/11/13 14:08:47 dries Exp $

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

