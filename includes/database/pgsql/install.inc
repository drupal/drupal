<?php
// $Id: install.inc,v 1.1 2008/08/21 19:36:36 dries Exp $

// PostgreSQL specific install functions

class DatabaseInstaller_pgsql extends DatabaseInstaller {
  protected $pdoDriver = 'pgsql';
  public function name() {
    return 'PostgreSQL';
  }
}

