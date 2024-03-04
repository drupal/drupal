<?php

namespace Drupal\system\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\system\ModuleAdminLinksHelper;
use Drupal\user\ModulePermissionsLinkHelper;

/**
 * Controller for admin section.
 */
class AdminController extends ControllerBase {

  /**
   * AdminController constructor.
   */
  public function __construct(
    protected ModuleExtensionList $moduleExtensionList,
    protected ModuleAdminLinksHelper $moduleAdminLinks,
    protected ModulePermissionsLinkHelper $modulePermissionsLinks,
  ) {
  }

  /**
   * Prints a listing of admin tasks, organized by module.
   *
   * @return array
   *   A render array containing the listing.
   */
  public function index() {
    $extensions = array_intersect_key($this->moduleExtensionList->getList(), $this->moduleHandler()->getModuleList());

    uasort($extensions, [ModuleExtensionList::class, 'sortByName']);
    $menu_items = [];

    foreach ($extensions as $module => $extension) {
      // Only display a section if there are any available tasks.
      $admin_tasks = $this->moduleAdminLinks->getModuleAdminLinks($module);
      if ($module_permissions_link = $this->modulePermissionsLinks->getModulePermissionsLink($module, $extension->info['name'])) {
        $admin_tasks["user.admin_permissions.{$module}"] = $module_permissions_link;
      }
      if (!empty($admin_tasks)) {
        // Sort links by title.
        uasort($admin_tasks, ['\Drupal\Component\Utility\SortArray', 'sortByTitleElement']);
        // Move 'Configure permissions' links to the bottom of each section.
        $permission_key = "user.admin_permissions.$module";
        if (isset($admin_tasks[$permission_key])) {
          $permission_task = $admin_tasks[$permission_key];
          unset($admin_tasks[$permission_key]);
          $admin_tasks[$permission_key] = $permission_task;
        }

        $menu_items[$extension->info['name']] = [$extension->info['description'], $admin_tasks];
      }
    }

    $output = [
      '#theme' => 'system_admin_index',
      '#menu_items' => $menu_items,
    ];

    return $output;
  }

}
