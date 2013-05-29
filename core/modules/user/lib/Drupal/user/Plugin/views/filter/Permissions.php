<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\filter\Permissions.
 */

namespace Drupal\user\Plugin\views\filter;

use Drupal\Component\Annotation\PluginID;
use Drupal\views\Plugin\views\filter\ManyToOne;

/**
 * Filter handler for user roles.
 *
 * @ingroup views_filter_handlers
 *
 * @PluginID("user_permissions")
 */
class Permissions extends ManyToOne {

  public function getValueOptions() {
    $module_info = system_get_info('module');

    // Get a list of all the modules implementing a hook_permission() and sort by
    // display name.
    $modules = array();
    foreach (module_implements('permission') as $module) {
      $modules[$module] = $module_info[$module]['name'];
    }
    asort($modules);

    $this->value_options = array();
    foreach ($modules as $module => $display_name) {
      if ($permissions = module_invoke($module, 'permission')) {
        foreach ($permissions as $perm => $perm_item) {
          // @todo: group by module but views_handler_filter_many_to_one does not support this.
          $this->value_options[$perm] = check_plain(strip_tags($perm_item['title']));
        }
      }
    }
  }

}
