<?php

namespace Drupal\system\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleExtensionList;
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
   * AdminController constructor.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList|null $extension_list_module
   *   The module extension list. This is left optional for BC reasons, but the
   *   optional usage is deprecated and will become required in Drupal 9.0.0.
   */
  public function __construct(ModuleExtensionList $extension_list_module = NULL) {
    if ($extension_list_module === NULL) {
      @trigger_error('Calling AdminController::__construct() with the $module_extension_list argument is supported in drupal:8.8.0 and will be required before drupal:9.0.0. See https://www.drupal.org/node/2709919.', E_USER_DEPRECATED);
      $extension_list_module = \Drupal::service('extension.list.module');
    }
    $this->moduleExtensionList = $extension_list_module;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('extension.list.module')
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
      // Only display a section if there are any available tasks.
      if ($admin_tasks = system_get_module_admin_tasks($module, $info->info)) {
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
