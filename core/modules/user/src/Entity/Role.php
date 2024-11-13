<?php

namespace Drupal\user\Entity;

use Drupal\Core\Config\Action\Attribute\ActionMethod;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\RoleAccessControlHandler;
use Drupal\user\RoleForm;
use Drupal\user\RoleInterface;
use Drupal\user\RoleListBuilder;
use Drupal\user\RoleStorage;

/**
 * Defines the user role entity class.
 */
#[ConfigEntityType(
  id: 'user_role',
  label: new TranslatableMarkup('Role'),
  label_collection: new TranslatableMarkup('Roles'),
  label_singular: new TranslatableMarkup('role'),
  label_plural: new TranslatableMarkup('roles'),
  config_prefix: 'role',
  static_cache: TRUE,
  entity_keys: [
    'id' => 'id',
    'weight' => 'weight',
    'label' => 'label',
  ],
  handlers: [
    'storage' => RoleStorage::class,
    'access' => RoleAccessControlHandler::class,
    'list_builder' => RoleListBuilder::class,
    'form' => [
      'default' => RoleForm::class,
      'delete' => EntityDeleteForm::class,
    ],
  ],
  links: [
    'delete-form' => '/admin/people/roles/manage/{user_role}/delete',
    'edit-form' => '/admin/people/roles/manage/{user_role}',
    'edit-permissions-form' => '/admin/people/permissions/{user_role}',
    'collection' => '/admin/people/roles',
  ],
  admin_permission: 'administer permissions',
  label_count: [
    'singular' => '@count role',
    'plural' => '@count roles',
  ],
  config_export: [
    'id',
    'label',
    'weight',
    'is_admin',
    'permissions',
  ],
)]
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
  protected $permissions = [];

  /**
   * An indicator whether the role has all permissions.
   *
   * @var bool
   */
  protected $is_admin = FALSE;

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
  #[ActionMethod(adminLabel: new TranslatableMarkup('Add permission to role'))]
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
    $this->permissions = array_diff($this->permissions, [$permission]);
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
    uasort($entities, [static::class, 'sort']);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if (!isset($this->weight)) {
      // Set a role weight to make this new role last.
      $this->weight = array_reduce($storage->loadMultiple(), function ($max, $role) {
        return $max > $role->weight ? $max : $role->weight + 1;
      }, 0);
    }

    if (!$this->isSyncing() && $this->hasTrustedData()) {
      // Permissions are always ordered alphabetically to avoid conflicts in the
      // exported configuration. If the save is not trusted then the
      // configuration will be sorted by StorableConfigBase.
      sort($this->permissions);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    // Load all permission definitions.
    $permission_definitions = \Drupal::service('user.permissions')->getPermissions();
    $valid_permissions = array_intersect($this->permissions, array_keys($permission_definitions));
    $invalid_permissions = array_diff($this->permissions, $valid_permissions);
    if (!empty($invalid_permissions)) {
      throw new \RuntimeException('Adding non-existent permissions to a role is not allowed. The incorrect permissions are "' . implode('", "', $invalid_permissions) . '".');
    }
    foreach ($valid_permissions as $permission) {
      // Depend on the module that is providing this permissions.
      $this->addDependency('module', $permission_definitions[$permission]['provider']);
      // Depend on any other dependencies defined by permissions granted to
      // this role.
      if (!empty($permission_definitions[$permission]['dependencies'])) {
        $this->addDependencies($permission_definitions[$permission]['dependencies']);
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);
    // Load all permission definitions.
    $permission_definitions = \Drupal::service('user.permissions')->getPermissions();

    // Convert config and content entity dependencies to a list of names to make
    // it easier to check.
    foreach (['content', 'config'] as $type) {
      $dependencies[$type] = array_keys($dependencies[$type]);
    }

    // Remove any permissions from the role that are dependent on anything being
    // deleted or uninstalled.
    foreach ($this->permissions as $key => $permission) {
      if (!isset($permission_definitions[$permission])) {
        // If the permission is not defined then there's nothing we can do.
        continue;
      }

      if (in_array($permission_definitions[$permission]['provider'], $dependencies['module'], TRUE)) {
        unset($this->permissions[$key]);
        $changed = TRUE;
        // Process the next permission.
        continue;
      }

      if (isset($permission_definitions[$permission]['dependencies'])) {
        foreach ($permission_definitions[$permission]['dependencies'] as $type => $list) {
          if (array_intersect($list, $dependencies[$type])) {
            unset($this->permissions[$key]);
            $changed = TRUE;
            // Process the next permission.
            continue 2;
          }
        }
      }
    }

    return $changed;
  }

  /**
   * Returns all valid permissions.
   *
   * @return string[]
   *   All possible valid permissions.
   *
   * @see \Drupal\user\PermissionHandler::getPermissions()
   *
   * @internal
   * @todo Revisit in https://www.drupal.org/node/3446364
   */
  public static function getAllValidPermissions(): array {
    return array_keys(\Drupal::service('user.permissions')->getPermissions());
  }

}
