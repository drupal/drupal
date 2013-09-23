<?php

/**
 * @file
 * Contains \Drupal\menu_link\Entity\MenuLink.
 */

namespace Drupal\menu_link\Entity;

use Drupal\menu_link\MenuLinkInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity;

/**
 * Defines the menu link entity class.
 *
 * @EntityType(
 *   id = "menu_link",
 *   label = @Translation("Menu link"),
 *   module = "menu_link",
 *   controllers = {
 *     "storage" = "Drupal\menu_link\MenuLinkStorageController",
 *     "access" = "Drupal\menu_link\MenuLinkAccessController",
 *     "render" = "Drupal\Core\Entity\EntityRenderController",
 *     "form" = {
 *       "default" = "Drupal\menu_link\MenuLinkFormController"
 *     }
 *   },
 *   static_cache = FALSE,
 *   base_table = "menu_links",
 *   uri_callback = "menu_link_uri",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "mlid",
 *     "label" = "link_title",
 *     "uuid" = "uuid",
 *     "bundle" = "bundle"
 *   },
 *   bundle_keys = {
 *     "bundle" = "bundle"
 *   }
 * )
 */
class MenuLink extends Entity implements \ArrayAccess, MenuLinkInterface {

  /**
   * The link's menu name.
   *
   * @var string
   */
  public $menu_name = 'tools';

  /**
   * The link's bundle.
   *
   * @var string
   */
  public $bundle = 'tools';

  /**
   * The menu link ID.
   *
   * @var int
   */
  public $mlid;

  /**
   * The menu link UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The parent link ID.
   *
   * @var int
   */
  public $plid;

  /**
   * The Drupal path or external path this link points to.
   *
   * @var string
   */
  public $link_path;

  /**
   * For links corresponding to a Drupal path (external = 0), this connects the
   * link to a {menu_router}.path for joins.
   *
   * @var string
   */
  public $router_path;

  /**
   * The entity label.
   *
   * @var string
   */
  public $link_title = '';

  /**
   * A serialized array of options to be passed to the url() or l() function,
   * such as a query string or HTML attributes.
   *
   * @var array
   */
  public $options = array();

  /**
   * The name of the module that generated this link.
   *
   * @var string
   */
  public $module = 'menu';

  /**
   * A flag for whether the link should be rendered in menus.
   *
   * @var int
   */
  public $hidden = 0;

  /**
   * A flag to indicate if the link points to a full URL starting with a
   * protocol, like http:// (1 = external, 0 = internal).
   *
   * @var int
   */
  public $external;

  /**
   * Flag indicating whether any links have this link as a parent.
   *
   * @var int
   */
  public $has_children = 0;

  /**
   * Flag for whether this link should be rendered as expanded in menus.
   * Expanded links always have their child links displayed, instead of only
   * when the link is in the active trail.
   *
   * @var int
   */
  public $expanded = 0;

  /**
   * Link weight among links in the same menu at the same depth.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * The depth relative to the top level. A link with plid == 0 will have
   * depth == 1.
   *
   * @var int
   */
  public $depth;

  /**
   * A flag to indicate that the user has manually created or edited the link.
   *
   * @var int
   */
  public $customized = 0;

  /**
   * The first entity ID in the materialized path.
   *
   * @var int
   *
   * @todo Investigate whether the p1, p2, .. pX properties can be moved to a
   * single array property.
   */
  public $p1;

  /**
   * The second entity ID in the materialized path.
   *
   * @var int
   */
  public $p2;

  /**
   * The third entity ID in the materialized path.
   *
   * @var int
   */
  public $p3;

  /**
   * The fourth entity ID in the materialized path.
   *
   * @var int
   */
  public $p4;

  /**
   * The fifth entity ID in the materialized path.
   *
   * @var int
   */
  public $p5;

  /**
   * The sixth entity ID in the materialized path.
   *
   * @var int
   */
  public $p6;

  /**
   * The seventh entity ID in the materialized path.
   *
   * @var int
   */
  public $p7;

  /**
   * The eighth entity ID in the materialized path.
   *
   * @var int
   */
  public $p8;

  /**
   * The ninth entity ID in the materialized path.
   *
   * @var int
   */
  public $p9;

  /**
   * The menu link modification timestamp.
   *
   * @var int
   */
  public $updated = 0;

  /**
   * The name of the route associated with this menu link, if any.
   *
   * @var string
   */
  public $route_name;

  /**
   * The parameters of the route associated with this menu link, if any.
   *
   * @var array
   */
  public $route_parameters;

  /**
   * The route object associated with this menu link, if any.
   *
   * @var \Symfony\Component\Routing\Route
   */
  protected $routeObject;

  /**
   * Overrides Entity::id().
   */
  public function id() {
    return $this->mlid;
  }

  /**
   * {@inheritdoc}
   */
  public function bundle() {
    return $this->bundle;
  }

  /**
   * Overrides Entity::createDuplicate().
   */
  public function createDuplicate() {
    $duplicate = parent::createDuplicate();
    $duplicate->plid = NULL;
    return $duplicate;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute() {
    if (!$this->route_name) {
      return NULL;
    }
    if (!($this->routeObject instanceof Route)) {
      $route_provider = \Drupal::service('router.route_provider');
      $this->routeObject = $route_provider->getRouteByName($this->route_name);
    }
    return $this->routeObject;
  }

  /**
   * {@inheritdoc}
   */
  public function setRouteObject(Route $route) {
    $this->routeObject = $route;
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    // To reset the link to its original values, we need to retrieve its
    // definition from hook_menu(). Otherwise, for example, the link's menu
    // would not be reset, because properties like the original 'menu_name' are
    // not stored anywhere else. Since resetting a link happens rarely and this
    // is a one-time operation, retrieving the full menu router does no harm.
    $menu = menu_get_router();
    $router_item = $menu[$this->router_path];
    $new_link = self::buildFromRouterItem($router_item);
    // Merge existing menu link's ID and 'has_children' property.
    foreach (array('mlid', 'has_children') as $key) {
      $new_link->{$key} = $this->{$key};
    }
    $new_link->save();
    return $new_link;
  }

  /**
   * {@inheritdoc}
   */
  public static function buildFromRouterItem(array $item) {
    // Suggested items are disabled by default.
    if ($item['type'] == MENU_SUGGESTED_ITEM) {
      $item['hidden'] = 1;
    }
    // Hide all items that are not visible in the tree.
    elseif (!($item['type'] & MENU_VISIBLE_IN_TREE)) {
      $item['hidden'] = -1;
    }
    // Note, we set this as 'system', so that we can be sure to distinguish all
    // the menu links generated automatically from entries in {menu_router}.
    $item['module'] = 'system';
    $item += array(
      'link_title' => $item['title'],
      'link_path' => $item['path'],
      'options' => empty($item['description']) ? array() : array('attributes' => array('title' => $item['description'])),
    );
    return \Drupal::entityManager()
      ->getStorageController('menu_link')->create($item);
  }

  /**
   * Implements ArrayAccess::offsetExists().
   */
  public function offsetExists($offset) {
    return isset($this->{$offset});
  }

  /**
   * Implements ArrayAccess::offsetGet().
   */
  public function &offsetGet($offset) {
    return $this->{$offset};
  }

  /**
   * Implements ArrayAccess::offsetSet().
   */
  public function offsetSet($offset, $value) {
    $this->{$offset} = $value;
  }

  /**
   * Implements ArrayAccess::offsetUnset().
   */
  public function offsetUnset($offset) {
    unset($this->{$offset});
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    parent::preDelete($storage_controller, $entities);

    // Nothing to do if we don't want to reparent children.
    if ($storage_controller->getPreventReparenting()) {
      return;
    }

    foreach ($entities as $entity) {
      // Children get re-attached to the item's parent.
      if ($entity->has_children) {
        $children = $storage_controller->loadByProperties(array('plid' => $entity->plid));
        foreach ($children as $child) {
          $child->plid = $entity->plid;
          $storage_controller->save($child);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    parent::postDelete($storage_controller, $entities);

    $affected_menus = array();
    // Update the has_children status of the parent.
    foreach ($entities as $entity) {
      if (!$storage_controller->getPreventReparenting()) {
        $storage_controller->updateParentalStatus($entity);
      }

      // Store all menu names for which we need to clear the cache.
      if (!isset($affected_menus[$entity->menu_name])) {
        $affected_menus[$entity->menu_name] = $entity->menu_name;
      }
    }

    foreach ($affected_menus as $menu_name) {
      menu_cache_clear($menu_name);
    }
    _menu_clear_page_cache();
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    parent::preSave($storage_controller);

    // This is the easiest way to handle the unique internal path '<front>',
    // since a path marked as external does not need to match a router path.
    $this->external = (url_is_external($this->link_path) || $this->link_path == '<front>') ? 1 : 0;

    // Try to find a parent link. If found, assign it and derive its menu.
    $parent_candidates = !empty($this->parentCandidates) ? $this->parentCandidates : array();
    $parent = $this->findParent($storage_controller, $parent_candidates);
    if ($parent) {
      $this->plid = $parent->id();
      $this->menu_name = $parent->menu_name;
    }
    // If no corresponding parent link was found, move the link to the top-level.
    else {
      $this->plid = 0;
    }

    // Directly fill parents for top-level links.
    if ($this->plid == 0) {
      $this->p1 = $this->id();
      for ($i = 2; $i <= MENU_MAX_DEPTH; $i++) {
        $parent_property = "p$i";
        $this->{$parent_property} = 0;
      }
      $this->depth = 1;
    }
    // Otherwise, ensure that this link's depth is not beyond the maximum depth
    // and fill parents based on the parent link.
    else {
      if ($this->has_children && $this->original) {
        $limit = MENU_MAX_DEPTH - $storage_controller->findChildrenRelativeDepth($this->original) - 1;
      }
      else {
        $limit = MENU_MAX_DEPTH - 1;
      }
      if ($parent->depth > $limit) {
        return FALSE;
      }
      $this->depth = $parent->depth + 1;
      $this->setParents($parent);
    }

    // Need to check both plid and menu_name, since plid can be 0 in any menu.
    if (isset($this->original) && ($this->plid != $this->original->plid || $this->menu_name != $this->original->menu_name)) {
      $storage_controller->moveChildren($this, $this->original);
    }
    // Find the router_path.
    if (empty($this->router_path) || empty($this->original) || (isset($this->original) && $this->original->link_path != $this->link_path)) {
      if ($this->external) {
        $this->router_path = '';
      }
      else {
        // Find the router path which will serve this path.
        $this->parts = explode('/', $this->link_path, MENU_MAX_PARTS);
        $this->router_path = _menu_find_router_path($this->link_path);
      }
    }
    // Find the route_name.
    if (!isset($this->route_name)) {
      if ($result = static::findRouteNameParameters($this->link_path)) {
        list($this->route_name, $this->route_parameters) = $result;
      }
      else {
        $this->route_name = '';
        $this->route_parameters = array();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    parent::postSave($storage_controller, $update);

    // Check the has_children status of the parent.
    $storage_controller->updateParentalStatus($this);

    menu_cache_clear($this->menu_name);
    if (isset($this->original) && $this->menu_name != $this->original->menu_name) {
      menu_cache_clear($this->original->menu_name);
    }

    // Now clear the cache.
    _menu_clear_page_cache();
  }

  /**
   * {@inheritdoc}
   */
  public static function findRouteNameParameters($link_path) {
    // Look up the route_name used for the given path.
    $request = Request::create('/' . $link_path);
    $request->attributes->set('_system_path', $link_path);
    try {
      // Use router.dynamic instead of router, because router will call the
      // legacy router which will call hook_menu() and you will get back to
      // this method.
      $result = \Drupal::service('router.dynamic')->matchRequest($request);
      $return = array();
      $return[] = isset($result['_route']) ? $result['_route'] : '';
      $return[] = $result['_raw_variables']->all();
      return $return;
    }
    catch (\Exception $e) {
      return array();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setParents(EntityInterface $parent) {
    $i = 1;
    while ($i < $this->depth) {
      $p = 'p' . $i++;
      $this->{$p} = $parent->{$p};
    }
    $p = 'p' . $i++;
    // The parent (p1 - p9) corresponding to the depth always equals the mlid.
    $this->{$p} = $this->id();
    while ($i <= MENU_MAX_DEPTH) {
      $p = 'p' . $i++;
      $this->{$p} = 0;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function findParent(EntityStorageControllerInterface $storage_controller, array $parent_candidates = array()) {
    $parent = FALSE;

    // This item is explicitely top-level, skip the rest of the parenting.
    if (isset($this->plid) && empty($this->plid)) {
      return $parent;
    }

    // If we have a parent link ID, try to use that.
    $candidates = array();
    if (isset($this->plid)) {
      $candidates[] = $this->plid;
    }

    // Else, if we have a link hierarchy try to find a valid parent in there.
    if (!empty($this->depth) && $this->depth > 1) {
      for ($depth = $this->depth - 1; $depth >= 1; $depth--) {
        $parent_property = "p$depth";
        $candidates[] = $this->$parent_property;
      }
    }

    foreach ($candidates as $mlid) {
      if (isset($parent_candidates[$mlid])) {
        $parent = $parent_candidates[$mlid];
      }
      else {
        $parent = $storage_controller->load($mlid);
      }
      if ($parent) {
        return $parent;
      }
    }

    // If everything else failed, try to derive the parent from the path
    // hierarchy. This only makes sense for links derived from menu router
    // items (ie. from hook_menu()).
    if ($this->module == 'system') {
      $parent = $storage_controller->getParentFromHierarchy($this);
    }

    return $parent;
  }


}
