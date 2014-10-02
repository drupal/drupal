<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\filter\Permissions.
 */

namespace Drupal\user\Plugin\views\filter;

use Drupal\Component\Utility\String;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\user\PermissionHandlerInterface;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter handler for user roles.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("user_permissions")
 */
class Permissions extends ManyToOne {

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * Constructs a Permissions object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PermissionHandlerInterface $permission_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->permissionHandler = $permission_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('user.permissions')
    );
  }

  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $module_info = system_get_info('module');

      $permissions = $this->permissionHandler->getPermissions();
      foreach ($permissions as $perm => $perm_item) {
        $provider = $perm_item['provider'];
        $display_name = $module_info[$provider]['name'];
        $this->valueOptions[$display_name][$perm] = String::checkPlain(strip_tags($perm_item['title']));
      }
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
    // @todo user_role_names() should maybe support multiple permissions.
    $rids = array();
    // Get all roles, that have the configured permissions.
    foreach ($this->value as $permission) {
      $roles = user_role_names(FALSE, $permission);
      $rids += array_keys($roles);
    }
    $rids = array_unique($rids);
    $this->value = $rids;

    // $this->value contains the role IDs that have the configured permission.
    parent::query();
  }

}
