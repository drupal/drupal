<?php
// $Id: install.inc,v 1.2 2009/04/20 20:02:30 dries Exp $

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

