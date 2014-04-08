<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\filter\Permissions.
 */

namespace Drupal\user\Plugin\views\filter;

use Drupal\Component\Utility\String;
use Drupal\Core\Extension\ModuleHandlerInterface;
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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('module_handler'));
  }

  public function getValueOptions() {
    if (!isset($this->value_options)) {
      $module_info = system_get_info('module');

      // Get a list of all the modules implementing a hook_permission() and sort by
      // display name.
      $modules = array();
      foreach ($this->moduleHandler->getImplementations('permission') as $module) {
        $modules[$module] = $module_info[$module]['name'];
      }
      asort($modules);

      $this->value_options = array();
      foreach ($modules as $module => $display_name) {
        if ($permissions = $this->moduleHandler->invoke($module, 'permission')) {
          foreach ($permissions as $perm => $perm_item) {
            $this->value_options[$display_name][$perm] = String::checkPlain(strip_tags($perm_item['title']));
          }
        }
      }
    }
    else {
      return $this->value_options;
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
