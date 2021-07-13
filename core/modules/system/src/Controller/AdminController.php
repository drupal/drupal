<?php

namespace Drupal\system\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\system\ModuleAdminLinks;
use Drupal\user\ModulePermissionsLink;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for admin section.
 */
class AdminController extends ControllerBase {

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The module admin links service.
   *
   * @var \Drupal\system\ModuleAdminLinks
   */
  protected $moduleAdminLinks;

  /**
   * The module permissions link service.
   *
   * @var \Drupal\user\ModulePermissionsLink
   */
  protected $modulePermissionsLinks;

  /**
   * AdminController constructor.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   * @param \Drupal\system\ModuleAdminLinks $module_admin_links
   *   The module admin links service.
   * @param \Drupal\user\ModulePermissionsLink $module_permissions_link
   *   The module permissions link service.
   */
  public function __construct(ModuleExtensionList $extension_list_module, ModuleAdminLinks $module_admin_links = NULL, ModulePermissionsLink $module_permissions_link = NULL) {
    $this->moduleExtensionList = $extension_list_module;
    if (!isset($module_admin_links)) {
      @trigger_error('Calling AdminController::__construct() without the $module_admin_tasks_helper argument is deprecated in drupal:9.3.0 and the $module_admin_tasks_helper argument will be required in drupal:10.0.0. See https://www.drupal.org/node/3038972', E_USER_DEPRECATED);
      $module_admin_links = \Drupal::service('system.module_admin_links');
    }
    $this->moduleAdminLinks = $module_admin_links;
    if (!isset($module_permissions_link)) {
      @trigger_error('Calling AdminController::__construct() without the $module_permissions_link argument is deprecated in drupal:9.3.0 and the $module_permissions_link argument will be required in drupal:10.0.0. See https://www.drupal.org/node/3038972', E_USER_DEPRECATED);
      $module_permissions_link = \Drupal::service('user.module_permissions_link');
    }
    $this->modulePermissionsLinks = $module_permissions_link;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('extension.list.module'),
      $container->get('system.module_admin_links'),
      $container->get('user.module_permissions_link')
    );
  }

  /**
   * Prints a listing of admin tasks, organized by module.
   *
   * @return array
   *   A render array containing the listing.
   */
  public function index() {
    $module_info = $this->moduleExtensionList->getAllInstalledInfo();
    foreach ($module_info as $module => $info) {
      $module_info[$module] = new \stdClass();
      $module_info[$module]->info = $info;
    }

    uasort($module_info, 'system_sort_modules_by_info_name');
    $menu_items = [];

    foreach ($module_info as $module => $info) {
      $admin_tasks = $this->moduleAdminLinks->getModuleAdminLinks($module);
      if ($module_permissions_link = \Drupal::service('user.module_permissions_link')->getModulePermissionsLink($module)) {
        $admin_tasks["user.admin_permissions.{$module}"] = $module_permissions_link;
      }

      // Only display a section if there are any available tasks.
      if ($admin_tasks) {
        // Sort links by title.
        uasort($admin_tasks, ['\Drupal\Component\Utility\SortArray', 'sortByTitleElement']);
        // Move 'Configure permissions' links to the bottom of each section.
        $permission_key = "user.admin_permissions.$module";
        if (isset($admin_tasks[$permission_key])) {
          $permission_task = $admin_tasks[$permission_key];
          unset($admin_tasks[$permission_key]);
          $admin_tasks[$permission_key] = $permission_task;
        }

        $menu_items[$info->info['name']] = [$info->info['description'], $admin_tasks];
      }
    }

    $output = [
      '#theme' => 'system_admin_index',
      '#menu_items' => $menu_items,
    ];

    return $output;
  }

}
