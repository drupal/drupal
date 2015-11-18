<?php

/**
 * @file
 * Contains \Drupal\file\FileStorageInterface.
 */

namespace Drupal\file;

use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines an interface for file entity storage classes.
 */
interface FileStorageInterface extends ContentEntityStorageInterface {

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
}
