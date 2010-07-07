<?php
// $Id: entity.inc,v 1.11 2010/07/07 13:13:44 dries Exp $

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
    $this->idKey = $this->entityInfo['entity keys']['id'];

    // Check if the entity type supports revisions.
    if (!empty($this->entityInfo['entity keys']['revision'])) {
      $this->revisionKey = $this->entityInfo['entity keys']['revision'];
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

/**
 * Exception thrown by EntityFieldQuery() on unsupported query syntax.
 *
 * Some storage modules might not support the full range of the syntax for
 * conditions, and will raise an EntityFieldQueryException when an unsupported
 * condition was specified.
 */
class EntityFieldQueryException extends Exception {}

/**
 * Retrieves entities matching a given set of conditions.
 *
 * This class allows finding entities based on entity properties (for example,
 * node->changed), field values, and generic entity meta data (bundle,
 * entity type, entity id, and revision ID). It is not possible to query across
 * multiple entity types. For example, there is no facility to find published
 * nodes written by users created in the last hour, as this would require
 * querying both node->status and user->created.
 *
 * Normally we would not want to have public properties on the object, as that
 * allows the object's state to become inconsistent too easily. However, this
 * class's standard use case involves primarily code that does need to have
 * direct access to the collected properties in order to handle alternate
 * execution routines. We therefore use public properties for simplicity. Note
 * that code that is simply creating and running a field query should still use
 * the appropriate methods add conditions on the query.
 *
 * Storage engines are not required to support every type of query. By default,
 * an EntityFieldQueryException will be raised if an unsupported condition is
 * specified or if the query has field conditions or sorts that are stored in
 * different field storage engines. However, this logic can be overridden in
 * hook_entity_query().
 */
class EntityFieldQuery {
  /**
   * Indicates that both deleted and non-deleted fields should be returned.
   *
   * @see EntityFieldQuery::deleted()
   */
  const RETURN_ALL = NULL;

  /**
   * Associative array of entity-generic metadata conditions.
   *
   * @var array
   *
   * @see EntityFieldQuery::entityCondition()
   */
  public $entityConditions = array();

  /**
   * List of field conditions.
   *
   * @var array
   *
   * @see EntityFieldQuery::fieldCondition()
   */
  public $fieldConditions = array();

  /**
   * List of property conditions.
   *
   * @var array
   *
   * @see EntityFieldQuery::propertyCondition()
   */
  public $propertyConditions = array();

  /**
   * List of order clauses for entity-generic metadata.
   *
   * @var array
   *
   * @see EntityFieldQuery::entityOrderBy()
   */
  public $entityOrder = array();

  /**
   * List of order clauses for fields.
   *
   * @var array
   *
   * @see EntityFieldQuery::fieldOrderBy()
   */
  public $fieldOrder = array();

  /**
   * List of order clauses for entities.
   *
   * @var array
   *
   * @see EntityFieldQuery::entityOrderBy()
   */
  public $propertyOrder = array();

  /**
   * The query range.
   *
   * @var array
   *
   * @see EntityFieldQuery::range()
   */
  public $range = array();

  /**
   * Query behavior for deleted data.
   *
   * TRUE to return only deleted data, FALSE to return only non-deleted data,
   * EntityFieldQuery::RETURN_ALL to return everything.
   *
   * @see EntityFieldQuery::deleted()
   */
  public $deleted = FALSE;

  /**
   * A list of field arrays used.
   *
   * Field names passed to EntityFieldQuery::fieldCondition() and
   * EntityFieldQuery::fieldOrderBy() are run through field_info_field() before
   * stored in this array. This way, the elements of this array are field
   * arrays.
   *
   * @var array
   */
  public $fields = array();

  /**
   * TRUE if this is a count query, FALSE if it isn't.
   *
   * @var boolean
   */
  public $count = FALSE;

  /**
   * Flag indicating whether this is querying current or all revisions.
   *
   * @var int
   *
   * @see EntityFieldQuery::age()
   */
  public $age = FIELD_LOAD_CURRENT;

  /**
   * The ordered results.
   *
   * @var array
   *
   * @see EntityFieldQuery::execute().
   */
  public $orderedResults = array();

  /**
   * The method executing the query, if it is overriding the default.
   *
   * @var string
   *
   * @see EntityFieldQuery::execute().
   */
  public $executeCallback = '';

  /**
   * Adds a condition on entity-generic metadata.
   *
   * If the overall query contains only entity conditions or ordering, or if
   * there are property conditions, then specifying the entity type is
   * mandatory. If there are field conditions or ordering but no property
   * conditions or ordering, then specifying an entity type is optional. While
   * the field storage engine might support field conditions on more than one
   * entity type, there is no way to query across multiple entity base tables by
   * default. To specify the entity type, pass in 'entity_type' for $name,
   * the type as a string for $value, and no $operator (it's disregarded).
   *
   * 'bundle', 'revision_id' and 'entity_id' have no such restrictions.
   *
   * @param $name
   *   'entity_type', 'bundle', 'revision_id' or 'entity_id'.
   * @param $value
   *   The value for $name. In most cases, this is a scalar. For more complex
   *   options, it is an array. The meaning of each element in the array is
   *   dependent on $operator.
   * @param $operator
   *   Possible values:
   *   - '=', '!=', '>', '>=', '<', '<=', 'STARTS_WITH', 'CONTAINS': These
   *     operators expect $value to be a literal of the same type as the
   *     column.
   *   - 'IN', 'NOT IN': These operators expect $value to be an array of
   *     literals of the same type as the column.
   *   - 'BETWEEN': This operator expects $value to be an array of two literals
   *     of the same type as the column.
   *
   * @return EntityFieldQuery
   *   The called object.
   */
  public function entityCondition($name, $value, $operator = NULL) {
    $this->entityConditions[$name] = array(
      'value' => $value,
      'operator' => $operator,
    );
    return $this;
  }

  /**
   * Adds a condition on field values.
   *
   * @param $field
   *   Either a field name or a field array.
   * @param $column
   *   A column defined in the hook_field_schema() of this field. If this is
   *   omitted then the query will find only entities that have data in this
   *   field, using the entity and property conditions if there are any.
   * @param $value
   *   The value to test the column value against. In most cases, this is a
   *   scalar. For more complex options, it is an array. The meaning of each
   *   element in the array is dependent on $operator.
   * @param $operator
   *   Possible values:
   *   - '=', '!=', '>', '>=', '<', '<=', 'STARTS_WITH', 'CONTAINS': These
   *     operators expect $value to be a literal of the same type as the
   *     column.
   *   - 'IN', 'NOT IN': These operators expect $value to be an array of
   *     literals of the same type as the column.
   *   - 'BETWEEN': This operator expects $value to be an array of two literals
   *     of the same type as the column.
   * @param $delta_group
   *   An arbitrary identifier: conditions in the same group must have the same
   *   $delta_group. For example, let's presume a multivalue field which has
   *   two columns, 'color' and 'shape', and for entity id 1, there are two
   *   values: red/square and blue/circle. Entity ID 1 does not have values
   *   corresponding to 'red circle', however if you pass 'red' and 'circle' as
   *   conditions, it will appear in the  results - by default queries will run
   *   against any combination of deltas. By passing the conditions with the
   *   same $delta_group it will ensure that only values attached to the same
   *   delta are matched, and entity 1 would then be excluded from the results.
   * @param $language_group
   *   An arbitrary identifier: conditions in the same group must have the same
   *   $language_group.
   *
   * @return EntityFieldQuery
   *   The called object.
   */
  public function fieldCondition($field, $column = NULL, $value = NULL, $operator = NULL, $delta_group = NULL, $language_group = NULL) {
    if (is_scalar($field)) {
      $field = field_info_field($field);
    }
    // Ensure the same index is used for fieldConditions as for fields.
    $index = count($this->fields);
    $this->fields[$index] = $field;
    if (isset($column)) {
      $this->fieldConditions[$index] = array(
        'field' => $field,
        'column' => $column,
        'value' => $value,
        'operator' => $operator,
        'delta_group' => $delta_group,
        'language_group' => $language_group,
      );
    }
    return $this;
  }

  /**
   * Adds a condition on an entity-specific property.
   *
   * An $entity_type must be specified by calling
   * EntityFieldCondition::entityCondition('entity_type', $entity_type) before
   * executing the query. Also, by default only entities stored in SQL are
   * supported; however, EntityFieldQuery::executeCallback can be set to handle
   * different entity storage.
   *
   * @param $column
   *   A column defined in the hook_schema() of the base table of the entity.
   * @param $value
   *   The value to test the field against. In most cases, this is a scalar. For
   *   more complex options, it is an array. The meaning of each element in the
   *   array is dependent on $operator.
   * @param $operator
   *   Possible values:
   *   - '=', '!=', '>', '>=', '<', '<=', 'STARTS_WITH', 'CONTAINS': These
   *     operators expect $value to be a literal of the same type as the
   *     column.
   *   - 'IN', 'NOT IN': These operators expect $value to be an array of
   *     literals of the same type as the column.
   *   - 'BETWEEN': This operator expects $value to be an array of two literals
   *     of the same type as the column.
   *   The operator can be omitted, and will default to 'IN' if the value is an
   *   array, or to '=' otherwise.
   *
   * @return EntityFieldQuery
   *   The called object.
   */
  public function propertyCondition($column, $value, $operator = NULL) {
    $this->propertyConditions[] = array(
      'column' => $column,
      'value' => $value,
      'operator' => $operator,
    );
    return $this;
  }

  /**
   * Orders the result set by entity-generic metadata.
   *
   * If called multiple times, the query will order by each specified column in
   * the order this method is called.
   *
   * @param $name
   *   'entity_type', 'bundle', 'revision_id' or 'entity_id'.
   * @param $direction
   *   The direction to sort. Legal values are "ASC" and "DESC".
   *
   * @return EntityFieldQuery
   *   The called object.
   */
  public function entityOrderBy($name, $direction) {
    $this->entityOrder[$name] = $direction;
    return $this;
  }

  /**
   * Orders the result set by a given field column.
   *
   * If called multiple times, the query will order by each specified column in
   * the order this method is called.
   *
   * @param $field
   *   Either a field name or a field array.
   * @param $column
   *   A column defined in the hook_field_schema() of this field. entity_id and
   *   bundle can also be used.
   * @param $direction
   *   The direction to sort. Legal values are "ASC" and "DESC".
   *
   * @return EntityFieldQuery
   *   The called object.
   */
  public function fieldOrderBy($field, $column, $direction) {
    if (is_scalar($field)) {
      $field = field_info_field($field);
    }
    // Ensure the same index is used for fieldOrder as for fields.
    $index = count($this->fields);
    $this->fields[$index] = $field;
    $this->fieldOrder[$index] = array(
      'field' => $field,
      'column' => $column,
      'direction' => $direction,
    );
    return $this;
  }

  /**
   * Orders the result set by an entity-specific property.
   *
   * An $entity_type must be specified by calling
   * EntityFieldCondition::entityCondition('entity_type', $entity_type) before
   * executing the query.
   *
   * If called multiple times, the query will order by each specified column in
   * the order this method is called.
   *
   * @param $column
   *   The column on which to order.
   * @param $direction
   *   The direction to sort. Legal values are "ASC" and "DESC".
   *
   * @return EntityFieldQuery
   *   The called object.
   */
  public function propertyOrderBy($column, $direction) {
    $this->propertyOrder[] = array(
      'column' => $column,
      'direction' => $direction,
    );
    return $this;
  }

  /**
   * Sets the query to be a count query only.
   *
   * @return EntityFieldQuery
   *   The called object.
   */
  public function count() {
    $this->count = TRUE;
    return $this;
  }

  /**
   * Restricts a query to a given range in the result set.
   *
   * @param $start
   *   The first entity from the result set to return. If NULL, removes any
   *   range directives that are set.
   * @param $length
   *   The number of entities to return from the result set.
   *
   * @return EntityFieldQuery
   *   The called object.
   */
  public function range($start = NULL, $length = NULL) {
    $this->range = array(
      'start' => $start,
      'length' => $length,
    );
    return $this;
  }

  /**
   * Filters on the data being deleted.
   *
   * @param $deleted
   *   TRUE to only return deleted data, FALSE to return non-deleted data,
   *   EntityFieldQuery::RETURN_ALL to return everything. Defaults to FALSE.
   *
   * @return EntityFieldQuery
   *   The called object.
   */
  public function deleted($deleted = TRUE) {
    $this->deleted = $deleted;
    return $this;
  }

  /**
   * Queries the current or every revision.
   *
   * Note that this only affects field conditions. Property conditions always
   * apply to the current revision.
   * @TODO: Once revision tables have been cleaned up, revisit this.
   *
   * @param $age
   *   - FIELD_LOAD_CURRENT (default): Query the most recent revisions for all
   *     entities. The results will be keyed by entity type and entity ID.
   *   - FIELD_LOAD_REVISION: Query all revisions. The results will be keyed by
   *     entity type and entity revision ID.
   *
   * @return EntityFieldQuery
   *   The called object.
   */
  public function age($age) {
    $this->age = $age;
    return $this;
  }

  /**
   * Executes the query.
   *
   * After executing the query, $this->orderedResults will contain a list of
   * the same stub entities in the order returned by the query. This is only
   * relevant if there are multiple entity types in the returned value and
   * a field ordering was requested. In every other case, the returned value
   * contains everything necessary for processing.
   *
   * @return
   *   Either a number if count() was called or an array of associative
   *   arrays of stub entities. The outer array keys are entity types, and the
   *   inner array keys are the relevant ID. (In most this cases this will be
   *   the entity ID. The only exception is when age=FIELD_LOAD_REVISION is used
   *   and field conditions or sorts are present -- in this case, the key will
   *   be the revision ID.) The inner array values are always stub entities, as
   *   returned by entity_create_stub_entity(). To traverse the returned array:
   *   @code
   *     foreach ($query->execute() as $entity_type => $entities) {
   *       foreach ($entities as $entity_id => $entity) {
   *   @endcode
   *   Note if the entity type is known, then the following snippet will load
   *   the entities found:
   *   @code
   *     $result = $query->execute();
   *     $entities = entity_load($my_type, array_keys($result[$my_type]));
   *   @endcode
   */
  public function execute() {
    // Give a chance to other modules to alter the query.
    drupal_alter('entity_query', $this);

    // Execute the query using the correct callback.
    $result = call_user_func($this->queryCallback(), $this);

    // Sanity checks.
    if (!empty($this->propertyConditions)) {
      throw new EntityFieldQueryException(t('Property query conditions were not handled in !function.', array('!function' => $function)));
    }
    if (!empty($this->propertyOrderBy)) {
      throw new EntityFieldQueryException(t('Property query order by was not handled in !function.', array('!function' => $function)));
    }
    return $result;
  }

  /**
   * Determines the query callback to use for this entity query.
   *
   * @return
   *   A callback that can be used with call_user_func().
   */
  public function queryCallback() {
    // Use the override from $this->executeCallback. It can be set either
    // while building the query, or using hook_entity_query_alter().
    if (function_exists($this->executeCallback)) {
      return $this->executeCallback;
    }
    // If there are no field conditions and sorts, and no execute callback
    // then we default to querying entity tables in SQL.
    if (empty($this->fields)) {
      return array($this, 'propertyQuery');
    }
    // If no override, find the storage engine to be used.
    foreach ($this->fields as $field) {
      if (!isset($storage)) {
        $storage = $field['storage']['module'];
      }
      elseif ($storage != $field['storage']['module']) {
        throw new EntityFieldQueryException(t("Can't handle more than one field storage engine"));
      }
    }
    if ($storage) {
      // Use hook_field_storage_query() from the field storage.
      return $storage . '_field_storage_query';
    }
    else {
      throw new EntityFieldQueryException(t("Field storage engine not found."));
    }
  }

  /**
   * Queries entity tables in SQL for property conditions and sorts.
   *
   * This method is only used if there are no field conditions and sorts.
   *
   * @return
   *   See EntityFieldQuery::execute().
   */
  protected function propertyQuery() {
    if (empty($this->entityConditions['entity_type'])) {
      throw new EntityFieldQueryException(t('For this query an entity type must be specified.'));
    }
    $entity_type = $this->entityConditions['entity_type']['value'];
    unset($this->entityConditions['entity_type']);
    $entity_info = entity_get_info($entity_type);
    if (empty($entity_info['base table'])) {
      throw new EntityFieldQueryException(t('Entity %entity has no base table.', array('%entity' => $entity_type)));
    }
    $base_table = $entity_info['base table'];
    $select_query = db_select($base_table);
    $select_query->addExpression(':entity_type', 'entity_type', array(':entity_type' => $entity_type));
    // Process the four possible entity condition.
    // The id field is always present in entity keys.
    $sql_field = $entity_info['entity keys']['id'];
    $id_map['entity_id'] = $sql_field;
    $select_query->addField($base_table, $sql_field, 'entity_id');
    if (isset($this->entityConditions['entity_id'])) {
      $this->addCondition($select_query, $sql_field, $this->entityConditions['entity_id']);
    }

    // If there is a revision key defined, use it.
    if (!empty($entity_info['entity keys']['revision'])) {
      $sql_field = $entity_info['entity keys']['revision'];
      $select_query->addField($base_table, $sql_field, 'revision_id');
      if (isset($this->entityConditions['revision_id'])) {
        $this->addCondition($select_query, $sql_field, $this->entityConditions['revision_id']);
      }
    }
    else {
      $sql_field = 'revision_id';
      $select_query->addExpression('NULL', 'revision_id');
    }
    $id_map['revision_id'] = $sql_field;

    // Handle bundles.
    if (!empty($entity_info['entity keys']['bundle'])) {
      $sql_field = $entity_info['entity keys']['bundle'];
      $select_query->addField($base_table, $sql_field, 'bundle');
      $having = FALSE;
    }
    else {
      $sql_field = 'bundle';
      $select_query->addExpression(':bundle', 'bundle', array(':bundle' => $entity_type));
      $having = TRUE;
    }
    $id_map['bundle'] = $sql_field;
    if (isset($this->entityConditions['bundle'])) {
      $this->addCondition($select_query, $sql_field, $this->entityConditions['bundle'], $having);
    }

    foreach ($this->entityOrder as $key => $direction) {
      if (isset($id_map[$key])) {
        $select_query->orderBy($id_map[$key], $direction);
      }
      else {
        throw new EntityFieldQueryException(t('Do not know how to order on @key for @entity_type', array('@key' => $key, '@entity_type' => $entity_type)));
      }
    }
    $this->processProperty($select_query, $base_table);
    return $this->finishQuery($select_query);
  }

  /**
   * Finishes the query.
   *
   * Adds the range and returns the requested list.
   *
   * @param SelectQuery $select_query
   *   A SelectQuery which has entity_type, entity_id, revision_id and bundle
   *   fields added.
   * @param $id_key
   *   Which field's values to use as the returned array keys.
   *
   * @return
   *   See EntityFieldQuery::execute().
   */
  function finishQuery($select_query, $id_key = 'entity_id') {
    if ($this->range) {
      $select_query->range($this->range['start'], $this->range['length']);
    }
    if ($this->count) {
      return $select_query->countQuery()->execute()->fetchField();
    }
    $return = array();
    foreach ($select_query->execute() as $partial_entity) {
      $entity = entity_create_stub_entity($partial_entity->entity_type, array($partial_entity->entity_id, $partial_entity->revision_id, $partial_entity->bundle));
      $return[$partial_entity->entity_type][$partial_entity->$id_key] = $entity;
      $this->ordered_results[] = $partial_entity;
    }
    return $return;
  }

  /**
   * Processes the property condition and orders.
   *
   * This is a helper for hook_entity_query() and hook_field_storage_query().
   *
   * @param SelectQuery $select_query
   *   A SelectQuery object.
   * @param $entity_base_table
   *   The name of the entity base table. This already should be in
   *   $select_query.
   */
  public function processProperty(SelectQuery $select_query, $entity_base_table) {
    foreach ($this->propertyConditions as $entity_condition) {
      $this->addCondition($select_query, "$entity_base_table." . $entity_condition['column'], $entity_condition);
    }
    foreach ($this->propertyOrder as $order) {
      $select_query->orderBy("$entity_base_table." . $order['column'], $order['direction']);
    }
    unset($this->propertyConditions, $this->propertyOrder);
  }

  /**
   * Adds a condition to an already built SelectQuery (internal function).
   *
   * This is a helper for hook_entity_query() and hook_field_storage_query().
   *
   * @param SelectQuery $select_query
   *   A SelectQuery object.
   * @param $sql_field
   *   The name of the field.
   * @param $condition
   *   A condition as described in EntityFieldQuery::fieldCondition() and
   *   EntityFieldQuery::entityCondition().
   * @param $having
   *   HAVING or WHERE. This is necessary because SQL can't handle WHERE
   *   conditions on aliased columns.
   */
  public function addCondition(SelectQuery $select_query, $sql_field, $condition, $having = FALSE) {
    $method = $having ? 'havingCondition' : 'condition';
    $like_prefix = '';
    switch ($condition['operator']) {
      case 'CONTAINS':
        $like_prefix = '%';
      case 'STARTS_WITH':
        $select_query->$method($sql_field, $like_prefix . db_like($condition['value']) . '%', 'LIKE');
        break;
      default:
        $select_query->$method($sql_field, $condition['value'], $condition['operator']);
    }
  }

}
