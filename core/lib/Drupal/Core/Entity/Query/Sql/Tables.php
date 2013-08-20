<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Query\Sql\Tables.
 */

namespace Drupal\Core\Entity\Query\Sql;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityReference;
use Drupal\Core\Entity\Query\QueryException;
use Drupal\field\Entity\Field;

/**
 * Adds tables and fields to the SQL entity query.
 */
class Tables implements TablesInterface {

  /**
   * @var \Drupal\Core\Database\Query\SelectInterface
   */
  protected $sqlQuery;

  /**
   * Entity table array, key is table name, value is alias.
   *
   * This array contains at most two entries: one for the data, one for the
   * properties.
   *
   * @var array
   */
  protected $entityTables = array();


  /**
   * Field table array, key is table name, value is alias.
   *
   * This array contains one entry per field table.
   *
   * @var array
   */
  protected $fieldTables = array();

  /**
   * @param \Drupal\Core\Database\Query\SelectInterface $sql_query
   */
  public function __construct(SelectInterface $sql_query) {
    $this->sqlQuery = $sql_query;
  }

  /**
   * {@inheritdoc}
   */
  public function addField($field, $type, $langcode) {
    $entity_type = $this->sqlQuery->getMetaData('entity_type');
    $age = $this->sqlQuery->getMetaData('age');
    // This variable ensures grouping works correctly. For example:
    // ->condition('tags', 2, '>')
    // ->condition('tags', 20, '<')
    // ->condition('node_reference.nid.entity.tags', 2)
    // The first two should use the same table but the last one needs to be a
    // new table. So for the first two, the table array index will be 'tags'
    // while the third will be 'node_reference.nid.tags'.
    $index_prefix = '';
    $specifiers = explode('.', $field);
    $base_table = 'base_table';
    $count = count($specifiers) - 1;
    // This will contain the definitions of the last specifier seen by the
    // system.
    $propertyDefinitions = array();
    $entity_info = entity_get_info($entity_type);
    // Use the lightweight and fast field map for checking whether a specifier
    // is a field or not. While calling field_info_field() on every specifier
    // delivers the same information, if no specifiers are using the field API
    // it is much faster if field_info_field() is never called.
    $field_map = field_info_field_map();
    for ($key = 0; $key <= $count; $key ++) {
      // If there is revision support and only the current revision is being
      // queried then use the revision id. Otherwise, the entity id will do.
      if (!empty($entity_info['entity_keys']['revision']) && $age == FIELD_LOAD_CURRENT) {
        // This contains the relevant SQL field to be used when joining entity
        // tables.
        $entity_id_field = $entity_info['entity_keys']['revision'];
        // This contains the relevant SQL field to be used when joining field
        // tables.
        $field_id_field = 'revision_id';
      }
      else {
        $entity_id_field = $entity_info['entity_keys']['id'];
        $field_id_field = 'entity_id';
      }
      // This can either be the name of an entity property (non-configurable
      // field), a field API field (a configurable field).
      $specifier = $specifiers[$key];
      // First, check for field API fields by trying to retrieve the field specified.
      // Normally it is a field name, but field_purge_batch() is passing in
      // id:$field_id so check that first.
      if (substr($specifier, 0, 3) == 'id:') {
        $field = field_info_field_by_id(substr($specifier, 3));
      }
      elseif (isset($field_map[$specifier])) {
        $field = field_info_field($specifier);
      }
      else {
        $field = FALSE;
      }
      // If we managed to retrieve the field, process it.
      if ($field) {
        // Find the field column.
        $column = FALSE;
        if ($key < $count) {
          $next = $specifiers[$key + 1];
          // Is this a field column?
          if (isset($field['columns'][$next]) || in_array($next, Field::getReservedColumns())) {
            // Use it.
            $column = $next;
            // Do not process it again.
            $key++;
          }
          // If there are more specifiers, the next one must be a
          // relationship. Either the field name followed by a relationship
          // specifier, for example $node->field_image->entity. Or a field
          // column followed by a relationship specifier, for example
          // $node->field_image->fid->entity. In both cases, prepare the
          // property definitions for the relationship. In the first case,
          // also use the property definitions for column.
          if ($key < $count) {
            $relationship_specifier = $specifiers[$key + 1];

            // Get the field definitions form a mocked entity.
            $entity = entity_create($entity_type, array());
            $propertyDefinitions = $entity->{$field['field_name']}->getPropertyDefinitions();

            // If the column is not yet known, ie. the
            // $node->field_image->entity case then use first property as
            // column, i.e. target_id or fid.
            // Otherwise, the code executing the relationship will throw an
            // exception anyways so no need to do it here.
            if (!$column && isset($propertyDefinitions[$relationship_specifier]) && $entity->{$field['field_name']}->get('entity') instanceof EntityReference) {
              $column = current(array_keys($propertyDefinitions));
            }
            // Prepare the next index prefix.
            $next_index_prefix = "$relationship_specifier.$column";
          }
        }
        else {
          // If this is the last specifier, default to value.
          $column = 'value';
        }
        $table = $this->ensureFieldTable($index_prefix, $field, $type, $langcode, $base_table, $entity_id_field, $field_id_field);
        $sql_column = _field_sql_storage_columnname($field['field_name'], $column);
      }
      // This is an entity property (non-configurable field).
      else {
        // ensureEntityTable() decides whether an entity property will be
        // queried from the data table or the base table based on where it
        // finds the property first. The data table is prefered, which is why
        // it gets added before the base table.
        $entity_tables = array();
        if (isset($entity_info['data_table'])) {
          $this->sqlQuery->addMetaData('simple_query', FALSE);
          $entity_tables[$entity_info['data_table']] = drupal_get_schema($entity_info['data_table']);
        }
        $entity_tables[$entity_info['base_table']] = drupal_get_schema($entity_info['base_table']);
        $sql_column = $specifier;
        $table = $this->ensureEntityTable($index_prefix, $specifier, $type, $langcode, $base_table, $entity_id_field, $entity_tables);
      }
      // If there are more specifiers to come, it's a relationship.
      if ($key < $count) {
        // Computed fields have prepared their property definition already, do
        // it for properties as well.
        if (!$propertyDefinitions) {
          // Create a relevant entity to find the definition for this
          // property.
          $values = array();
          // If there are bundles, pick one. It does not matter which,
          // properties exist on all bundles.
          if (!empty($entity_info['entity keys']['bundle'])) {
            $values[$entity_info['entity keys']['bundle']] = key(entity_get_bundles('node'));
          }
          $entity = entity_create($entity_type, $values);
          $propertyDefinitions = $entity->$specifier->getPropertyDefinitions();
          $relationship_specifier = $specifiers[$key + 1];
          $next_index_prefix = $relationship_specifier;
        }
        // Check for a valid relationship.
        if (isset($propertyDefinitions[$relationship_specifier]) && $entity->{$specifier}->get('entity') instanceof EntityReference) {
          // If it is, use the entity type.
          $entity_type = $propertyDefinitions[$relationship_specifier]['constraints']['EntityType'];
          $entity_info = entity_get_info($entity_type);
          // Add the new entity base table using the table and sql column.
          $join_condition= '%alias.' . $entity_info['entity_keys']['id'] . " = $table.$sql_column";
          $base_table = $this->sqlQuery->leftJoin($entity_info['base_table'], NULL, $join_condition);
          $propertyDefinitions = array();
          $key++;
          $index_prefix .= "$next_index_prefix.";
        }
        else {
          throw new QueryException(format_string('Invalid specifier @next.', array('@next' => $relationship_specifier)));
        }
      }
    }
    return "$table.$sql_column";
  }

  /**
   * Join entity table if necessary and return the alias for it.
   *
   * @param string $property
   * @return string
   * @throws \Drupal\Core\Entity\Query\QueryException
   */
  protected function ensureEntityTable($index_prefix, $property, $type, $langcode, $base_table, $id_field, $entity_tables) {
    foreach ($entity_tables as $table => $schema) {
      if (isset($schema['fields'][$property])) {
        if (!isset($this->entityTables[$index_prefix . $table])) {
          $this->entityTables[$index_prefix . $table] = $this->addJoin($type, $table, "%alias.$id_field = $base_table.$id_field", $langcode);
        }
        return $this->entityTables[$index_prefix . $table];
      }
    }
    throw new QueryException(format_string('@property not found', array('@property' => $property)));
  }

  /**
   * Join field table if necessary.
   *
   * @param $field_name
   *   Name of the field.
   * @return string
   * @throws \Drupal\Core\Entity\Query\QueryException
   */
  protected function ensureFieldTable($index_prefix, &$field, $type, $langcode, $base_table, $entity_id_field, $field_id_field) {
    $field_name = $field['field_name'];
    if (!isset($this->fieldTables[$index_prefix . $field_name])) {
      $table = $this->sqlQuery->getMetaData('age') == FIELD_LOAD_CURRENT ? _field_sql_storage_tablename($field) : _field_sql_storage_revision_tablename($field);
      if ($field['cardinality'] != 1) {
        $this->sqlQuery->addMetaData('simple_query', FALSE);
      }
      $entity_type = $this->sqlQuery->getMetaData('entity_type');
      $this->fieldTables[$index_prefix . $field_name] = $this->addJoin($type, $table, "%alias.$field_id_field = $base_table.$entity_id_field AND %alias.entity_type = '$entity_type'", $langcode);
    }
    return $this->fieldTables[$index_prefix . $field_name];
  }

  protected function addJoin($type, $table, $join_condition, $langcode) {
    $arguments = array();
    if ($langcode) {
      $placeholder = ':langcode' . $this->sqlQuery->nextPlaceholder();
      $join_condition .= ' AND %alias.langcode = ' . $placeholder;
      $arguments[$placeholder] = $langcode;
    }
    return $this->sqlQuery->addJoin($type, $table, NULL, $join_condition, $arguments);
  }

}
