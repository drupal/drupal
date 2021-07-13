<?php

namespace Drupal\system;

use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;

/**
 * Provides module admin links.
 */
class ModuleAdminLinks {

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

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
   */
  public function __construct(MenuLinkTreeInterface $menu_link_tree) {
    $this->menuLinkTree = $menu_link_tree;
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
  public function getModuleAdminLinks(string $module): array {
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

    return $admin_tasks;
  }

}
