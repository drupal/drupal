<?php
// $Id: install.inc,v 1.7 2010/11/26 21:36:43 webchick Exp $

/**
 * @file
 * Installation code for MySQL embedded database engine.
 */

/**
 * Specifies installation tasks for MySQL and equivalent databases.
 */
class DatabaseTasks_mysql extends DatabaseTasks {
  /**
   * The PDO driver name for MySQL and equivalent databases.
   *
   * @var string
   */
  protected $pdoDriver = 'mysql';

  /**
   * Returns a human-readable name string for MySQL and equivalent databases.
   */
  public function name() {
    return st('MySQL, MariaDB, or equivalent');
  }

  /**
   * Returns the minimum version for MySQL.
   */
  public function minimumVersion() {
    return '5.0.15';
  }
}

