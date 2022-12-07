<?php

namespace Drupal\views\Plugin\views\query;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\PluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * @defgroup views_query_plugins Views query plugins
 * @{
 * Plugins for views queries.
 *
 * Query plugins generate and execute a built query object against a
 * particular storage backend, converting the Views query object into an
 * actual query. Although query plugins need not necessarily use SQL, most
 * other handler plugins that affect the query (fields, filters, etc.)
 * implicitly assume that the query is using SQL.
 *
 * Query plugins extend \Drupal\views\Plugin\views\query\QueryPluginBase.
 * They must be annotated with \Drupal\views\Annotation\ViewsQuery
 * annotation, and they must be in namespace directory Plugin\views\query.
 *
 * @ingroup views_plugins
 * @see plugin_api
 */

/**
 * Base plugin class for Views queries.
 */
abstract class QueryPluginBase extends PluginBase implements CacheableDependencyInterface {

  /**
   * A pager plugin that should be provided by the display.
   *
   * @var \Drupal\views\Plugin\views\pager\PagerPluginBase|null
   */
  public $pager = NULL;

  /**
   * Stores the limit of items that should be requested in the query.
   *
   * @var int
   */
  protected $limit;

  /**
   * The OFFSET on the query.
   */
  public int $offset;

  /**
   * Controls how the WHERE and HAVING groups are put together.
   *
   * @var string
   */
  protected $groupOperator;

  /**
   * Generate a query and a countquery from all of the information supplied
   * to the object.
   *
   * @param $get_count
   *   Provide a countquery if this is true, otherwise provide a normal query.
   */
  public function query($get_count = FALSE) {}

  /**
   * Let modules modify the query just prior to finalizing it.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view which is executed.
   */
  public function alter(ViewExecutable $view) {}

  /**
   * Builds the necessary info to execute the query.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view which is executed.
   */
  public function build(ViewExecutable $view) {}

  /**
   * Executes the query and fills the associated view object with according
   * values.
   *
   * Values to set: $view->result, $view->total_rows, $view->execute_time,
   * $view->pager['current_page'].
   *
   * $view->result should contain an array of objects. The array must use a
   * numeric index starting at 0.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view which is executed.
   */
  public function execute(ViewExecutable $view) {}

  /**
   * Add a signature to the query, if such a thing is feasible.
   *
   * This signature is something that can be used when perusing query logs to
   * discern where particular queries might be coming from.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view which is executed.
   */
  public function addSignature(ViewExecutable $view) {}

  /**
   * Get aggregation info for group by queries.
   *
   * If NULL, aggregation is not allowed.
   */
  public function getAggregationInfo() {}

  public function validateOptionsForm(&$form, FormStateInterface $form_state) {}

  public function submitOptionsForm(&$form, FormStateInterface $form_state) {}

  public function summaryTitle() {
    return $this->t('Settings');
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = [];

    foreach ($this->getEntityTableInfo() as $info) {
      if (!empty($info['provider'])) {
        $dependencies['module'][] = $info['provider'];
      }
    }

    return $dependencies;
  }

  /**
   * Set a LIMIT on the query, specifying a maximum number of results.
   */
  public function setLimit($limit) {
    $this->limit = $limit;
  }

  /**
   * Set an OFFSET on the query, specifying a number of results to skip.
   */
  public function setOffset($offset) {
    $this->offset = $offset;
  }

  /**
   * Returns the limit of the query.
   */
  public function getLimit() {
    return $this->limit;
  }

  /**
   * Create a new grouping for the WHERE or HAVING clause.
   *
   * @param $type
   *   Either 'AND' or 'OR'. All items within this group will be added
   *   to the WHERE clause with this logical operator.
   * @param $group
   *   An ID to use for this group. If unspecified, an ID will be generated.
   * @param $where
   *   'where' or 'having'.
   *
   * @return int|string
   *   The group ID generated.
   */
  public function setWhereGroup($type = 'AND', $group = NULL, $where = 'where') {
    // Set an alias.
    $groups = &$this->$where;

    if (!isset($group)) {
      $group = empty($groups) ? 1 : max(array_keys($groups)) + 1;
    }

    // Create an empty group
    if (empty($groups[$group])) {
      $groups[$group] = ['conditions' => [], 'args' => []];
    }

    $groups[$group]['type'] = strtoupper($type);
    return $group;
  }

  /**
   * Control how all WHERE and HAVING groups are put together.
   *
   * @param $type
   *   Either 'AND' or 'OR'
   */
  public function setGroupOperator($type = 'AND') {
    $this->groupOperator = strtoupper($type);
  }

  /**
   * Loads all entities contained in the passed-in $results.
   *
   * If the entity belongs to the base table, then it gets stored in
   * $result->_entity. Otherwise, it gets stored in
   * $result->_relationship_entities[$relationship_id];
   *
   * Query plugins that don't support entities can leave the method empty.
   */
  public function loadEntities(&$results) {}

  /**
   * Returns a Unix timestamp to database native timestamp expression.
   *
   * @param string $field
   *   The query field that will be used in the expression.
   * @param bool $string_date
   *   For certain databases, date format functions vary depending on string or
   *   numeric storage.
   * @param bool $calculate_offset
   *   If set to TRUE, the timezone offset will be included in the returned
   *   field.
   *
   * @return string
   *   An expression representing a timestamp with time zone.
   */
  public function getDateField($field, $string_date = FALSE, $calculate_offset = TRUE) {
    return $field;
  }

  /**
   * Set the database to the current user timezone.
   *
   * @return string
   *   The current timezone as returned by date_default_timezone_get().
   */
  public function setupTimezone() {
    return date_default_timezone_get();
  }

  /**
   * Creates cross-database date formatting.
   *
   * @param string $field
   *   An appropriate query expression pointing to the date field.
   * @param string $format
   *   A format string for the result, like 'Y-m-d H:i:s'.
   * @param bool $string_date
   *   For certain databases, date format functions vary depending on string or
   *   numeric storage.
   *
   * @return string
   *   A string representing the field formatted as a date in the format
   *   specified by $format.
   */
  public function getDateFormat($field, $format, $string_date = FALSE) {
    return $field;
  }

  /**
   * Returns an array of all tables from the query that map to an entity type.
   *
   * Includes the base table and all relationships, if eligible.
   *
   * Available keys for each table:
   * - base: The actual base table (i.e. "user" for an author relationship).
   * - relationship_id: The id of the relationship, or "none".
   * - alias: The alias used for the relationship.
   * - entity_type: The entity type matching the base table.
   * - revision: A boolean that specifies whether the table is a base table or
   *   a revision table of the entity type.
   *
   * @return array
   *   An array of table information, keyed by table alias.
   */
  public function getEntityTableInfo() {
    // Start with the base table.
    $entity_tables = [];
    $views_data = Views::viewsData();
    $base_table = $this->view->storage->get('base_table');
    $base_table_data = $views_data->get($base_table);

    if (isset($base_table_data['table']['entity type'])) {
      $entity_tables[$base_table_data['table']['entity type']] = [
        'base' => $base_table,
        'alias' => $base_table,
        'relationship_id' => 'none',
        'entity_type' => $base_table_data['table']['entity type'],
        'revision' => $base_table_data['table']['entity revision'],
      ];

      // Include the entity provider.
      if (!empty($base_table_data['table']['provider'])) {
        $entity_tables[$base_table_data['table']['entity type']]['provider'] = $base_table_data['table']['provider'];
      }
    }

    // Include all relationships.
    foreach ((array) $this->view->relationship as $relationship_id => $relationship) {
      $table_data = $views_data->get($relationship->definition['base']);
      if (isset($table_data['table']['entity type'])) {

        // If this is not one of the entity base tables, skip it.
        $entity_type = \Drupal::entityTypeManager()->getDefinition($table_data['table']['entity type']);
        $entity_base_tables = [$entity_type->getBaseTable(), $entity_type->getDataTable(), $entity_type->getRevisionTable(), $entity_type->getRevisionDataTable()];
        if (!in_array($relationship->definition['base'], $entity_base_tables)) {
          continue;
        }

        $entity_tables[$relationship_id . '__' . $relationship->tableAlias] = [
          'base' => $relationship->definition['base'],
          'relationship_id' => $relationship_id,
          'alias' => $relationship->alias,
          'entity_type' => $table_data['table']['entity type'],
          'revision' => $table_data['table']['entity revision'],
        ];

        // Include the entity provider.
        if (!empty($table_data['table']['provider'])) {
          $entity_tables[$relationship_id . '__' . $relationship->tableAlias]['provider'] = $table_data['table']['provider'];
        }
      }
    }

    // Determine which of the tables are revision tables.
    foreach ($entity_tables as $table_alias => $table) {
      $entity_type = \Drupal::entityTypeManager()->getDefinition($table['entity_type']);
      if ($entity_type->getRevisionTable() == $table['base']) {
        $entity_tables[$table_alias]['revision'] = TRUE;
      }
    }

    return $entity_tables;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = [];
    if (($views_data = Views::viewsData()->get($this->view->storage->get('base_table'))) && !empty($views_data['table']['entity type'])) {
      $entity_type_id = $views_data['table']['entity type'];
      $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
      $contexts = $entity_type->getListCacheContexts();
    }
    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

  /**
   * Applies a timezone offset to the given field.
   *
   * @param string &$field
   *   The date field, in string format.
   * @param int $offset
   *   The timezone offset to apply to the field.
   */
  public function setFieldTimezoneOffset(&$field, $offset) {
    // No-op. Timezone offsets are implementation-specific and should implement
    // this method as needed.
  }

  /**
   * Get the timezone offset in seconds.
   *
   * @return int
   *   The offset, in seconds, for the timezone being used.
   */
  public function getTimezoneOffset() {
    $timezone = $this->setupTimezone();
    $offset = 0;
    if ($timezone) {
      $dtz = new \DateTimeZone($timezone);
      $dt = new \DateTime('now', $dtz);
      $offset = $dtz->getOffset($dt);
    }
    return $offset;
  }

}

/**
 * @}
 */
