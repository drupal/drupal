<?php

/**
 * @file
 * Contains \Drupal\user\Entity\Role.
 */

namespace Drupal\user\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\user\RoleInterface;

/**
 * Defines the user role entity class.
 *
 * @ConfigEntityType(
 *   id = "user_role",
 *   label = @Translation("Role"),
 *   handlers = {
 *     "storage" = "Drupal\user\RoleStorage",
 *     "access" = "Drupal\user\RoleAccessControlHandler",
 *     "list_builder" = "Drupal\user\RoleListBuilder",
 *     "form" = {
 *       "default" = "Drupal\user\RoleForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer permissions",
 *   config_prefix = "role",
 *   static_cache = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "weight" = "weight",
 *     "label" = "label"
 *   },
 *   links = {
 *     "delete-form" = "/admin/people/roles/manage/{user_role}/delete",
 *     "edit-form" = "/admin/people/roles/manage/{user_role}",
 *     "edit-permissions-form" = "/admin/people/permissions/{user_role}",
 *     "collection" = "/admin/people/roles",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "weight",
 *     "is_admin",
 *     "permissions",
 *   }
 * )
 */
class Role extends ConfigEntityBase implements RoleInterface {

  /**
   * The machine name of this role.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable label of this role.
   *
   * @var string
   */
  protected $label;

  /**
   * The weight of this role in administrative listings.
   *
   * @var int
   */
  protected $weight;

  /**
   * The permissions belonging to this role.
   *
   * @var array
   */
  protected $permissions = array();

  /**
   * An indicator whether the role has all permissions.
   *
   * @var bool
   */
  protected $is_admin;

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    if ($this->isAdmin()) {
      return [];
    }
    return $this->permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get('weight');
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission($permission) {
    if ($this->isAdmin()) {
      return TRUE;
    }
    return in_array($permission, $this->permissions);
  }

  /**
   * {@inheritdoc}
   */
  public function grantPermission($permission) {
    if ($this->isAdmin()) {
      return $this;
    }
    if (!$this->hasPermission($permission)) {
      $this->permissions[] = $permission;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function revokePermission($permission) {
    if ($this->isAdmin()) {
      return $this;
    }
    $this->permissions = array_diff($this->permissions, array($permission));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isAdmin() {
    return (bool) $this->is_admin;
  }

  /**
   * {@inheritdoc}
   */
  public function setIsAdmin($is_admin) {
    $this->is_admin = $is_admin;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    parent::postLoad($storage, $entities);
    // Sort the queried roles by their weight.
    // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
    uasort($entities, 'static::sort');
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if (!isset($this->weight) && ($roles = $storage->loadMultiple())) {
      // Set a role weight to make this new role last.
      $max = array_reduce($roles, function($max, $role) {
        return $max > $role->weight ? $max : $role->weight;
      });
      $this->weight = $max + 1;
    }
  }

}
