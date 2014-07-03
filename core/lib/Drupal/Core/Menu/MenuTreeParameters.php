<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\MenuTreeParameters.
 */

namespace Drupal\Core\Menu;

/**
 * Provides a value object to model menu tree parameters.
 *
 * Menu tree parameters are used to determine the set of definitions to be
 * loaded from \Drupal\Core\Menu\MenuTreeStorageInterface. Hence they determine
 * the shape and content of the tree:
 * - which links should be expanded, i.e. subtrees will get loaded that may not
 *   be loaded otherwise
 * - which menu links are omitted, i.e. minimum and maximum depth
 *
 * @todo - add getter methods and make all properties protected.
 * @todo - define an interface instead of using the concrete class to type hint.
 */
class MenuTreeParameters {

  /**
   * A menu link plugin ID that should be used as the root.
   *
   * By default the "real" root (@code '' @encode) of a menu is used. But, when
   * only the descendants (subtree) of a certain menu link are needed, a custom
   * root can be specified.
   *
   * @var string
   */
  public $root = '';

  /**
   * The minimum depth of menu links in the resulting tree. Root-relative.
   *
   * Defaults to 1, which is the default to build a whole tree for a menu
   * (excluding the root).
   *
   * @var int|null
   */
  public $minDepth = NULL;

  /**
   * The maximum depth of menu links in the resulting tree. Root-relative.
   *
   * @var int|null
   */
  public $maxDepth = NULL;

  /**
   * An array of parent link ids to return only menu links that are children of
   * one of the menu link plugin IDs in this list. If empty, the whole menu tree
   * is built.
   *
   * Defaults to the empty array.
   *
   * @var string[]
   */
  public $expanded = array();

  /**
   * An array of menu link plugin IDs, representing the trail from the currently
   * active menu link to the ("real") root of that menu link's menu.
   *
   * Defaults to the empty array.
   *
   * @var string[]
   */
  public $activeTrail = array();

  /**
   * An associative array of custom query condition key/value pairs to restrict
   * the links loaded.
   *
   * Defaults to the empty array.
   *
   * @var array
   */
  public $conditions = array();

  /**
   * Sets a root; loads a menu tree with this menu link plugin ID as root.
   *
   * @param string $root
   *   A menu link plugin ID, or @code '' @endcode to use the "real" root.
   *
   * @return $this
   *
   * @codeCoverageIgnore
   */
  public function setRoot($root) {
    $this->root = (string) $root;
    return $this;
  }

  /**
   * Sets a minimum depth; loads a menu tree from the given level.
   *
   * @param int $min_depth
   *   The (root-relative) minimum depth to apply.
   *
   * @return $this
   */
  public function setMinDepth($min_depth) {
    $this->minDepth = max(1, $min_depth);
    return $this;
  }

  /**
   * Sets a minimum depth; loads a menu tree up to the given level.
   *
   * @param int $max_depth
   *   The (root-relative) maximum depth to apply.
   *
   * @return $this
   *
   * @codeCoverageIgnore
   */
  public function setMaxDepth($max_depth) {
    $this->maxDepth = $max_depth;
    return $this;
  }

  /**
   * Adds menu links to be expanded (whose children to show).
   *
   * @param string[] $expanded
   *   An array containing the links to be expanded: a list of menu link plugin
   *   IDs.
   *
   * @return $this
   */
  public function addExpanded(array $expanded) {
    $this->expanded = array_merge($this->expanded, $expanded);
    $this->expanded = array_unique($this->expanded);
    return $this;
  }

  /**
   * Sets the active trail.
   *
   * @param string[] $active_trail
   *   An array containing the active trail: a list of menu link plugin IDs.
   *
   * @return $this
   *
   * @see \Drupal\Core\Menu\MenuActiveTrail::getActiveTrailIds()
   *
   * @codeCoverageIgnore
   */
  public function setActiveTrail(array $active_trail) {
    $this->activeTrail = $active_trail;
    return $this;
  }

  /**
   * Adds a custom query condition.
   *
   * @param string $definition_field
   *   Only conditions that are testing menu link definition fields are allowed.
   * @param mixed $value
   *   The value to test the link definition field against. In most cases, this
   *   is a scalar. For more complex options, it is an array. The meaning of
   *   each element in the array is dependent on the $operator.
   * @param string|NULL $operator
   *   The comparison operator, such as =, <, or >=. It also accepts more
   *   complex options such as IN, LIKE, or BETWEEN.
   *
   * @return $this
   */
  public function addCondition($definition_field, $value, $operator = NULL) {
    if (!isset($operator)) {
      $this->conditions[$definition_field] = $value;
    }
    else {
      $this->conditions[$definition_field] = array($value, $operator);
    }
    return $this;
  }

  /**
   * Excludes hidden links.
   *
   * @return $this
   */
  public function excludeHiddenLinks() {
    $this->addCondition('hidden', 0);
    return $this;
  }

  /**
   * Ensures only the top level of the tree is loaded.
   *
   * @return $this
   */
  public function topLevelOnly() {
    $this->setMaxDepth(1);
    return $this;
  }

  /**
   * Excludes the root menu link from the tree.
   *
   * Note that this is only necessary when you specified a custom root, because
   * the "real" root (@code '' @encode) is mapped to a non-existing menu link.
   * Hence when loading a menu link tree without specifying a custom root, you
   * will never get a root; the tree will start at the children.
   *
   * @return $this
   */
  public function excludeRoot() {
    $this->setMinDepth(1);
    return $this;
  }

}
