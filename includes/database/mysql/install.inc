<?php
// $Id: install.inc,v 1.3 2009/07/27 19:42:56 dries Exp $

/**
 * @file
 * Installation code for MySQL embedded database engine.
 */


// MySQL specific install functions

class DatabaseTasks_mysql extends DatabaseTasks {
  protected $pdoDriver = 'mysql';
  public function name() {
    return 'MySQL';
  }
}

