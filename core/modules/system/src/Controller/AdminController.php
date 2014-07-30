<?php

/**
 * @file
 * Contains \Drupal\system\Controller\AdminController.
 */

namespace Drupal\system\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for admin section.
 */
class AdminController extends ControllerBase {

  /**
   * Prints a listing of admin tasks, organized by module.
   *
   * @return array
   *  A render array containing the listing.
   */
  public function index() {
    $module_info = system_get_info('module');
    foreach ($module_info as $module => $info) {
      $module_info[$module] = new \stdClass();
      $module_info[$module]->info = $info;
    }

    uasort($module_info, 'system_sort_modules_by_info_name');
    $menu_items = array();

    foreach ($module_info as $module => $info) {
      // Only display a section if there are any available tasks.
      if ($admin_tasks = system_get_module_admin_tasks($module, $info->info)) {
        // Sort links by title.
        uasort($admin_tasks, array('\Drupal\Component\Utility\SortArray', 'sortByTitleElement'));
        // Move 'Configure permissions' links to the bottom of each section.
        $permission_key = "user.admin_permissions.$module";
        if (isset($admin_tasks[$permission_key])) {
          $permission_task = $admin_tasks[$permission_key];
          unset($admin_tasks[$permission_key]);
          $admin_tasks[$permission_key] = $permission_task;
        }

        $menu_items[$info->info['name']] = array($info->info['description'], $admin_tasks);
      }
    }

    $output = array(
      '#theme' => 'system_admin_index',
      '#menu_items' => $menu_items,
    );

    return $output;
  }

}
