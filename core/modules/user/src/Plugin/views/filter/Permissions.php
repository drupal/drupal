<?php

namespace Drupal\user\Plugin\views\filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\user\Entity\Role;
use Drupal\user\PermissionHandlerInterface;
use Drupal\user\RoleInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * Module extension list.
   */
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * Constructs a Permissions object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   * @param \Drupal\Core\Extension\ModuleExtensionList|\Drupal\Core\Extension\ModuleHandlerInterface $module_extension_list
   *   The module extension list.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PermissionHandlerInterface $permission_handler, ModuleExtensionList|ModuleHandlerInterface $module_extension_list) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->permissionHandler = $permission_handler;
    if ($module_extension_list instanceof ModuleHandlerInterface) {
      @trigger_error('Calling ' . __METHOD__ . '() with the $module_extension_list argument as ModuleHandlerInterface is deprecated in drupal:10.3.0 and will be required in drupal:12.0.0. See https://www.drupal.org/node/3310017', E_USER_DEPRECATED);
      $module_extension_list = \Drupal::service('extension.list.module');
    }
    $this->moduleExtensionList = $module_extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('user.permissions'),
      $container->get('extension.list.module'),
    );
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
