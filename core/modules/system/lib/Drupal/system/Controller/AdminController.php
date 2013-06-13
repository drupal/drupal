<?php

/**
 * @file
 * Contains \Drupal\system\Controller\AdminController.
 */

namespace Drupal\system\Controller;

use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for admin section.
 */
class AdminController implements ControllerInterface {

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs an AdminController object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module Handler Service.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

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

    $this->moduleHandler->loadInclude('system', 'admin.inc');

    uasort($module_info, 'system_sort_modules_by_info_name');
    $menu_items = array();

    foreach ($module_info as $module => $info) {
      // Only display a section if there are any available tasks.
      if ($admin_tasks = system_get_module_admin_tasks($module, $info->info)) {
        // Sort links by title.
        uasort($admin_tasks, 'drupal_sort_title');
        // Move 'Configure permissions' links to the bottom of each section.
        $permission_key = "admin/people/permissions#module-$module";
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
