<?php

/**
 * @file
 * Classes used for updating various files in the Drupal webroot. These
 * classes use a FileTransfer object to actually perform the operations.
 * Normally, the FileTransfer is provided when the site owner is redirected to
 * authorize.php as part of a multistep process.
 */

/**
 * Interface for a class which can update a Drupal project.
 *
 * An Updater currently serves the following purposes:
 *   - It can take a given directory, and determine if it can operate on it.
 *   - It can move the contents of that directory into the appropriate place
 *     on the system using FileTransfer classes.
 *   - It can return a list of "next steps" after an update or install.
 *   - In the future, it will most likely perform some of those steps as well.
 */
interface DrupalUpdaterInterface {

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
   * @return string
   *   An absolute path to the default install location.
   */
  public function getInstallDirectory();

  /**
   * Determine if the Updater can handle the project provided in $directory.
   *
   * @todo: Provide something more rational here, like a project spec file.
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

/**
 * Base class for Updaters used in Drupal.
 */
class Updater {

  /**
   * @var string $source Directory to install from.
   */
  public $source;

  /**
   * @var string $name The name of the project.
   */
  protected $name;

  /**
   * @var string $title The title of the project.
   */
  protected $title;

  public function __construct($source) {
    $this->source = $source;
    $this->name = self::getProjectName($source);
    $this->title = self::getProjectTitle($source);
  }

  /**
   * Return an Updater of the appropriate type depending on the source.
   *
   * If a directory is provided which contains a module, will return a
   * ModuleUpdater.
   *
   * @param string $source
   *   Directory of a Drupal project.
   *
   * @return Updater
   */
  public static function factory($source) {
    if (is_dir($source)) {
      $updater = self::getUpdaterFromDirectory($source);
    }
    else {
      throw new UpdaterException(t('Unable to determine the type of the source directory.'));
    }
    return new $updater($source);
  }

  /**
   * Determine which Updater class can operate on the given directory.
   *
   * @param string $directory
   *   Extracted Drupal project.
   *
   * @return string
   *   The class name which can work with this project type.
   */
  public static function getUpdaterFromDirectory($directory) {
    // Gets a list of possible implementing classes.
    $updaters = drupal_get_updaters();
    foreach ($updaters as $updater) {
      $class = $updater['class'];
      if (call_user_func(array($class, 'canUpdateDirectory'), $directory)) {
        return $class;
      }
    }
    throw new UpdaterException(t('Cannot determine the type of project.'));
  }

  /**
   * Figure out what the most important (or only) info file is in a directory.
   *
   * Since there is no enforcement of which info file is the project's "main"
   * info file, this will get one with the same name as the directory, or the
   * first one it finds.  Not ideal, but needs a larger solution.
   *
   * @param string $directory
   *   Directory to search in.
   *
   * @return string
   *   Path to the info file.
   */
  public static function findInfoFile($directory) {
    $info_files = file_scan_directory($directory, '/.*\.info$/');
    if (!$info_files) {
      return FALSE;
    }
    foreach ($info_files as $info_file) {
      if (drupal_substr($info_file->filename, 0, -5) == drupal_basename($directory)) {
        // Info file Has the same name as the directory, return it.
        return $info_file->uri;
      }
    }
    // Otherwise, return the first one.
    $info_file = array_shift($info_files);
    return $info_file->uri;
  }

  /**
   * Get the name of the project directory (basename).
   *
   * @todo: It would be nice, if projects contained an info file which could
   *        provide their canonical name.
   *
   * @param string $directory
   *
   * @return string
   *   The name of the project.
   */
  public static function getProjectName($directory) {
    return drupal_basename($directory);
  }

  /**
   * Return the project name from a Drupal info file.
   *
   * @param string $directory
   *   Directory to search for the info file.
   *
   * @return string
   *   The title of the project.
   */
  public static function getProjectTitle($directory) {
    $info_file = self::findInfoFile($directory);
    $info = drupal_parse_info_file($info_file);
    if (empty($info)) {
      throw new UpdaterException(t('Unable to parse info file: %info_file.', array('%info_file' => $info_file)));
    }
    if (empty($info['name'])) {
      throw new UpdaterException(t("The info file (%info_file) does not define a 'name' attribute.", array('%info_file' => $info_file)));
    }
    return $info['name'];
  }

  /**
   * Store the default parameters for the Updater.
   *
   * @param array $overrides
   *   An array of overrides for the default parameters.
   *
   * @return array
   *   An array of configuration parameters for an update or install operation.
   */
  protected function getInstallArgs($overrides = array()) {
    $args = array(
      'make_backup' => FALSE,
      'install_dir' => $this->getInstallDirectory(),
      'backup_dir'  => $this->getBackupDir(),
    );
    return array_merge($args, $overrides);
  }

  /**
   * Updates a Drupal project, returns a list of next actions.
   *
   * @param FileTransfer $filetransfer
   *   Object that is a child of FileTransfer. Used for moving files
   *   to the server.
   * @param array $overrides
   *   An array of settings to override defaults; see self::getInstallArgs().
   *
   * @return array
   *   An array of links which the user may need to complete the update
   */
  public function update(&$filetransfer, $overrides = array()) {
    try {
      // Establish arguments with possible overrides.
      $args = $this->getInstallArgs($overrides);

      // Take a Backup.
      if ($args['make_backup']) {
        $this->makeBackup($args['install_dir'], $args['backup_dir']);
      }

      if (!$this->name) {
        // This is bad, don't want to delete the install directory.
        throw new UpdaterException(t('Fatal error in update, cowardly refusing to wipe out the install directory.'));
      }

      // Make sure the installation parent directory exists and is writable.
      $this->prepareInstallDirectory($filetransfer, $args['install_dir']);

      // Note: If the project is installed in sites/all, it will not be
      // deleted. It will be installed in sites/default as that will override
      // the sites/all reference and not break other sites which are using it.
      if (is_dir($args['install_dir'] . '/' . $this->name)) {
        // Remove the existing installed file.
        $filetransfer->removeDirectory($args['install_dir'] . '/' . $this->name);
      }

      // Copy the directory in place.
      $filetransfer->copyDirectory($this->source, $args['install_dir']);

      // Make sure what we just installed is readable by the web server.
      $this->makeWorldReadable($filetransfer, $args['install_dir'] . '/' . $this->name);

      // Run the updates.
      // @TODO: decide if we want to implement this.
      $this->postUpdate();

      // For now, just return a list of links of things to do.
      return $this->postUpdateTasks();
    }
    catch (FileTransferException $e) {
      throw new UpdaterFileTransferException(t('File Transfer failed, reason: !reason', array('!reason' => strtr($e->getMessage(), $e->arguments))));
    }
  }

  /**
   * Installs a Drupal project, returns a list of next actions.
   *
   * @param FileTransfer $filetransfer
   *   Object that is a child of FileTransfer.
   * @param array $overrides
   *   An array of settings to override defaults; see self::getInstallArgs().
   *
   * @return array
   *   An array of links which the user may need to complete the install.
   */
  public function install(&$filetransfer, $overrides = array()) {
    try {
      // Establish arguments with possible overrides.
      $args = $this->getInstallArgs($overrides);

      // Make sure the installation parent directory exists and is writable.
      $this->prepareInstallDirectory($filetransfer, $args['install_dir']);

      // Copy the directory in place.
      $filetransfer->copyDirectory($this->source, $args['install_dir']);

      // Make sure what we just installed is readable by the web server.
      $this->makeWorldReadable($filetransfer, $args['install_dir'] . '/' . $this->name);

      // Potentially enable something?
      // @TODO: decide if we want to implement this.
      $this->postInstall();
      // For now, just return a list of links of things to do.
      return $this->postInstallTasks();
    }
    catch (FileTransferException $e) {
      throw new UpdaterFileTransferException(t('File Transfer failed, reason: !reason', array('!reason' => strtr($e->getMessage(), $e->arguments))));
    }
  }

  /**
   * Make sure the installation parent directory exists and is writable.
   *
   * @param FileTransfer $filetransfer
   *   Object which is a child of FileTransfer.
   * @param string $directory
   *   The installation directory to prepare.
   */
  public function prepareInstallDirectory(&$filetransfer, $directory) {
    // Make the parent dir writable if need be and create the dir.
    if (!is_dir($directory)) {
      $parent_dir = dirname($directory);
      if (!is_writable($parent_dir)) {
        @chmod($parent_dir, 0755);
        // It is expected that this will fail if the directory is owned by the
        // FTP user. If the FTP user == web server, it will succeed.
        try {
          $filetransfer->createDirectory($directory);
          $this->makeWorldReadable($filetransfer, $directory);
        }
        catch (FileTransferException $e) {
          // Probably still not writable. Try to chmod and do it again.
          // @todo: Make a new exception class so we can catch it differently.
          try {
            $old_perms = substr(sprintf('%o', fileperms($parent_dir)), -4);
            $filetransfer->chmod($parent_dir, 0755);
            $filetransfer->createDirectory($directory);
            $this->makeWorldReadable($filetransfer, $directory);
            // Put the permissions back.
            $filetransfer->chmod($parent_dir, intval($old_perms, 8));
          }
          catch (FileTransferException $e) {
            $message = t($e->getMessage(), $e->arguments);
            $throw_message = t('Unable to create %directory due to the following: %reason', array('%directory' => $directory, '%reason' => $message));
            throw new UpdaterException($throw_message);
          }
        }
        // Put the parent directory back.
        @chmod($parent_dir, 0555);
      }
    }
  }

  /**
   * Ensure that a given directory is world readable.
   *
   * @param FileTransfer $filetransfer
   *   Object which is a child of FileTransfer.
   * @param string $path
   *   The file path to make world readable.
   * @param bool $recursive
   *   If the chmod should be applied recursively.
   */
  public function makeWorldReadable(&$filetransfer, $path, $recursive = TRUE) {
    if (!is_executable($path)) {
      // Set it to read + execute.
      $new_perms = substr(sprintf('%o', fileperms($path)), -4, -1) . "5";
      $filetransfer->chmod($path, intval($new_perms, 8), $recursive);
    }
  }

  /**
   * Perform a backup.
   *
   * @todo Not implemented.
   */
  public function makeBackup(&$filetransfer, $from, $to) {
  }

  /**
   * Return the full path to a directory where backups should be written.
   */
  public function getBackupDir() {
    return file_stream_wrapper_get_instance_by_scheme('temporary')->getDirectoryPath();
  }

  /**
   * Perform actions after new code is updated.
   */
  public function postUpdate() {
  }

  /**
   * Perform actions after installation.
   */
  public function postInstall() {
  }

  /**
   * Return an array of links to pages that should be visited post operation.
   *
   * @return array
   *   Links which provide actions to take after the install is finished.
   */
  public function postInstallTasks() {
    return array();
  }

  /**
   * Return an array of links to pages that should be visited post operation.
   *
   * @return array
   *   Links which provide actions to take after the update is finished.
   */
  public function postUpdateTasks() {
    return array();
  }
}

/**
 * Exception class for the Updater class hierarchy.
 *
 * This is identical to the base Exception class, we just give it a more
 * specific name so that call sites that want to tell the difference can
 * specifically catch these exceptions and treat them differently.
 */
class UpdaterException extends Exception {
}

/**
 * Child class of UpdaterException that indicates a FileTransfer exception.
 *
 * We have to catch FileTransfer exceptions and wrap those in t(), since
 * FileTransfer is so low-level that it doesn't use any Drupal APIs and none
 * of the strings are translated.
 */
class UpdaterFileTransferException extends UpdaterException {
}
