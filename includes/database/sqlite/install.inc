<?php
// $Id$

/**
 * @file
 * SQLite specific install functions
 */

class DatabaseInstaller_sqlite extends DatabaseInstaller {
  protected $pdoDriver = 'sqlite';
  public function name() {
    return 'SQLite';
  }
}

