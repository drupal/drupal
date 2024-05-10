<?php

declare(strict_types=1);

namespace Drupal\system;

use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;

/**
 * Provides a helper for generating module admin links.
 */
class ModuleAdminLinksHelper {

  /**
   * The cache key for the menu tree.
   */
  const ADMIN_LINKS_MENU_TREE = 'admin_links_menu_tree';

  /**
   * Constructs a new service instance.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menuLinkTree
   *   The menu link tree service.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memoryCache
   *   The memory cache.
   */
  public function __construct(
    protected MenuLinkTreeInterface $menuLinkTree,
    protected MemoryCacheInterface $memoryCache,
  ) {}

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
    // Cache the menu tree as it is expensive to load.
    /** @var \Drupal\Core\Menu\MenuLinkTreeElement[]|false $menuTree */
    $cacheItem = $this->memoryCache->get(self::ADMIN_LINKS_MENU_TREE);
    if ($cacheItem) {
      $menuTree = $cacheItem->data;
    }
    else {
      $parameters = (new MenuTreeParameters())
        ->setRoot('system.admin')
        ->excludeRoot()
        ->onlyEnabledLinks();
      $menuTree = $this->menuLinkTree->load('system.admin', $parameters);
      $manipulators = [
        ['callable' => 'menu.default_tree_manipulators:checkAccess'],
        ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
        ['callable' => 'menu.default_tree_manipulators:flatten'],
      ];
      $menuTree = $this->menuLinkTree->transform($menuTree, $manipulators);
      $this->memoryCache->set(self::ADMIN_LINKS_MENU_TREE, $menuTree);
    }

    $admin_tasks = [];
    foreach ($menuTree as $element) {
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
