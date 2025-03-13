<?php

namespace Drupal\Core\Updater;

@trigger_error('The ' . __NAMESPACE__ . '\UpdaterInterface is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is no replacement. Use composer to manage the code for your site. See https://www.drupal.org/node/3512364', E_USER_DEPRECATED);

/**
 * Defines an interface for a class which can update a Drupal project.
 *
 * An Updater currently serves the following purposes:
 *   - It can take a given directory, and determine if it can operate on it.
 *   - It can move the contents of that directory into the appropriate place
 *     on the system using FileTransfer classes.
 *   - It can return a list of "next steps" after an update or install.
 *   - In the future, it will most likely perform some of those steps as well.
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is no
 *   replacement. Use composer to manage the code for your site.
 *
 * @see https://www.drupal.org/node/3512364
 */
interface UpdaterInterface {

  /**
   * Checks if the project is installed.
   *
   * @return bool
   *   Return TRUE if the project is installed, FALSE otherwise.
   */
  public function isInstalled();

  /**
   * Returns the system name of the project.
   *
   * @param string $directory
   *   A directory containing a project.
   */
  public static function getProjectName($directory);

  /**
   * Returns the path to the default install location for the current project.
   *
   * @return string
   *   An absolute path to the default install location.
   */
  public function getInstallDirectory();

  /**
   * Returns the name of the root directory under which projects will be copied.
   *
   * @return string
   *   A relative path to the root directory.
   */
  public static function getRootDirectoryRelativePath();

  /**
   * Determines if the Updater can handle the project provided in $directory.
   *
   * @param string $directory
   *   The directory.
   *
   * @return bool
   *   TRUE if the project is installed, FALSE if not.
   */
  public static function canUpdateDirectory($directory);

  /**
   * Actions to run after an install has occurred.
   *
   * @deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3461934
   */
  public function postInstall();

  /**
   * Actions to run after an update has occurred.
   */
  public function postUpdate();

}
