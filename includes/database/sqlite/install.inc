<?php
// $Id: install.inc,v 1.6 2010/11/29 02:55:57 webchick Exp $

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

  public function getFormOptions($database) {
    $form = parent::getFormOptions($database);

    // Remove the options that only apply to client/server style databases.
    unset($form['username'], $form['password'], $form['advanced_options']['host'], $form['advanced_options']['port']);

    // Make the text more accurate for SQLite.
    $form['database']['#title'] = st('Database file');
    $form['database']['#description'] = st('The absolute path to the file where @drupal data will be stored. This must be writable by the web server and should exist outside of the web root.', array('@drupal' => drupal_install_profile_distribution_name()));
    $default_database = conf_path(FALSE, TRUE) . '/files/.ht.sqlite';
    $form['database']['#default_value'] = empty($database['database']) ? $default_database : $database['database'];
    return $form;
  }

  public function validateDatabaseSettings($database) {
    // Perform standard validation.
    $errors = parent::validateDatabaseSettings($database);

    // Verify the database is writable.
    $db_directory = new SplFileInfo(dirname($database['database']));
    if (!$db_directory->isWritable()) {
      $errors[$database['driver'] . '][database'] = st('The directory you specified is not writable by the web server.');
    }

    return $errors;
  }
}

