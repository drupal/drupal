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
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   */
  public function __construct(ModuleExtensionList $extension_list_module) {
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
    $extensions = array_intersect_key($this->moduleExtensionList->getList(), $this->moduleHandler()->getModuleList());

    uasort($extensions, [ModuleExtensionList::class, 'sortByName']);
    $menu_items = [];

    foreach ($extensions as $module => $extension) {
      // Only display a section if there are any available tasks.
      if ($admin_tasks = system_get_module_admin_tasks($module, $extension->info)) {
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
