<?php

/**
 * @file
 * Definition of Drupal\Core\Updater\UpdaterInterface.
 */

namespace Drupal\Core\Updater;

/**
 * Defines an interface for a class which can update a Drupal project.
 *
 * An Updater currently serves the following purposes:
 *   - It can take a given directory, and determine if it can operate on it.
 *   - It can move the contents of that directory into the appropriate place
 *     on the system using FileTransfer classes.
 *   - It can return a list of "next steps" after an update or install.
 *   - In the future, it will most likely perform some of those steps as well.
 */
interface UpdaterInterface {

  /**
   * Checks if the project is installed.
   *
   * @return bool
   */
  public function isInstalled();

  /**
   * Returns the system name of the project.
   *
   * @param string $directory
   *  A directory containing a project.
   */
  public static function getProjectName($directory);

  /**
   * Returns the path to the default install location.
   *
   * @return string
   *   An absolute path to the default install location.
   */
  public function getInstallDirectory();

  /**
   * Determines if the Updater can handle the project provided in $directory.
   *
   * @param string $directory
   *
   * @return bool
   *   TRUE if the project is installed, FALSE if not.
   */
  public static function canUpdateDirectory($directory);

  /**
   * Actions to run after an install has occurred.
   */
  public function postInstall();

  /**
   * Actions to run after an update has occurred.
   */
  public function postUpdate();
}
