<?php

/**
 * @file
 * Contains \Drupal\file\FileStorageInterface.
 */

namespace Drupal\file;

use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines a common interface for file entity controller classes.
 */
interface FileStorageInterface extends EntityStorageInterface {

  /**
   * Determines total disk space used by a single user or the whole filesystem.
   *
   * @param int $uid
   *   Optional. A user id, specifying NULL returns the total space used by all
   *   non-temporary files.
   * @param int $status
   *   (Optional) The file status to consider. The default is to only
   *   consider files in status FILE_STATUS_PERMANENT.
   *
   * @return int
   *   An integer containing the number of bytes used.
   */
  public function spaceUsed($uid = NULL, $status = FILE_STATUS_PERMANENT);

  /**
   * Retrieves old temporary files.
   *
   * Get files older than the temporary maximum age,
   * \Drupal::config('system.file')->get('temporary_maximum_age').
   *
   *  @return array
   *    A list of files to be deleted.
   */
  public function retrieveTemporaryFiles();

}
