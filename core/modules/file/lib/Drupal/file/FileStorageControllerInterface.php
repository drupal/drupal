<?php

/**
 * @file
 * Contains \Drupal\file\FileStorageControllerInterface.
 */

namespace Drupal\file;

use Drupal\Core\Entity\EntityStorageControllerInterface;

/**
 * Defines a common interface for file entity controller classes.
 */
interface FileStorageControllerInterface extends EntityStorageControllerInterface {

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
   * Retrieve temporary files that are older than DRUPAL_MAXIMUM_TEMP_FILE_AGE.
   *
   *  @return array
   *    A list of files to be deleted.
   */
  public function retrieveTemporaryFiles();

}
