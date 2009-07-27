<?php
// $Id: install.inc,v 1.2 2009/07/27 19:42:56 dries Exp $

/**
 * @file
 * SQLite specific install functions
 */

class DatabaseTasks_sqlite extends DatabaseTasks {
  protected $pdoDriver = 'sqlite';
  public function name() {
    return 'SQLite';
  }
}

