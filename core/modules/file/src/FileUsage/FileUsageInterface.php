<?php

/**
 * @file
 * Contains \Drupal\file\FileUsage\FileUsageInterface.
 */

namespace Drupal\file\FileUsage;

use Drupal\file\FileInterface;

/**
 * File usage backend interface.
 */
interface FileUsageInterface {

  /**
   * Records that a module is using a file.
   *
   * Examples:
   * - A module that associates files with nodes, so $type would be
   *   'node' and $id would be the node's nid. Files for all revisions are
   *   stored within a single nid.
   * - The User module associates an image with a user, so $type would be 'user'
   *   and the $id would be the user's uid.
   *
   * @param \Drupal\file\FileInterface $file
   *   A file entity.
   * @param string $module
   *   The name of the module using the file.
   * @param string $type
   *   The type of the object that contains the referenced file.
   * @param int $id
   *   The unique, numeric ID of the object containing the referenced file.
   * @param int $count
   *   (optional) The number of references to add to the object. Defaults to 1.
   */
  public function add(FileInterface $file, $module, $type, $id, $count = 1);

  /**
   * Removes a record to indicate that a module is no longer using a file.
   *
   * @param \Drupal\file\FileInterface $file
   *   A file entity.
   * @param string $module
   *   The name of the module using the file.
   * @param string $type
   *   (optional) The type of the object that contains the referenced file. May
   *   be omitted if all module references to a file are being deleted. Defaults
   *   to NULL.
   * @param int $id
   *   (optional) The unique, numeric ID of the object containing the referenced
   *   file. May be omitted if all module references to a file are being
   *   deleted. Defaults to NULL.
   * @param int $count
   *   (optional) The number of references to delete from the object. Defaults
   *   to 1. Zero may be specified to delete all references to the file within a
   *   specific object.
   */
  public function delete(FileInterface $file, $module, $type = NULL, $id = NULL, $count = 1);

  /**
   * Determines where a file is used.
   *
   * @param \Drupal\file\FileInterface $file
   *   A file entity.
   *
   * @return array
   *   A nested array with usage data. The first level is keyed by module name,
   *   the second by object type and the third by the object id. The value of
   *   the third level contains the usage count.
   *
   */
  public function listUsage(FileInterface $file);
}
