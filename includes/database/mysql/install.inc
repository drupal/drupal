<?php
// $Id: install.inc,v 1.1 2008/08/21 19:36:36 dries Exp $

// MySQL specific install functions

class DatabaseInstaller_mysql extends DatabaseInstaller {
  protected $pdoDriver = 'mysql';
  public function name() {
    return 'MySQL';
  }
}

