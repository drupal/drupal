<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Query\Sql\Tables.
 */

namespace Drupal\Core\Entity\Query\Sql;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal\Core\Entity\Query\QueryException;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\FieldConfigInterface;

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
    $entity_type_id = $this->sqlQuery->getMetaData('entity_type');
    $entity_manager = \Drupal::entityManager();
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
    $entity_type = $entity_manager->getDefinition($entity_type_id);

    $field_storage_definitions = array();
    // @todo Needed for menu links, make this implementation content entity
    //   specific after https://drupal.org/node/2256521.
    if ($entity_type instanceof ContentEntityTypeInterface) {
      $field_storage_definitions = $entity_manager->getFieldStorageDefinitions($entity_type_id);
    }
    for ($key = 0; $key <= $count; $key ++) {
      // If there is revision support and only the current revision is being
      // queried then use the revision id. Otherwise, the entity id will do.
      if (($revision_key = $entity_type->getKey('revision')) && $age == EntityStorageInterface::FIELD_LOAD_CURRENT) {
        // This contains the relevant SQL field to be used when joining entity
        // tables.
        $entity_id_field = $revision_key;
        // This contains the relevant SQL field to be used when joining field
        // tables.
        $field_id_field = 'revision_id';
      }
      else {
        $entity_id_field = $entity_type->getKey('id');
        $field_id_field = 'entity_id';
      }
      // This can either be the name of an entity base field or a configurable
      // field.
      $specifier = $specifiers[$key];
      // Normally it is a field name, but field_purge_batch() is passing in
      // id:$field_id so check that first.
      /* @var \Drupal\Core\Field\FieldDefinitionInterface $field */
      if (substr($specifier, 0, 3) == 'id:') {
        if ($fields = entity_load_multiple_by_properties('field_config', array('uuid' => substr($specifier, 3), 'include_deleted' => TRUE))) {
          $field = current($fields);
        }
      }
      elseif (isset($field_storage_definitions[$specifier])) {
        $field = $field_storage_definitions[$specifier];
      }
      else {
        $field = FALSE;
      }
      // If we managed to retrieve a configurable field, process it.
      if ($field instanceof FieldConfigInterface) {
        // Find the field column.
        $column = $field->getMainPropertyName();
        if ($key < $count) {
          $next = $specifiers[$key + 1];
          // Is this a field column?
          $columns = $field->getColumns();
          if (isset($columns[$next]) || in_array($next, FieldConfig::getReservedColumns())) {
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
            $propertyDefinitions = $field->getPropertyDefinitions();

            // Prepare the next index prefix.
            $next_index_prefix = "$relationship_specifier.$column";
          }
        }
        $table = $this->ensureFieldTable($index_prefix, $field, $type, $langcode, $base_table, $entity_id_field, $field_id_field);
        $sql_column = ContentEntityDatabaseStorage::_fieldColumnName($field, $column);
      }
      // This is an entity base field (non-configurable field).
      else {
        // ensureEntityTable() decides whether an entity property will be
        // queried from the data table or the base table based on where it
        // finds the property first. The data table is preferred, which is why
        // it gets added before the base table.
        $entity_tables = array();
        if ($data_table = $entity_type->getDataTable()) {
          $this->sqlQuery->addMetaData('simple_query', FALSE);
          $entity_tables[$data_table] = drupal_get_schema($data_table);
        }
        $entity_base_table = $entity_type->getBaseTable();
        $entity_tables[$entity_base_table] = drupal_get_schema($entity_base_table);
        $sql_column = $specifier;
        $table = $this->ensureEntityTable($index_prefix, $specifier, $type, $langcode, $base_table, $entity_id_field, $entity_tables);
      }
      // If there are more specifiers to come, it's a relationship.
      if ($field && $key < $count) {
        // Computed fields have prepared their property definition already, do
        // it for properties as well.
        if (!$propertyDefinitions) {
          $propertyDefinitions = $field->getPropertyDefinitions();
          $relationship_specifier = $specifiers[$key + 1];
          $next_index_prefix = $relationship_specifier;
        }
        // Check for a valid relationship.
        if (isset($propertyDefinitions[$relationship_specifier]) && $field->getPropertyDefinition('entity')->getDataType() == 'entity_reference' ) {
          // If it is, use the entity type.
          $entity_type_id = $propertyDefinitions[$relationship_specifier]->getTargetDefinition()->getEntityTypeId();
          $entity_type = $entity_manager->getDefinition($entity_type_id);
          $field_storage_definitions = $entity_manager->getFieldStorageDefinitions($entity_type_id);
          // Add the new entity base table using the table and sql column.
          $join_condition= '%alias.' . $entity_type->getKey('id') . " = $table.$sql_column";
          $base_table = $this->sqlQuery->leftJoin($entity_type->getBaseTable(), NULL, $join_condition);
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
    $field_name = $field->getName();
    if (!isset($this->fieldTables[$index_prefix . $field_name])) {
      $table = $this->sqlQuery->getMetaData('age') == EntityStorageInterface::FIELD_LOAD_CURRENT ? ContentEntityDatabaseStorage::_fieldTableName($field) : ContentEntityDatabaseStorage::_fieldRevisionTableName($field);
      if ($field->getCardinality() != 1) {
        $this->sqlQuery->addMetaData('simple_query', FALSE);
      }
      $entity_type = $this->sqlQuery->getMetaData('entity_type');
      $this->fieldTables[$index_prefix . $field_name] = $this->addJoin($type, $table, "%alias.$field_id_field = $base_table.$entity_id_field", $langcode);
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
