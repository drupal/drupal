<?php

namespace Drupal\user\Plugin\views\filter;

use Drupal\Component\Utility\Html;
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
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PermissionHandlerInterface $permission_handler, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->permissionHandler = $permission_handler;
    $this->moduleHandler = $module_handler;
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
      $container->get('module_handler')
    );
  }

  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $permissions = $this->permissionHandler->getPermissions();
      foreach ($permissions as $perm => $perm_item) {
        $provider = $perm_item['provider'];
        $display_name = $this->moduleHandler->getName($provider);
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
    // @todo user_role_names() should maybe support multiple permissions.
    $rids = array();
    // Get all role IDs that have the configured permissions.
    foreach ($this->value as $permission) {
      $roles = user_role_names(FALSE, $permission);
      // user_role_names() returns an array with the role IDs as keys, so take
      // the array keys and merge them with previously found role IDs.
      $rids = array_merge($rids, array_keys($roles));
    }
    // Remove any duplicate role IDs.
    $rids = array_unique($rids);
    $this->value = $rids;

    // $this->value contains the role IDs that have the configured permission.
    parent::query();
  }

}
