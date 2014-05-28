<?php

/**
 * @file
 * Contains \Drupal\menu_link\Entity\MenuLink.
 */

namespace Drupal\menu_link\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Url;
use Drupal\menu_link\MenuLinkInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines the menu link entity class.
 *
 * @EntityType(
 *   id = "menu_link",
 *   label = @Translation("Menu link"),
 *   controllers = {
 *     "storage" = "Drupal\menu_link\MenuLinkStorage",
 *     "access" = "Drupal\menu_link\MenuLinkAccessController",
 *     "form" = {
 *       "default" = "Drupal\menu_link\MenuLinkForm"
 *     }
 *   },
 *   admin_permission = "administer menu",
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
   * An optional machine name if defined via the menu_link.static service.
   *
   * @var string
   */
  public $machine_name;

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
  public $module = 'menu_ui';

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
  public $route_parameters = array();

  /**
   * The route object associated with this menu link, if any.
   *
   * @var \Symfony\Component\Routing\Route
   */
  protected $routeObject;

  /**
   * Boolean indicating whether a new revision should be created on save.
   *
   * @var bool
   */
  protected $newRevision = FALSE;

  /**
   * Indicates whether this is the default revision.
   *
   * @var bool
   */
  protected $isDefaultRevision = TRUE;

  /**
   * {@inheritdoc}
   */
  public function setNewRevision($value = TRUE) {
    $this->newRevision = $value;
  }
  /**
   * {@inheritdoc}
   */
  public function isNewRevision() {
    return $this->newRevision || ($this->getEntityType()->hasKey('revision') && !$this->getRevisionId());
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    // @todo Inject the entity manager and retrieve bundle info from it.
    $bundles = entity_get_bundles($this->entityTypeId);
    return !empty($bundles[$this->bundle()]['translatable']);
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
  }

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
    // definition from the menu_link.static service. Otherwise, for example,
    // the link's menu would not be reset, because properties like the original
    // 'menu_name' are not stored anywhere else. Since resetting a link happens
    // rarely and this is a one-time operation, retrieving the full set of
    // default menu links does little harm.
    $all_links = \Drupal::service('menu_link.static')->getLinks();
    $original = $all_links[$this->machine_name];
    $original['machine_name'] = $this->machine_name;
    /** @var \Drupal\menu_link\MenuLinkStorageInterface $storage */
    $storage = \Drupal::entityManager()->getStorage($this->entityTypeId);
    // @todo Do not create a new entity in order to update it, see
    //   https://drupal.org/node/2241865
    $new_link = $storage->createFromDefaultLink($original);
    $new_link->setOriginalId($this->id());
    // Allow the menu to be determined by the parent
    if (!empty($new_link['parent']) && !empty($all_links[$new_link['parent']])) {
      // Walk up the tree to find the menu name.
      $parent = $all_links[$new_link['parent']];
      $existing_parent = db_select('menu_links')
        ->fields('menu_links')
        ->condition('machine_name', $parent['machine_name'])
        ->execute()->fetchAssoc();
      if ($existing_parent) {
        /** @var \Drupal\Core\Entity\EntityInterface $existing_parent */
        $existing_parent = $storage->create($existing_parent);
        $new_link->menu_name = $existing_parent->menu_name;
        $new_link->plid = $existing_parent->id();
      }
    }
    // Merge existing menu link's ID and 'has_children' property.
    foreach (array('mlid', 'has_children') as $key) {
      $new_link->{$key} = $this->{$key};
    }
    $new_link->save();
    return $new_link;
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
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    // Nothing to do if we don't want to reparent children.
    if ($storage->getPreventReparenting()) {
      return;
    }

    foreach ($entities as $entity) {
      // Children get re-attached to the item's parent.
      if ($entity->has_children) {
        $children = $storage->loadByProperties(array('plid' => $entity->plid));
        foreach ($children as $child) {
          $child->plid = $entity->plid;
          $storage->save($child);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Update the has_children status of the parent.
    foreach ($entities as $entity) {
      if (!$storage->getPreventReparenting()) {
        $storage->updateParentalStatus($entity);
      }
    }

    // Also clear the menu system static caches.
    menu_reset_static_cache();
    _menu_clear_page_cache();
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // This is the easiest way to handle the unique internal path '<front>',
    // since a path marked as external does not need to match a route.
    $this->external = (UrlHelper::isExternal($this->link_path) || $this->link_path == '<front>') ? 1 : 0;

    // Try to find a parent link. If found, assign it and derive its menu.
    $parent = $this->findParent($storage);
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
        $limit = MENU_MAX_DEPTH - $storage->findChildrenRelativeDepth($this->original) - 1;
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
      $storage->moveChildren($this);
    }

    // Find the route_name.
    if (!$this->external && !isset($this->route_name)) {
      $url = Url::createFromPath($this->link_path);
      $this->route_name = $url->getRouteName();
      $this->route_parameters = $url->getRouteParameters();
    }
    elseif (empty($this->link_path)) {
      $this->link_path = \Drupal::urlGenerator()->getPathFromRoute($this->route_name, $this->route_parameters);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Check the has_children status of the parent.
    $storage->updateParentalStatus($this);


    // Entity::postSave() calls Entity::invalidateTagsOnSave(), which only
    // handles the regular cases. The MenuLink entity has two special cases.
    $cache_tags = array();
    // Case 1: a newly created menu link is *also* added to a menu, so we must
    // invalidate the associated menu's cache tag.
    if (!$update) {
      $cache_tags = $this->getCacheTag();
    }
    // Case 2: a menu link may be moved from one menu to another; the original
    // menu's cache tag must also be invalidated.
    if (isset($this->original) && $this->menu_name != $this->original->menu_name) {
      $cache_tags = NestedArray::mergeDeep($cache_tags, $this->original->getCacheTag());
    }
    Cache::invalidateTags($cache_tags);

    // Also clear the menu system static caches.
    menu_reset_static_cache();

    // Now clear the cache.
    _menu_clear_page_cache();
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    parent::postLoad($storage, $entities);

    $routes = array();
    foreach ($entities as $menu_link) {
      $menu_link->options = unserialize($menu_link->options);
      $menu_link->route_parameters = unserialize($menu_link->route_parameters);

      // By default use the menu_name as type.
      $menu_link->bundle = $menu_link->menu_name;

      // For all links that have an associated route, load the route object now
      // and save it on the object. That way we avoid a select N+1 problem later.
      if ($menu_link->route_name) {
        $routes[$menu_link->id()] = $menu_link->route_name;
      }
    }

    // Now mass-load any routes needed and associate them.
    if ($routes) {
      $route_objects = \Drupal::service('router.route_provider')->getRoutesByNames($routes);
      foreach ($routes as $entity_id => $route) {
        // Not all stored routes will be valid on load.
        if (isset($route_objects[$route])) {
          $entities[$entity_id]->setRouteObject($route_objects[$route]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setParents(MenuLinkInterface $parent) {
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
  protected function findParent(EntityStorageInterface $storage) {
    $parent = FALSE;

    // This item is explicitly top-level, skip the rest of the parenting.
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
      $parent = $storage->load($mlid);
      if ($parent) {
        break;
      }
    }
    return $parent;
  }

  /**
   * Builds and returns the renderable array for this menu link.
   *
   * @return array
   *   A renderable array representing the content of the link.
   */
  public function build() {
    $build = array(
      '#type' => 'link',
      '#title' => $this->title,
      '#href' => $this->href,
      '#route_name' => $this->route_name ? $this->route_name : NULL,
      '#route_parameters' => $this->route_parameters,
      '#options' => !empty($this->localized_options) ? $this->localized_options : array(),
    );
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTag() {
    return entity_load('menu', $this->menu_name)->getCacheTag();
  }

  /**
   * {@inheritdoc}
   */
  public function getListCacheTags() {
    return entity_load('menu', $this->menu_name)->getListCacheTags();
  }

}
