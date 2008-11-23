<?php
// $Id: install.inc,v 1.1 2008/11/23 06:06:15 webchick Exp $

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

