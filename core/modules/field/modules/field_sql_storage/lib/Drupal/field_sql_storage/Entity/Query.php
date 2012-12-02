<?php

/**
 * @file
 * Definition of Drupal\field_sql_storage\Entity\Query.
 */

namespace Drupal\field_sql_storage\Entity;

use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryException;

/**
 * The SQL storage entity query class.
 */
class Query extends QueryBase {

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * @param string $entity_type
   *   The entity type.
   * @param string $conjunction
   *   - AND: all of the conditions on the query need to match.
   *   - OR: at least one of the conditions on the query need to match.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to run the query against.
   */
  public function __construct($entity_type, $conjunction, $connection) {
    parent::__construct($entity_type, $conjunction);
    $this->connection = $connection;
  }

  /**
   * Implements Drupal\Core\Entity\Query\QueryInterface::conditionGroupFactory().
   */
  public function conditionGroupFactory($conjunction = 'AND') {
    return new Condition($conjunction);
  }

  /**
   * Implements Drupal\Core\Entity\Query\QueryInterface::execute().
   */
  public function execute() {
    $entity_type = $this->entityType;
    $entity_info = entity_get_info($entity_type);
    if (!isset($entity_info['base_table'])) {
      throw new QueryException("No base table, invalid query.");
    }
    $base_table = $entity_info['base_table'];
    $simple_query = TRUE;
    if (isset($entity_info['data_table'])) {
      $simple_query = FALSE;
    }
    $sqlQuery = $this->connection->select($base_table, 'base_table', array('conjunction' => $this->conjunction));
    $sqlQuery->addMetaData('entity_type', $entity_type);
    $id_field = $entity_info['entity_keys']['id'];
    $fields[$id_field] = TRUE;
    if (empty($entity_info['entity_keys']['revision'])) {
      // Add the key field for fetchAllKeyed(). When there is no revision
      // support, this is the entity key.
      $sqlQuery->addField('base_table', $entity_info['entity_keys']['id']);
    }
    else {
      // Add the key field for fetchAllKeyed(). When there is revision
      // support, this is the revision key.
      $revision_field = $entity_info['entity_keys']['revision'];
      $fields[$revision_field] = TRUE;
      $sqlQuery->addField('base_table', $revision_field);
    }
    // Now add the value column for fetchAllKeyed(). This is always the
    // entity id.
    $sqlQuery->addField('base_table', $id_field);
    if ($this->accessCheck) {
      $sqlQuery->addTag($entity_type . '_access');
    }
    $sqlQuery->addTag('entity_query');
    $sqlQuery->addTag('entity_query_' . $this->entityType);

    // Add further tags added.
    if (isset($this->alterTags)) {
      foreach ($this->alterTags as $tag => $value) {
        $sqlQuery->addTag($tag);
      }
    }

    // Add further metadata added.
    if (isset($this->alterMetaData)) {
      foreach ($this->alterMetaData as $key => $value) {
        $sqlQuery->addMetaData($key, $value);
      }
    }
    // This now contains first the table containing entity properties and
    // last the entity base table. They might be the same.
    $sqlQuery->addMetaData('age', $this->age);
    $sqlQuery->addMetaData('simple_query', $simple_query);
    $this->condition->compile($sqlQuery);
    if ($this->count) {
      $this->sort = FALSE;
    }
    // Gather the SQL field aliases first to make sure every field table
    // necessary is added. This might change whether the query is simple or
    // not. See below for more on simple queries.
    $sort = array();
    if ($this->sort) {
      $tables = new Tables($sqlQuery);
      foreach ($this->sort as $property => $data) {
        $sort[$property] = isset($fields[$property]) ? $property : $tables->addField($property, 'LEFT', $data['langcode']);
      }
    }
    // If the query is set up for paging either via pager or by range or a
    // count is requested, then the correct amount of rows returned is
    // important. If the entity has a data table or multiple value fields are
    // involved then each revision might appear in several rows and this needs
    // a significantly more complex query.
    $simple_query = (!$this->pager && !$this->range && !$this->count) || $sqlQuery->getMetaData('simple_query');
    if (!$simple_query) {
      // First, GROUP BY revision id (if it has been added) and entity id.
      // Now each group contains a single revision of an entity.
      foreach (array_keys($fields) as $field) {
        $sqlQuery->groupBy($field);
      }
    }
    // Now we know whether this is a simple query or not, actually do the
    // sorting.
    foreach ($sort as $property => $sql_alias) {
      $direction = $this->sort[$property]['direction'];
      if ($simple_query || isset($fields[$property])) {
        // Simple queries, and the grouped columns of complicated queries
        // can be ordered normally, without the aggregation function.
        $sqlQuery->orderBy($sql_alias, $direction);
      }
      else {
        // Order based on the smallest element of each group if the
        // direction is ascending, or on the largest element of each group
        // if the direction is descending.
        $function = $direction == 'ASC' ? 'min' : 'max';
        $expression = "$function($sql_alias)";
        $sqlQuery->addExpression($expression, "order_by_{$property}_$direction");
        $sqlQuery->orderBy($expression, $direction);
      }
    }
    $this->initializePager();
    if ($this->range) {
      $sqlQuery->range($this->range['start'], $this->range['length']);
    }
    if ($this->count) {
      return $sqlQuery->countQuery()->execute()->fetchField();
    }
    // Return a keyed array of results. The key is either the revision_id or
    // the entity_id depending on whether the entity type supports revisions.
    // The value is always the entity id.
    return $sqlQuery->execute()->fetchAllKeyed();
  }

}
