<?php
// $Id: install.inc,v 1.5 2010/11/26 21:36:43 webchick Exp $

/**
 * @file
 * SQLite specific install functions
 */

class DatabaseTasks_sqlite extends DatabaseTasks {
  protected $pdoDriver = 'sqlite';

  public function name() {
    return st('SQLite');
  }

  /**
   * Minimum engine version.
   *
   * @todo: consider upping to 3.6.8 in Drupal 8 to get SAVEPOINT support.
   */
  public function minimumVersion() {
    return '3.3.7';
  }
}

