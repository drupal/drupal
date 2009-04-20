<?php
// $Id$

/**
 * @file
 * Installation code for MySQL embedded database engine.
 */


// MySQL specific install functions

class DatabaseInstaller_mysql extends DatabaseInstaller {
  protected $pdoDriver = 'mysql';
  public function name() {
    return 'MySQL';
  }
}

