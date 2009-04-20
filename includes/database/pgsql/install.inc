<?php
// $Id$

/**
 * @file
 * Install functions for PostgreSQL embedded database engine.
 */


// PostgreSQL specific install functions

class DatabaseInstaller_pgsql extends DatabaseInstaller {
  protected $pdoDriver = 'pgsql';
  public function name() {
    return 'PostgreSQL';
  }
}

