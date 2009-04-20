<?php
// $Id: install.inc,v 1.2 2009/04/20 20:02:30 dries Exp $

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

