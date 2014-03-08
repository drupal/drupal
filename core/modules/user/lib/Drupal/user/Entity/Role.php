<?php

/**
 * @file
 * Contains Drupal\user\Entity\Role.
 */

namespace Drupal\user\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\user\RoleInterface;

/**
 * Defines the user role entity class.
 *
 * @ConfigEntityType(
 *   id = "user_role",
 *   label = @Translation("Role"),
 *   controllers = {
 *     "storage" = "Drupal\user\RoleStorageController",
 *     "access" = "Drupal\user\RoleAccessController",
 *     "list" = "Drupal\user\RoleListController",
 *     "form" = {
 *       "default" = "Drupal\user\RoleFormController",
 *       "delete" = "Drupal\user\Form\UserRoleDelete"
 *     }
 *   },
 *   admin_permission = "administer permissions",
 *   config_prefix = "role",
 *   entity_keys = {
 *     "id" = "id",
 *     "weight" = "weight",
 *     "label" = "label"
 *   },
 *   links = {
 *     "delete-form" = "user.role_delete",
 *     "edit-form" = "user.role_edit",
 *     "edit-permissions-form" = "user.admin_permission"
 *   }
 * )
 */
class Role extends ConfigEntityBase implements RoleInterface {

  /**
   * The machine name of this role.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable label of this role.
   *
   * @var string
   */
  public $label;

  /**
   * The weight of this role in administrative listings.
   *
   * @var int
   */
  public $weight;

  /**
   * The permissions belonging to this role.
   *
   * @var array
   */
  public $permissions = array();

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    return $this->permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission($permission) {
    return in_array($permission, $this->permissions);
  }

  /**
   * {@inheritdoc}
   */
  public function grantPermission($permission) {
    if (!$this->hasPermission($permission)) {
      $this->permissions[] = $permission;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function revokePermission($permission) {
    $this->permissions = array_diff($this->permissions, array($permission));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageControllerInterface $storage_controller, array &$entities) {
    parent::postLoad($storage_controller, $entities);
    // Sort the queried roles by their weight.
    // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
    uasort($entities, 'static::sort');
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    parent::preSave($storage_controller);

    if (!isset($this->weight) && ($roles = $storage_controller->loadMultiple())) {
      // Set a role weight to make this new role last.
      $max = array_reduce($roles, function($max, $role) {
        return $max > $role->weight ? $max : $role->weight;
      });
      $this->weight = $max + 1;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    parent::postSave($storage_controller, $update);

    Cache::invalidateTags(array('role' => $this->id()));
    // Clear render cache.
    entity_render_cache_clear();
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    parent::postDelete($storage_controller, $entities);

    $ids = array_keys($entities);
    $storage_controller->deleteRoleReferences($ids);
    Cache::invalidateTags(array('role' => $ids));
  }

}
