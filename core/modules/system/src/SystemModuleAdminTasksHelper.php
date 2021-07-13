<?php

namespace Drupal\system;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Provides a helper service for the admin area.
 */
class SystemModuleAdminTasksHelper {

  use StringTranslationTrait;

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * The access manager service.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Memory cache of processed menu tree elements.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeElement[]
   */
  protected $menuTree;

  /**
   * Constructs a new service instance.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree
   *   The menu link tree service.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(MenuLinkTreeInterface $menu_link_tree, AccessManagerInterface $access_manager, ModuleHandlerInterface $module_handler) {
    $this->menuLinkTree = $menu_link_tree;
    $this->accessManager = $access_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Generates a list of admin tasks offered by a specified module.
   *
   * @param string $module
   *   The module name.
   *
   * @return array
   *   An array of task links.
   */
  public function getModuleAdminTasks(string $module): array {
    if (!isset($this->menuTree)) {
      $parameters = (new MenuTreeParameters())
        ->setRoot('system.admin')
        ->excludeRoot()
        ->onlyEnabledLinks();
      $this->menuTree = $this->menuLinkTree->load('system.admin', $parameters);
      $manipulators = [
        ['callable' => 'menu.default_tree_manipulators:checkAccess'],
        ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
        ['callable' => 'menu.default_tree_manipulators:flatten'],
      ];
      $this->menuTree = $this->menuLinkTree->transform($this->menuTree, $manipulators);
    }

    $admin_tasks = [];
    foreach ($this->menuTree as $element) {
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
    if (\Drupal::getContainer()->get('user.permissions')->moduleProvidesPermissions($module)) {
      if ($this->accessManager->checkNamedRoute('user.admin_permissions')) {
        $url = new Url('user.admin_permissions');
        $url->setOption('fragment', 'module-' . $module);
        $admin_tasks["user.admin_permissions.$module"] = [
          'title' => $this->t('Configure @module permissions', [
            '@module' => $this->moduleHandler->getName($module),
          ]),
          'description' => '',
          'url' => $url,
        ];
      }
    }

    return $admin_tasks;
  }

}
