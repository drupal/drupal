<?php

/**
 * @file
 * Contains \Drupal\user\RoleStorageInterface.
 */

namespace Drupal\user;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;

/**
 * Defines a common interface for roel entity controller classes.
 */
interface RoleStorageInterface extends ConfigEntityStorageInterface {

  /**
   * Delete role references.
   *
   * @param array $rids
   *   The list of role IDs being deleted. The storage should
   *   remove permission and user references to this role.
   */
  public function deleteRoleReferences(array $rids);
}
