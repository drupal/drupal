<?php

namespace Drupal\Core\Menu;

/**
 * Provides a value object to model an element in a menu link tree.
 *
 * \Drupal\Core\Menu\MenuLinkTreeElement objects represent a menu link's data.
 * Objects of this class provide complimentary data: the placement in a tree.
 * Therefore, we can summarize this split as follows:
 * - Menu link objects contain all information about an individual menu link,
 *   plus what their parent is. But they don't know where exactly in a menu link
 *   tree they live.
 * - Instances of this class are complimentary to those objects, they know:
 *   - All additional metadata from {menu_tree}, which contains "materialized"
 *     metadata about a menu link tree, such as whether a link in the tree has
 *     visible children and the depth relative to the root.
 *   - Plus all additional metadata that's adjusted for the current tree query,
 *     such as whether the link is in the active trail, whether the link is
 *     accessible for the current user, and the link's children (which are only
 *     loaded if the link was marked as "expanded" by the query).
 *
 * @see \Drupal\Core\Menu\MenuTreeStorage::loadTreeData()
 */
class MenuLinkTreeElement {

  /**
   * The menu link for this element in a menu link tree.
   *
   * @var \Drupal\Core\Menu\MenuLinkInterface
   */
  public $link;

  /**
   * The subtree of this element in the menu link tree (this link's children).
   *
   * (Children of a link are only loaded if a link is marked as "expanded" by
   * the query.)
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeElement[]
   */
  public $subtree;

  /**
   * The depth of this link relative to the root of the tree.
   *
   * @var int
   */
  public $depth;

  /**
   * Whether this link has any children at all.
   *
   * @var bool
   */
  public $hasChildren;

  /**
   * Whether this link is in the active trail.
   *
   * @var bool
   */
  public $inActiveTrail;

  /**
   * Whether this link is accessible by the current user.
   *
   * If the value is NULL the access was not determined yet, if an access result
   * object, it was determined already.
   *
   * @var \Drupal\Core\Access\AccessResultInterface|null
   */
  public $access;

  /**
   * Additional options for this link.
   *
   * This is merged (\Drupal\Component\Utility\NestedArray::mergeDeep()) with
   * \Drupal\Core\Menu\MenuLinkInterface::getOptions(), to allow menu link tree
   * manipulators to add or override link options.
   */
  public $options = [];

  /**
   * Constructs a new \Drupal\Core\Menu\MenuLinkTreeElement.
   *
   * @param \Drupal\Core\Menu\MenuLinkInterface $link
   *   The menu link for this element in the menu link tree.
   * @param bool $has_children
   *   A flag as to whether this element has children even if they are not
   *   included in the tree (i.e. this may be TRUE even if $subtree is empty).
   * @param int $depth
   *   The depth of this element relative to the tree root.
   * @param bool $in_active_trail
   *   A flag as to whether this link was included in the list of active trail
   *   IDs used to build the tree.
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $subtree
   *   The children of this element in the menu link tree.
   */
  public function __construct(MenuLinkInterface $link, $has_children, $depth, $in_active_trail, array $subtree) {
    // Essential properties.
    $this->link = $link;
    $this->hasChildren = $has_children;
    $this->depth = $depth;
    $this->subtree = $subtree;
    $this->inActiveTrail = $in_active_trail;
  }

  /**
   * Counts all menu links in the current subtree.
   *
   * @return int
   *   The number of menu links in this subtree (one plus the number of menu
   *   links in all descendants).
   */
  public function count() {
    $sum = function ($carry, MenuLinkTreeElement $element) {
      return $carry + $element->count();
    };
    return 1 + array_reduce($this->subtree, $sum);
  }

}
