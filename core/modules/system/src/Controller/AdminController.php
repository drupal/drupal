<?php

namespace Drupal\system\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\system\ModuleAdminLinksHelper;
use Drupal\user\ModulePermissionsLinkHelper;
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
   * @var \Drupal\system\ModuleAdminLinksHelper
   */
  protected ModuleAdminLinksHelper $moduleAdminLinks;

  /**
   * The module permissions link service.
   *
   * @var \Drupal\user\ModulePermissionsLinkHelper
   */
  protected ModulePermissionsLinkHelper $modulePermissionsLinks;

  /**
   * AdminController constructor.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   * @param \Drupal\system\ModuleAdminLinksHelper|null $module_admin_links
   *   The module admin links.
   * @param \Drupal\user\ModulePermissionsLinkHelper|null $module_permissions_link
   *   The module permission link.
   */
  public function __construct(ModuleExtensionList $extension_list_module, ?ModuleAdminLinksHelper $module_admin_links = NULL, ?ModulePermissionsLinkHelper $module_permissions_link = NULL) {
    $this->moduleExtensionList = $extension_list_module;
    if (!isset($module_admin_links)) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $module_admin_tasks_helper argument is deprecated in drupal:10.2.0 and the $module_admin_tasks_helper argument will be required in drupal:11.0.0. See https://www.drupal.org/node/3038972', E_USER_DEPRECATED);
      $module_admin_links = \Drupal::service('system.module_admin_links_helper');
    }
    $this->moduleAdminLinks = $module_admin_links;
    if (!isset($module_permissions_link)) {
      @trigger_error('Calling ' . __METHOD__ . ' without the $module_permissions_link argument is deprecated in drupal:10.2.0 and the $module_permissions_link argument will be required in drupal:11.0.0. See https://www.drupal.org/node/3038972', E_USER_DEPRECATED);
      $module_permissions_link = \Drupal::service('user.module_permissions_link_helper');
    }
    $this->modulePermissionsLinks = $module_permissions_link;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('extension.list.module'),
      $container->get('system.module_admin_links_helper'),
      $container->get('user.module_permissions_link_helper')
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
