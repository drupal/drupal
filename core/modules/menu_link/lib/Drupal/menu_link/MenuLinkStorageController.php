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
use Drupal\field\FieldInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;

/**
 * Controller class for menu links.
 *
 * This extends the Drupal\entity\DatabaseStorageController class, adding
 * required special handling for menu_link entities.
 */
class MenuLinkStorageController extends DatabaseStorageController implements MenuLinkStorageControllerInterface {

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
   * @param \Drupal\field\FieldInfo $field_info
   *   The field info service.
   * @param \Symfony\Cmf\Component\Routing\RouteProviderInterface $route_provider
   *   The route provider service.
   */
  public function __construct($entity_type, array $entity_info, Connection $database, FieldInfo $field_info, RouteProviderInterface $route_provider) {
    parent::__construct($entity_type, $entity_info, $database, $field_info);

    $this->routeProvider = $route_provider;

    if (empty(static::$routerItemFields)) {
      static::$routerItemFields = array_diff(drupal_schema_fields_sql('menu_router'), array('weight'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function create(array $values) {
    // The bundle of menu links being the menu name is not enforced but is the
    // default behavior if no bundle is set.
    if (!isset($values['bundle']) && isset($values['menu_name'])) {
      $values['bundle'] = $values['menu_name'];
    }
    return parent::create($values);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $entity_type,
      $entity_info,
      $container->get('database'),
      $container->get('field.info'),
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
      $menu_link->route_parameters = unserialize($menu_link->route_parameters);

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
    $entity_class = $this->entityInfo['class'];

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
      $this->invokeFieldMethod('preSave', $entity);
      $this->invokeHook('presave', $entity);
      $entity->preSave($this);

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
            $entity->postSave($this, TRUE);
            $this->invokeFieldMethod('update', $entity);
            $this->saveFieldItems($entity, TRUE);
            $this->invokeHook('update', $entity);
          }
          else {
            $return = SAVED_NEW;
            $this->resetCache();

            $entity->enforceIsNew(FALSE);
            $entity->postSave($this, FALSE);
            $this->invokeFieldMethod('insert', $entity);
            $this->saveFieldItems($entity, FALSE);
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
   * {@inheritdoc}
   */
  public function setPreventReparenting($value = FALSE) {
    $this->preventReparenting = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreventReparenting() {
    return $this->preventReparenting;
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
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

    return $this->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function updateParentalStatus(EntityInterface $entity, $exclude = FALSE) {
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
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function moveChildren(EntityInterface $entity) {
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
   * {@inheritdoc}
   */
  public function countMenuLinks($menu_name) {
    $query = \Drupal::entityQuery($this->entityType);
    $query
      ->condition('menu_name', $menu_name)
      ->count();
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getParentFromHierarchy(EntityInterface $entity) {
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
        $parent = $this->load(reset($result));
      }
    } while ($parent === FALSE && $parent_path);

    return $parent;
  }

}
