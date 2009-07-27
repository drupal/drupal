<?php
// $Id$

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

