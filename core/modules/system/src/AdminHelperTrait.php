<?php

namespace Drupal\system;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Url;

/**
 * Reusable code for the admin area.
 */
trait AdminHelperTrait {

  /**
   * Memory cache of processed menu tree elements.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeElement[]
   */
  protected static $menuTree;

  /**
   * Generates a list of admin tasks offered by a specified module.
   *
   * @param string $module
   *   The module name.
   *
   * @return array
   *   An array of task links.
   */
  public static function getModuleAdminTasks($module) {
    if (!isset(static::$menuTree)) {
      $menu_link_tree = \Drupal::menuTree();
      $parameters = new MenuTreeParameters();
      $parameters->setRoot('system.admin')->excludeRoot()->onlyEnabledLinks();
      static::$menuTree = $menu_link_tree->load('system.admin', $parameters);
      $manipulators = [
        ['callable' => 'menu.default_tree_manipulators:checkAccess'],
        ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
        ['callable' => 'menu.default_tree_manipulators:flatten'],
      ];
      static::$menuTree = $menu_link_tree->transform(static::$menuTree, $manipulators);
    }

    $admin_tasks = [];
    foreach (static::$menuTree as $element) {
      if (!$element->access->isAllowed()) {
        // @todo Bubble cacheability metadata of both accessible and
        //   inaccessible links. Currently made impossible by the way admin
        //   tasks are rendered. See https://www.drupal.org/node/2488958
        continue;
      }

      $link = $element->link;
      if ($link->getProvider() !== $module) {
        continue;
      }
      $admin_tasks[] = [
        'title' => $link->getTitle(),
        'description' => $link->getDescription(),
        'url' => $link->getUrlObject(),
      ];
    }

    // Append link for permissions.
    /** @var \Drupal\user\PermissionHandlerInterface $permission_handler */
    $permission_handler = \Drupal::service('user.permissions');

    if ($permission_handler->moduleProvidesPermissions($module)) {
      /** @var \Drupal\Core\Access\AccessManagerInterface $access_manager */
      $access_manager = \Drupal::service('access_manager');
      if ($access_manager->checkNamedRoute('user.admin_permissions', [], \Drupal::currentUser())) {
        $url = new Url('user.admin_permissions');
        $url->setOption('fragment', 'module-' . $module);
        $admin_tasks["user.admin_permissions.$module"] = [
          'title' => t('Configure @module permissions', ['@module' => \Drupal::moduleHandler()->getName($module)]),
          'description' => '',
          'url' => $url,
        ];
      }
    }

    return $admin_tasks;
  }

  /**
   * Resets the static cache used by ::getModuleAdminTasks().
   *
   * @see \Drupal\system\AdminHelperTrait::getModuleAdminTasks()
   */
  public static function resetModuleAdminTasksCache() {
    static::$menuTree = NULL;
  }

}
