<?php

/**
 * @file
 * Contains \Drupal\menu_link\MenuLinkStorageController.
 */

namespace Drupal\menu_link;

use Drupal\Core\Entity\DatabaseStorageController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller class for menu links.
 *
 * This extends the Drupal\entity\DatabaseStorageController class, adding
 * required special handling for menu_link entities.
 */
class MenuLinkStorageController extends DatabaseStorageController {

  /**
   * Indicates whether the delete operation should re-parent children items.
   *
   * @var bool
   */
  protected $preventReparenting = FALSE;

  /**
   * Holds an array of router item schema fields.
   *
   * @var array
   */
  protected static $routerItemFields = array();

  /**
   * The route provider service.
   *
   * @var \Symfony\Cmf\Component\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Overrides DatabaseStorageController::__construct().
   *
   * @param string $entity_type
   *   The entity type for which the instance is created.
   * @param array $entity_info
   *   An array of entity info for the entity type.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Symfony\Cmf\Component\Routing\RouteProviderInterface $route_provider
   *   The route provider service.
   */
  public function __construct($entity_type, array $entity_info, Connection $database, RouteProviderInterface $route_provider) {
    parent::__construct($entity_type, $entity_info, $database);

    $this->routeProvider = $route_provider;

    if (empty(static::$routerItemFields)) {
      static::$routerItemFields = array_diff(drupal_schema_fields_sql('menu_router'), array('weight'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $entity_type,
      $entity_info,
      $container->get('database'),
      $container->get('router.route_provider')
    );
  }

  /**
   * Overrides DatabaseStorageController::buildQuery().
   */
  protected function buildQuery($ids, $revision_id = FALSE) {
    $query = parent::buildQuery($ids, $revision_id);
    // Specify additional fields from the {menu_router} table.
    $query->leftJoin('menu_router', 'm', 'base.router_path = m.path');
    $query->fields('m', static::$routerItemFields);
    return $query;
  }

  /**
   * Overrides DatabaseStorageController::attachLoad().
   *
   * @todo Don't call parent::attachLoad() at all because we want to be able to
   * control the entity load hooks.
   */
  protected function attachLoad(&$menu_links, $load_revision = FALSE) {
    $routes = array();

    foreach ($menu_links as &$menu_link) {
      $menu_link->options = unserialize($menu_link->options);

      // Use the weight property from the menu link.
      $menu_link->router_item['weight'] = $menu_link->weight;

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
      $route_objects = $this->routeProvider->getRoutesByNames($routes);
      foreach ($routes as $entity_id => $route) {
        // Not all stored routes will be valid on load.
        if (isset($route_objects[$route])) {
          $menu_links[$entity_id]->setRouteObject($route_objects[$route]);
        }
      }
    }

    parent::attachLoad($menu_links, $load_revision);
  }

  /**
   * Overrides DatabaseStorageController::save().
   */
  public function save(EntityInterface $entity) {
    // We return SAVED_UPDATED by default because the logic below might not
    // update the entity if its values haven't changed, so returning FALSE
    // would be confusing in that situation.
    $return = SAVED_UPDATED;

    $transaction = $this->database->startTransaction();
    try {
      // Load the stored entity, if any.
      if (!$entity->isNew() && !isset($entity->original)) {
        $entity->original = entity_load_unchanged($this->entityType, $entity->id());
      }

      if ($entity->isNew()) {
        $entity->mlid = $this->database->insert($this->entityInfo['base_table'])->fields(array('menu_name' => 'tools'))->execute();
        $entity->enforceIsNew();
      }

      // Unlike the save() method from DatabaseStorageController, we invoke the
      // 'presave' hook first because we want to allow modules to alter the
      // entity before all the logic from our preSave() method.
      $this->invokeHook('presave', $entity);
      $this->preSave($entity);

      // If every value in $entity->original is the same in the $entity, there
      // is no reason to run the update queries or clear the caches. We use
      // array_intersect_key() with the $entity as the first parameter because
      // $entity may have additional keys left over from building a router entry.
      // The intersect removes the extra keys, allowing a meaningful comparison.
      if ($entity->isNew() || (array_intersect_key(get_object_vars($entity), get_object_vars($entity->original)) != get_object_vars($entity->original))) {
        $return = drupal_write_record($this->entityInfo['base_table'], $entity, $this->idKey);

        if ($return) {
          if (!$entity->isNew()) {
            $this->resetCache(array($entity->{$this->idKey}));
            $this->postSave($entity, TRUE);
            $this->invokeHook('update', $entity);
          }
          else {
            $return = SAVED_NEW;
            $this->resetCache();

            $entity->enforceIsNew(FALSE);
            $this->postSave($entity, FALSE);
            $this->invokeHook('insert', $entity);
          }
        }
      }

      // Ignore slave server temporarily.
      db_ignore_slave();
      unset($entity->original);

      return $return;
    }
    catch (\Exception $e) {
      $transaction->rollback();
      watchdog_exception($this->entityType, $e);
      throw new EntityStorageException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * Overrides DatabaseStorageController::preSave().
   */
  protected function preSave(EntityInterface $entity) {
    // This is the easiest way to handle the unique internal path '<front>',
    // since a path marked as external does not need to match a router path.
    $entity->external = (url_is_external($entity->link_path) || $entity->link_path == '<front>') ? 1 : 0;

    // Try to find a parent link. If found, assign it and derive its menu.
    $parent_candidates = !empty($entity->parentCandidates) ? $entity->parentCandidates : array();
    $parent = $this->findParent($entity, $parent_candidates);
    if ($parent) {
      $entity->plid = $parent->id();
      $entity->menu_name = $parent->menu_name;
    }
    // If no corresponding parent link was found, move the link to the top-level.
    else {
      $entity->plid = 0;
    }

    // Directly fill parents for top-level links.
    if ($entity->plid == 0) {
      $entity->p1 = $entity->id();
      for ($i = 2; $i <= MENU_MAX_DEPTH; $i++) {
        $parent_property = "p$i";
        $entity->$parent_property = 0;
      }
      $entity->depth = 1;
    }
    // Otherwise, ensure that this link's depth is not beyond the maximum depth
    // and fill parents based on the parent link.
    else {
      if ($entity->has_children && $entity->original) {
        $limit = MENU_MAX_DEPTH - $this->findChildrenRelativeDepth($entity->original) - 1;
      }
      else {
        $limit = MENU_MAX_DEPTH - 1;
      }
      if ($parent->depth > $limit) {
        return FALSE;
      }
      $entity->depth = $parent->depth + 1;
      $this->setParents($entity, $parent);
    }

    // Need to check both plid and menu_name, since plid can be 0 in any menu.
    if (isset($entity->original) && ($entity->plid != $entity->original->plid || $entity->menu_name != $entity->original->menu_name)) {
      $this->moveChildren($entity, $entity->original);
    }
    // Find the router_path.
    if (empty($entity->router_path) || empty($entity->original) || (isset($entity->original) && $entity->original->link_path != $entity->link_path)) {
      if ($entity->external) {
        $entity->router_path = '';
      }
      else {
        // Find the router path which will serve this path.
        $entity->parts = explode('/', $entity->link_path, MENU_MAX_PARTS);
        $entity->router_path = _menu_find_router_path($entity->link_path);
      }
    }
    // Find the route_name.
    if (!isset($entity->route_name)) {
      $entity->route_name = $this->findRouteName($entity->link_path);
    }
  }

  /**
   * Returns the route_name matching a URL.
   *
   * @param string $link_path
   *   The link path to find a route name for.
   *
   * @return string
   *   The route name.
   */
  protected function findRouteName($link_path) {
    // Look up the route_name used for the given path.
    $request = Request::create('/' . $link_path);
    $request->attributes->set('system_path', $link_path);
    try {
      // Use router.dynamic instead of router, because router will call the
      // legacy router which will call hook_menu() and you will get back to
      // this method.
      $result = \Drupal::service('router.dynamic')->matchRequest($request);
      return isset($result['_route']) ? $result['_route'] : '';
    }
    catch (\Exception $e) {
      return '';
    }

  }

  /**
   * DatabaseStorageController::postSave().
   */
  function postSave(EntityInterface $entity, $update) {
    // Check the has_children status of the parent.
    $this->updateParentalStatus($entity);

    menu_cache_clear($entity->menu_name);
    if (isset($entity->original) && $entity->menu_name != $entity->original->menu_name) {
      menu_cache_clear($entity->original->menu_name);
    }

    // Now clear the cache.
    _menu_clear_page_cache();
  }

  /**
   * Sets an internal flag that allows us to prevent the reparenting operations
   * executed during deletion.
   *
   * @param bool $value
   */
  public function preventReparenting($value = FALSE) {
    $this->preventReparenting = $value;
  }

  /**
   * Overrides DatabaseStorageController::preDelete().
   */
  protected function preDelete($entities) {
    // Nothing to do if we don't want to reparent children.
    if ($this->preventReparenting) {
      return;
    }

    foreach ($entities as $entity) {
      // Children get re-attached to the item's parent.
      if ($entity->has_children) {
        $children = $this->loadByProperties(array('plid' => $entity->plid));
        foreach ($children as $child) {
          $child->plid = $entity->plid;
          $this->save($child);
        }
      }
    }
  }

  /**
   * Overrides DatabaseStorageController::postDelete().
   */
  protected function postDelete($entities) {
    $affected_menus = array();
    // Update the has_children status of the parent.
    foreach ($entities as $entity) {
      if (!$this->preventReparenting) {
        $this->updateParentalStatus($entity);
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
   * Loads updated and customized menu links for specific router paths.
   *
   * Note that this is a low-level method and it doesn't return fully populated
   * menu link entities. (e.g. no fields are attached)
   *
   * @param array $router_paths
   *   An array of router paths.
   *
   * @return array
   *   An array of menu link objects indexed by their ids.
   */
  public function loadUpdatedCustomized(array $router_paths) {
    $query = parent::buildQuery(NULL);
    $query
      ->condition(db_or()
      ->condition('updated', 1)
      ->condition(db_and()
        ->condition('router_path', $router_paths, 'NOT IN')
        ->condition('external', 0)
        ->condition('customized', 1)
        )
      );
    $query_result = $query->execute();

    if (!empty($this->entityInfo['class'])) {
      // We provide the necessary arguments for PDO to create objects of the
      // specified entity class.
      // @see Drupal\Core\Entity\EntityInterface::__construct()
      $query_result->setFetchMode(\PDO::FETCH_CLASS, $this->entityInfo['class'], array(array(), $this->entityType));
    }

    return $query_result->fetchAllAssoc($this->idKey);
  }

  /**
   * Loads system menu link as needed by system_get_module_admin_tasks().
   *
   * @return array
   *   An array of menu link entities indexed by their IDs.
   */
  public function loadModuleAdminTasks() {
    $query = $this->buildQuery(NULL);
    $query
      ->condition('base.link_path', 'admin/%', 'LIKE')
      ->condition('base.hidden', 0, '>=')
      ->condition('base.module', 'system')
      ->condition('m.number_parts', 1, '>')
      ->condition('m.page_callback', 'system_admin_menu_block_page', '<>');
    $ids = $query->execute()->fetchCol(1);

    return $this->load($ids);
  }

  /**
   * Checks and updates the 'has_children' property for the parent of a link.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A menu link entity.
   */
  protected function updateParentalStatus(EntityInterface $entity, $exclude = FALSE) {
    // If plid == 0, there is nothing to update.
    if ($entity->plid) {
      // Check if at least one visible child exists in the table.
      $query = \Drupal::entityQuery($this->entityType);
      $query
        ->condition('menu_name', $entity->menu_name)
        ->condition('hidden', 0)
        ->condition('plid', $entity->plid)
        ->count();

      if ($exclude) {
        $query->condition('mlid', $entity->id(), '<>');
      }

      $parent_has_children = ((bool) $query->execute()) ? 1 : 0;
      $this->database->update('menu_links')
        ->fields(array('has_children' => $parent_has_children))
        ->condition('mlid', $entity->plid)
        ->execute();
    }
  }

  /**
   * Finds a possible parent for a given menu link entity.
   *
   * Because the parent of a given link might not exist anymore in the database,
   * we apply a set of heuristics to determine a proper parent:
   *
   *  - use the passed parent link if specified and existing.
   *  - else, use the first existing link down the previous link hierarchy
   *  - else, for system menu links (derived from hook_menu()), reparent
   *    based on the path hierarchy.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A menu link entity.
   * @param array $parent_candidates
   *   An array of menu link entities keyed by mlid.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   *   A menu link entity structure of the possible parent or FALSE if no valid
   *   parent has been found.
   */
  protected function findParent(EntityInterface $entity, array $parent_candidates = array()) {
    $parent = FALSE;

    // This item is explicitely top-level, skip the rest of the parenting.
    if (isset($entity->plid) && empty($entity->plid)) {
      return $parent;
    }

    // If we have a parent link ID, try to use that.
    $candidates = array();
    if (isset($entity->plid)) {
      $candidates[] = $entity->plid;
    }

    // Else, if we have a link hierarchy try to find a valid parent in there.
    if (!empty($entity->depth) && $entity->depth > 1) {
      for ($depth = $entity->depth - 1; $depth >= 1; $depth--) {
        $parent_property = "p$depth";
        $candidates[] = $entity->$parent_property;
      }
    }

    foreach ($candidates as $mlid) {
      if (isset($parent_candidates[$mlid])) {
        $parent = $parent_candidates[$mlid];
      }
      else {
        $parent = $this->load(array($mlid));
        $parent = reset($parent);
      }
      if ($parent) {
        return $parent;
      }
    }

    // If everything else failed, try to derive the parent from the path
    // hierarchy. This only makes sense for links derived from menu router
    // items (ie. from hook_menu()).
    if ($entity->module == 'system') {
      // Find the parent - it must be unique.
      $parent_path = $entity->link_path;
      do {
        $parent = FALSE;
        $parent_path = substr($parent_path, 0, strrpos($parent_path, '/'));

        $query = \Drupal::entityQuery($this->entityType);
        $query
          ->condition('mlid', $entity->id(), '<>')
          ->condition('module', 'system')
          // We always respect the link's 'menu_name'; inheritance for router
          // items is ensured in _menu_router_build().
          ->condition('menu_name', $entity->menu_name)
          ->condition('link_path', $parent_path);

        $result = $query->execute();
        // Only valid if we get a unique result.
        if (count($result) == 1) {
          $parent = $this->load($result);
          $parent = reset($parent);
        }
      } while ($parent === FALSE && $parent_path);
    }

    return $parent;
  }

  /**
   * Sets the p1 through p9 properties for a menu link entity being saved.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A menu link entity.
   * @param \Drupal\Core\Entity\EntityInterface $parent
   *   A menu link entity.
   */
  protected function setParents(EntityInterface $entity, EntityInterface $parent) {
    $i = 1;
    while ($i < $entity->depth) {
      $p = 'p' . $i++;
      $entity->{$p} = $parent->{$p};
    }
    $p = 'p' . $i++;
    // The parent (p1 - p9) corresponding to the depth always equals the mlid.
    $entity->{$p} = $entity->id();
    while ($i <= MENU_MAX_DEPTH) {
      $p = 'p' . $i++;
      $entity->{$p} = 0;
    }
  }

  /**
   * Finds the depth of an item's children relative to its depth.
   *
   * For example, if the item has a depth of 2 and the maximum of any child in
   * the menu link tree is 5, the relative depth is 3.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A menu link entity.
   *
   * @return int
   *   The relative depth, or zero.
   */
  public function findChildrenRelativeDepth(EntityInterface $entity) {
    // @todo Since all we need is a specific field from the base table, does it
    // make sense to convert to EFQ?
    $query = $this->database->select('menu_links');
    $query->addField('menu_links', 'depth');
    $query->condition('menu_name', $entity->menu_name);
    $query->orderBy('depth', 'DESC');
    $query->range(0, 1);

    $i = 1;
    $p = 'p1';
    while ($i <= MENU_MAX_DEPTH && $entity->{$p}) {
      $query->condition($p, $entity->{$p});
      $p = 'p' . ++$i;
    }

    $max_depth = $query->execute()->fetchField();

    return ($max_depth > $entity->depth) ? $max_depth - $entity->depth : 0;
  }

  /**
   * Updates the children of a menu link that is being moved.
   *
   * The menu name, parents (p1 - p6), and depth are updated for all children of
   * the link, and the has_children status of the previous parent is updated.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A menu link entity.
   */
  protected function moveChildren(EntityInterface $entity) {
    $query = $this->database->update($this->entityInfo['base_table']);

    $query->fields(array('menu_name' => $entity->menu_name));

    $p = 'p1';
    $expressions = array();
    for ($i = 1; $i <= $entity->depth; $p = 'p' . ++$i) {
      $expressions[] = array($p, ":p_$i", array(":p_$i" => $entity->{$p}));
    }
    $j = $entity->original->depth + 1;
    while ($i <= MENU_MAX_DEPTH && $j <= MENU_MAX_DEPTH) {
      $expressions[] = array('p' . $i++, 'p' . $j++, array());
    }
    while ($i <= MENU_MAX_DEPTH) {
      $expressions[] = array('p' . $i++, 0, array());
    }

    $shift = $entity->depth - $entity->original->depth;
    if ($shift > 0) {
      // The order of expressions must be reversed so the new values don't
      // overwrite the old ones before they can be used because "Single-table
      // UPDATE assignments are generally evaluated from left to right"
      // @see http://dev.mysql.com/doc/refman/5.0/en/update.html
      $expressions = array_reverse($expressions);
    }
    foreach ($expressions as $expression) {
      $query->expression($expression[0], $expression[1], $expression[2]);
    }

    $query->expression('depth', 'depth + :depth', array(':depth' => $shift));
    $query->condition('menu_name', $entity->original->menu_name);
    $p = 'p1';
    for ($i = 1; $i <= MENU_MAX_DEPTH && $entity->original->{$p}; $p = 'p' . ++$i) {
      $query->condition($p, $entity->original->{$p});
    }

    $query->execute();

    // Check the has_children status of the parent, while excluding this item.
    $this->updateParentalStatus($entity->original, TRUE);
  }

  /**
   * Returns the number of menu links from a menu.
   *
   * @param string $menu_name
   *   The unique name of a menu.
   */
  public function countMenuLinks($menu_name) {
    $query = \Drupal::entityQuery($this->entityType);
    $query
      ->condition('menu_name', $menu_name)
      ->count();
    return $query->execute();
  }
}
