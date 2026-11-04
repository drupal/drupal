<?php

namespace Drupal\user\Plugin\views\filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\user\Entity\Role;
use Drupal\user\PermissionHandlerInterface;
use Drupal\user\RoleInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Filter handler for user roles.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("user_permissions")]
class Permissions extends ManyToOne {
  use DeprecatedServicePropertyTrait;

  /**
   * The service properties that should raise a deprecation error.
   */
  private array $deprecatedProperties = ['moduleHandler' => 'module_handler'];

  /**
   * Constructs a Permissions object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\user\PermissionHandlerInterface $permissionHandler
   *   The permission handler.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected PermissionHandlerInterface $permissionHandler,
    #[Autowire(service: 'extension.list.module')]
    protected ModuleExtensionList $moduleExtensionList,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $permissions = $this->permissionHandler->getPermissions();
      foreach ($permissions as $perm => $perm_item) {
        $provider = $perm_item['provider'];
        $display_name = $this->moduleExtensionList->getName($provider);
        $this->valueOptions[$display_name][$perm] = Html::escape(strip_tags($perm_item['title']));
      }
      return $this->valueOptions;
    }
    else {
      return $this->valueOptions;
    }
  }

  /**
   * {@inheritdoc}
   *
   * Replace the configured permission with a filter by all roles that have this
   * permission.
   */
  public function query() {
    $rids = [];
    $all_roles = Role::loadMultiple();
    // Get all role IDs that have the configured permissions.
    foreach ($this->value as $permission) {
      $roles = array_filter($all_roles, fn(RoleInterface $role) => $role->hasPermission($permission));
      // Method Role::loadMultiple() returns an array with the role IDs as keys,
      // so take the array keys and merge them with previously found role IDs.
      $rids = array_merge($rids, array_keys($roles));
    }
    // Remove any duplicate role IDs.
    $rids = array_unique($rids);
    $this->value = $rids;

    // $this->value contains the role IDs that have the configured permission.
    parent::query();
  }

}
