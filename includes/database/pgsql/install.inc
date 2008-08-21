<?php
// $Id$

// PostgreSQL specific install functions

class DatabaseInstaller_pgsql extends DatabaseInstaller {
  protected $pdoDriver = 'pgsql';
  public function name() {
    return 'PostgreSQL';
  }
}

