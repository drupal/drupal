<?php

/**
 * @file
 * Definition of Drupal\Component\Archiver\ArchiverInterface.
 */

namespace Drupal\Component\Archiver;

/**
 * Defines the common interface for all Archiver classes.
 */
interface ArchiverInterface {

  /**
   * Adds the specified file or directory to the archive.
   *
   * @param string $file_path
   *   The full system path of the file or directory to add. Only local files
   *   and directories are supported.
   *
   * @return \Drupal\Component\Archiver\ArchiverInterface
   *   The called object.
   */
  public function add($file_path);

  /**
   * Removes the specified file from the archive.
   *
   * @param string $path
   *   The file name relative to the root of the archive to remove.
   *
   * @return \Drupal\Component\Archiver\ArchiverInterface
   *   The called object.
   */
  public function remove($path);

  /**
   * Extracts multiple files in the archive to the specified path.
   *
   * @param string $path
   *   A full system path of the directory to which to extract files.
   * @param array $files
   *   Optionally specify a list of files to be extracted. Files are
   *   relative to the root of the archive. If not specified, all files
   *   in the archive will be extracted.
   *
   * @return \Drupal\Component\Archiver\ArchiverInterface
   *   The called object.
   */
  public function extract($path, array $files = array());

  /**
   * Lists all files in the archive.
   *
   * @return array
   *   An array of file names relative to the root of the archive.
   */
  public function listContents();
}
