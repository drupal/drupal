<?php

namespace Drupal\Core\Menu;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a couple of menu link tree manipulators.
 *
 * This class provides menu link tree manipulators to:
 * - perform render cached menu-optimized access checking
 * - optimized node access checking
 * - generate a unique index for the elements in a tree and sorting by it
 * - flatten a tree (i.e. a 1-dimensional tree)
 */
class DefaultMenuLinkTreeManipulators {

  /**
   * Constructs a \Drupal\Core\Menu\DefaultMenuLinkTreeManipulators object.
   *
   * @param \Drupal\Core\Access\AccessManagerInterface $accessManager
   *   The access manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    protected AccessManagerInterface $accessManager,
    protected AccountInterface $account,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ModuleHandlerInterface $moduleHandler,
  ) {
  }

  /**
   * Performs access checks of a menu tree.
   *
   * Sets the 'access' property to AccessResultInterface objects on menu link
   * tree elements. Descends into subtrees if the root of the subtree is
   * accessible. Inaccessible subtrees are deleted, except the top-level
   * inaccessible link, to be compatible with render caching.
   *
   * (This means that top-level inaccessible links are *not* removed; it is up
   * to the code doing something with the tree to exclude inaccessible links,
   * just like MenuLinkTree::build() does. This allows those things to specify
   * the necessary cacheability metadata.)
   *
   * This is compatible with render caching, because of cache context bubbling:
   * conditionally defined cache contexts (i.e. subtrees that are only
   * accessible to some users) will bubble just like they do for render arrays.
   * This is why inaccessible subtrees are deleted, except at the top-level
   * inaccessible link: if we didn't keep the first (depth-wise) inaccessible
   * link, we wouldn't be able to know which cache contexts would cause those
   * subtrees to become accessible again, thus forcing us to conclude that the
   * subtree is unconditionally inaccessible.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu link tree to manipulate.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The manipulated menu link tree.
   */
  public function checkAccess(array $tree) {
    foreach ($tree as $key => $element) {
      // Other menu tree manipulators may already have calculated access, do not
      // overwrite the existing value in that case.
      if (!isset($element->access)) {
        $tree[$key]->access = $this->menuLinkCheckAccess($element->link);
      }
      if ($tree[$key]->access->isAllowed()) {
        if ($tree[$key]->subtree) {
          $tree[$key]->subtree = $this->checkAccess($tree[$key]->subtree);
        }
      }
      else {
        // Replace the link with an InaccessibleMenuLink object, so that if it
        // is accidentally rendered, no sensitive information is divulged.
        $tree[$key]->link = new InaccessibleMenuLink($tree[$key]->link);
        // Always keep top-level inaccessible links: their cacheability metadata
        // that indicates why they're not accessible by the current user must be
        // bubbled. Otherwise, those subtrees will not be varied by any cache
        // contexts at all, therefore forcing them to remain empty for all users
        // unless some other part of the menu link tree accidentally varies by
        // the same cache contexts.
        // For deeper levels, we *can* remove the subtrees and therefore also
        // not perform access checking on the subtree, thanks to bubbling/cache
        // redirects. This therefore allows us to still do significantly less
        // work in case of inaccessible subtrees, which is the entire reason why
        // this deletes subtrees in the first place.
        $tree[$key]->subtree = [];
      }
    }
    return $tree;
  }

  /**
   * Performs access checking for nodes in an optimized way.
   *
   * This manipulator should be added before the generic ::checkAccess() one,
   * because it provides a performance optimization for ::checkAccess().
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu link tree to manipulate.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The manipulated menu link tree.
   */
  public function checkNodeAccess(array $tree) {
    $node_links = [];
    $this->collectNodeLinks($tree, $node_links);
    if ($node_links) {
      $nids = array_keys($node_links);

      $query = $this->entityTypeManager->getStorage('node')->getQuery();
      $query->accessCheck(TRUE);
      $query->condition('nid', $nids, 'IN');

      // Allows admins to view all nodes, by both disabling node_access
      // query rewrite as well as not checking for the node status. The
      // 'view own unpublished nodes' permission is ignored to not require cache
      // entries per user.
      $access_result = AccessResult::allowed()->cachePerPermissions();
      if ($this->account->hasPermission('bypass node access')) {
        $query->accessCheck(FALSE);
      }
      else {
        $access_result->addCacheContexts(['user.node_grants:view']);
        if (!$this->moduleHandler->hasImplementations('node_grants') && !$this->account->hasPermission('view any unpublished content')) {
          $query->condition('status', NodeInterface::PUBLISHED);
        }
      }

      $nids = $query->execute();
      foreach ($nids as $nid) {
        foreach ($node_links[$nid] as $key => $link) {
          $node_links[$nid][$key]->access = $access_result;
        }
      }
    }

    return $tree;
  }

  /**
   * Collects the node links in the menu tree.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu link tree to manipulate.
   * @param array $node_links
   *   Stores references to menu link elements to effectively set access.
   */
  protected function collectNodeLinks(array &$tree, array &$node_links) {
    foreach ($tree as $key => &$element) {
      if ($element->link->getRouteName() == 'entity.node.canonical') {
        $nid = $element->link->getRouteParameters()['node'];
        $node_links[$nid][$key] = $element;
        // Deny access by default. checkNodeAccess() will re-add it.
        $element->access = AccessResult::neutral();
      }
      if ($element->hasChildren) {
        $this->collectNodeLinks($element->subtree, $node_links);
      }
    }
  }

  /**
   * Checks access for one menu link instance.
   *
   * @param \Drupal\Core\Menu\MenuLinkInterface $instance
   *   The menu link instance.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function menuLinkCheckAccess(MenuLinkInterface $instance) {
    $url = $instance->getUrlObject();

    if ($url->isRouted()) {
      return $this->accessManager->checkNamedRoute($url->getRouteName(), $url->getRouteParameters(), $this->account, TRUE);
    }

    // Must be an external link.
    return AccessResult::allowed();
  }

  /**
   * Generates a unique index and sorts by it.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu link tree to manipulate.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The manipulated menu link tree.
   */
  public function generateIndexAndSort(array $tree) {
    $new_tree = [];
    foreach ($tree as $key => $v) {
      if ($tree[$key]->subtree) {
        $tree[$key]->subtree = $this->generateIndexAndSort($tree[$key]->subtree);
      }
      $instance = $tree[$key]->link;
      // The weights are made a uniform 5 digits by adding 50000 as an offset.
      // After $this->menuLinkCheckAccess(), $instance->getTitle() has the
      // localized or translated title. Adding the plugin id to the end of the
      // index insures that it is unique.
      $new_tree[(50000 + $instance->getWeight()) . ' ' . $instance->getTitle() . ' ' . $instance->getPluginId()] = $tree[$key];
    }
    ksort($new_tree);
    return $new_tree;
  }

  /**
   * Flattens the tree to a single level.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu link tree to manipulate.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The manipulated menu link tree.
   */
  public function flatten(array $tree) {
    foreach ($tree as $key => $element) {
      if ($tree[$key]->subtree) {
        $tree += $this->flatten($tree[$key]->subtree);
      }
      $tree[$key]->subtree = [];
    }
    return $tree;
  }

}
