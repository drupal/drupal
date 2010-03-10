<?php
// $Id: entity.inc,v 1.6 2010/03/10 14:04:26 dries Exp $

/**
 * Interface for entity controller classes.
 *
 * All entity controller classes specified via the 'controller class' key
 * returned by hook_entity_info() or hook_entity_info_alter() have to implement
 * this interface.
 *
 * Most simple, SQL-based entity controllers will do better by extending
 * DrupalDefaultEntityController instead of implementing this interface
 * directly.
 */
interface DrupalEntityControllerInterface {
  /**
   * Constructor.
   *
   * @param $entityType
   *   The entity type for which the instance is created.
   */
  public function __construct($entityType);

  /**
   * Reset the internal, static entity cache.
   */
  public function resetCache();

  /**
   * Load one or more entities.
   *
   * @param $ids
   *   An array of entity IDs, or FALSE to load all entities.
   * @param $conditions
   *   An array of conditions in the form 'field' => $value.
   *
   * @return
   *   An array of entity objects indexed by their ids.
   */
  public function load($ids = array(), $conditions = array());
}

/**
 * Default implementation of DrupalEntityControllerInterface.
 *
 * This class can be used as-is by most simple entity types. Entity types
 * requiring special handling can extend the class.
 */
class DrupalDefaultEntityController implements DrupalEntityControllerInterface {

  protected $entityCache;
  protected $entityType;
  protected $entityInfo;
  protected $hookLoadArguments;
  protected $idKey;
  protected $revisionKey;
  protected $revisionTable;

  /**
   * Constructor. Set basic variables.
   */
  public function __construct($entityType) {
    $this->entityType = $entityType;
    $this->entityInfo = entity_get_info($entityType);
    $this->entityCache = array();
    $this->hookLoadArguments = array();
    $this->idKey = $this->entityInfo['object keys']['id'];

    // Check if the entity type supports revisions.
    if (!empty($this->entityInfo['object keys']['revision'])) {
      $this->revisionKey = $this->entityInfo['object keys']['revision'];
      $this->revisionTable = $this->entityInfo['revision table'];
    }
    else {
      $this->revisionKey = FALSE;
    }

    // Check if the entity type supports static caching of loaded entities.
    $this->cache = !empty($this->entityInfo['static cache']);
  }

  public function resetCache() {
    $this->entityCache = array();
  }

  public function load($ids = array(), $conditions = array()) {
    $entities = array();

    // Revisions are not statically cached, and require a different query to
    // other conditions, so separate the revision id into its own variable.
    if ($this->revisionKey && isset($conditions[$this->revisionKey])) {
      $revision_id = $conditions[$this->revisionKey];
      unset($conditions[$this->revisionKey]);
    }
    else {
      $revision_id = FALSE;
    }

    // Create a new variable which is either a prepared version of the $ids
    // array for later comparison with the entity cache, or FALSE if no $ids
    // were passed. The $ids array is reduced as items are loaded from cache,
    // and we need to know if it's empty for this reason to avoid querying the
    // database when all requested entities are loaded from cache.
    $passed_ids = !empty($ids) ? array_flip($ids) : FALSE;
    // Try to load entities from the static cache, if the entity type supports
    // static caching.
    if ($this->cache && !$revision_id) {
      $entities += $this->cacheGet($ids, $conditions);
      // If any entities were loaded, remove them from the ids still to load.
      if ($passed_ids) {
        $ids = array_keys(array_diff_key($passed_ids, $entities));
      }
    }

    // Load any remaining entities from the database. This is the case if $ids
    // is set to FALSE (so we load all entities), if there are any ids left to
    // load, if loading a revision, or if $conditions was passed without $ids.
    if ($ids === FALSE || $ids || $revision_id || ($conditions && !$passed_ids)) {
      // Build the query.
      $query = $this->buildQuery($ids, $conditions, $revision_id);
      $queried_entities = $query
        ->execute()
        ->fetchAllAssoc($this->idKey);
    }

    // Pass all entities loaded from the database through $this->attachLoad(),
    // which attaches fields (if supported by the entity type) and calls the
    // entity type specific load callback, for example hook_node_load().
    if (!empty($queried_entities)) {
      $this->attachLoad($queried_entities, $revision_id);
      $entities += $queried_entities;
    }

    if ($this->cache) {
      // Add entities to the cache if we are not loading a revision.
      if (!empty($queried_entities) && !$revision_id) {
        $this->cacheSet($queried_entities);
      }
    }

    // Ensure that the returned array is ordered the same as the original
    // $ids array if this was passed in and remove any invalid ids.
    if ($passed_ids) {
      // Remove any invalid ids from the array.
      $passed_ids = array_intersect_key($passed_ids, $entities);
      foreach ($entities as $entity) {
        $passed_ids[$entity->{$this->idKey}] = $entity;
      }
      $entities = $passed_ids;
    }

    return $entities;
  }

  /**
   * Build the query to load the entity.
   *
   * This has full revision support. For entities requiring special queries,
   * the class can be extended, and the default query can be constructed by
   * calling parent::buildQuery(). This is usually necessary when the object
   * being loaded needs to be augmented with additional data from another
   * table, such as loading node type into comments or vocabulary machine name
   * into terms, however it can also support $conditions on different tables.
   * See CommentController::buildQuery() or TaxonomyTermController::buildQuery()
   * for examples.
   *
   * @return SelectQuery
   *   A SelectQuery object for loading the entity.
   */
  protected function buildQuery($ids, $conditions = array(), $revision_id = FALSE) {
    $query = db_select($this->entityInfo['base table'], 'base');

    $query->addTag($this->entityType . '_load_multiple');

    if ($revision_id) {
      $query->join($this->revisionTable, 'revision', "revision.{$this->idKey} = base.{$this->idKey} AND revision.{$this->revisionKey} = :revisionId", array(':revisionId' => $revision_id));
    }
    elseif ($this->revisionKey) {
      $query->join($this->revisionTable, 'revision', "revision.{$this->revisionKey} = base.{$this->revisionKey}");
    }

    // Add fields from the {entity} table.
    $entity_fields = drupal_schema_fields_sql($this->entityInfo['base table']);

    if ($this->revisionKey) {
      // Add all fields from the {entity_revision} table.
      $entity_revision_fields = drupal_map_assoc(drupal_schema_fields_sql($this->revisionTable));
      // The id field is provided by entity, so remove it.
      unset($entity_revision_fields[$this->idKey]);

      // Change timestamp to revision_timestamp, and revision uid to
      // revision_uid before adding them to the query.
      // TODO: This is node specific and has to be moved into NodeController.
      unset($entity_revision_fields['timestamp']);
      $query->addField('revision', 'timestamp', 'revision_timestamp');
      unset($entity_revision_fields['uid']);
      $query->addField('revision', 'uid', 'revision_uid');

      // Remove all fields from the base table that are also fields by the same
      // name in the revision table.
      $entity_field_keys = array_flip($entity_fields);
      foreach ($entity_revision_fields as $key => $name) {
        if (isset($entity_field_keys[$name])) {
          unset($entity_fields[$entity_field_keys[$name]]);
        }
      }
      $query->fields('revision', $entity_revision_fields);
    }

    $query->fields('base', $entity_fields);

    if ($ids) {
      $query->condition("base.{$this->idKey}", $ids, 'IN');
    }
    if ($conditions) {
      foreach ($conditions as $field => $value) {
        $query->condition('base.' . $field, $value);
      }
    }
    return $query;
  }

  /**
   * Attach data to entities upon loading.
   *
   * This will attach fields, if the entity is fieldable. It calls
   * hook_entity_load() for modules which need to add data to all entities.
   * It also calls hook_TYPE_load() on the loaded entities. For example
   * hook_node_load() or hook_user_load(). If your hook_TYPE_load()
   * expects special parameters apart from the queried entities, you can set
   * $this->hookLoadArguments prior to calling the method.
   * See NodeController::attachLoad() for an example.
   */
  protected function attachLoad(&$queried_entities, $revision_id = FALSE) {
    // Attach fields.
    if ($this->entityInfo['fieldable']) {
      if ($revision_id) {
        field_attach_load_revision($this->entityType, $queried_entities);
      }
      else {
        field_attach_load($this->entityType, $queried_entities);
      }
    }

    // Call hook_entity_load().
    foreach (module_implements('entity_load') as $module) {
      $function = $module . '_entity_load';
      $function($queried_entities, $this->entityType);
    }
    // Call hook_TYPE_load(). The first argument for hook_TYPE_load() are
    // always the queried entities, followed by additional arguments set in
    // $this->hookLoadArguments.
    $args = array_merge(array($queried_entities), $this->hookLoadArguments);
    foreach (module_implements($this->entityInfo['load hook']) as $module) {
      call_user_func_array($module . '_' . $this->entityInfo['load hook'], $args);
    }
  }

  /**
   * Get entities from the static cache.
   *
   * @param $ids
   *   If not empty, return entities that match these IDs.
   * @param $conditions
   *   If set, return entities that match all of these conditions.
   */
  protected function cacheGet($ids, $conditions = array()) {
    $entities = array();
    // Load any available entities from the internal cache.
    if (!empty($this->entityCache)) {
      if ($ids) {
        $entities += array_intersect_key($this->entityCache, array_flip($ids));
      }
      // If loading entities only by conditions, fetch all available entities
      // from the cache. Entities which don't match are removed later.
      elseif ($conditions) {
        $entities = $this->entityCache;
      }
    }

    // Exclude any entities loaded from cache if they don't match $conditions.
    // This ensures the same behavior whether loading from memory or database.
    if ($conditions) {
      foreach ($entities as $entity) {
        $entity_values = (array) $entity;
        if (array_diff_assoc($conditions, $entity_values)) {
          unset($entities[$entity->{$this->idKey}]);
        }
      }
    }
    return $entities;
  }

  /**
   * Store entities in the static entity cache.
   */
  protected function cacheSet($entities) {
    $this->entityCache += $entities;
  }
}
