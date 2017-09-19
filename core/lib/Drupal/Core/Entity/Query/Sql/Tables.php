<?php

namespace Drupal\Core\Entity\Query\Sql;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\Query\QueryException;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Entity\Sql\TableMappingInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceDefinitionInterface;

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
  protected $entityTables = [];

  /**
   * Field table array, key is table name, value is alias.
   *
   * This array contains one entry per field table.
   *
   * @var array
   */
  protected $fieldTables = [];

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * List of case sensitive fields.
   *
   * @var array
   */
  protected $caseSensitiveFields = [];

  /**
   * @param \Drupal\Core\Database\Query\SelectInterface $sql_query
   */
  public function __construct(SelectInterface $sql_query) {
    $this->sqlQuery = $sql_query;
    $this->entityManager = \Drupal::entityManager();
  }

  /**
   * {@inheritdoc}
   */
  public function addField($field, $type, $langcode) {
    $entity_type_id = $this->sqlQuery->getMetaData('entity_type');
    $all_revisions = $this->sqlQuery->getMetaData('all_revisions');
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
    $propertyDefinitions = [];
    $entity_type = $this->entityManager->getDefinition($entity_type_id);

    $field_storage_definitions = $this->entityManager->getFieldStorageDefinitions($entity_type_id);
    for ($key = 0; $key <= $count; $key++) {
      // This can either be the name of an entity base field or a configurable
      // field.
      $specifier = $specifiers[$key];
      if (isset($field_storage_definitions[$specifier])) {
        $field_storage = $field_storage_definitions[$specifier];
      }
      else {
        $field_storage = FALSE;
      }

      // If there is revision support, only the current revisions are being
      // queried, and the field is revisionable then use the revision id.
      // Otherwise, the entity id will do.
      if (($revision_key = $entity_type->getKey('revision')) && $all_revisions && $field_storage && $field_storage->isRevisionable()) {
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

      /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
      $table_mapping = $this->entityManager->getStorage($entity_type_id)->getTableMapping();

      // Check whether this field is stored in a dedicated table.
      if ($field_storage && $table_mapping->requiresDedicatedTableStorage($field_storage)) {
        $delta = NULL;
        // Find the field column.
        $column = $field_storage->getMainPropertyName();

        if ($key < $count) {
          $next = $specifiers[$key + 1];
          // If this is a numeric specifier we're adding a condition on the
          // specific delta.
          if (is_numeric($next)) {
            $delta = $next;
            $index_prefix .= ".$delta";
            // Do not process it again.
            $key++;
            $next = $specifiers[$key + 1];
          }
          // If this specifier is the reserved keyword "%delta" we're adding a
          // condition on a delta range.
          elseif ($next == TableMappingInterface::DELTA) {
            $index_prefix .= TableMappingInterface::DELTA;
            // Do not process it again.
            $key++;
            // If there are more specifiers to work with then continue
            // processing. If this is the last specifier then use the reserved
            // keyword as a column name.
            if ($key < $count) {
              $next = $specifiers[$key + 1];
            }
            else {
              $column = TableMappingInterface::DELTA;
            }
          }
          // Is this a field column?
          $columns = $field_storage->getColumns();
          if (isset($columns[$next]) || in_array($next, $table_mapping->getReservedColumns())) {
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
            $propertyDefinitions = $field_storage->getPropertyDefinitions();

            // Prepare the next index prefix.
            $next_index_prefix = "$relationship_specifier.$column";
          }
        }
        $table = $this->ensureFieldTable($index_prefix, $field_storage, $type, $langcode, $base_table, $entity_id_field, $field_id_field, $delta);
        $sql_column = $table_mapping->getFieldColumnName($field_storage, $column);
        $property_definitions = $field_storage->getPropertyDefinitions();
        if (isset($property_definitions[$column])) {
          $this->caseSensitiveFields[$field] = $property_definitions[$column]->getSetting('case_sensitive');
        }
      }
      // The field is stored in a shared table.
      else {
        // ensureEntityTable() decides whether an entity property will be
        // queried from the data table or the base table based on where it
        // finds the property first. The data table is preferred, which is why
        // it gets added before the base table.
        $entity_tables = [];
        if ($all_revisions && $field_storage && $field_storage->isRevisionable()) {
          $data_table = $entity_type->getRevisionDataTable();
          $entity_base_table = $entity_type->getRevisionTable();
        }
        else {
          $data_table = $entity_type->getDataTable();
          $entity_base_table = $entity_type->getBaseTable();
        }
        if ($data_table) {
          $this->sqlQuery->addMetaData('simple_query', FALSE);
          $entity_tables[$data_table] = $this->getTableMapping($data_table, $entity_type_id);
        }
        $entity_tables[$entity_base_table] = $this->getTableMapping($entity_base_table, $entity_type_id);
        $sql_column = $specifier;

        // If there are more specifiers, get the right sql column name if the
        // next one is a column of this field.
        if ($key < $count) {
          $next = $specifiers[$key + 1];
          // If this specifier is the reserved keyword "%delta" we're adding a
          // condition on a delta range.
          if ($next == TableMappingInterface::DELTA) {
            $key++;
            if ($key < $count) {
              $next = $specifiers[$key + 1];
            }
            else {
              return 0;
            }
          }
          // If this is a numeric specifier we're adding a condition on the
          // specific delta. Since we know that this is a single value base
          // field no other value than 0 makes sense.
          if (is_numeric($next)) {
            if ($next > 0) {
              $this->sqlQuery->condition('1 <> 1');
            }
            $key++;
            $next = $specifiers[$key + 1];
          }
          // Is this a field column?
          $columns = $field_storage->getColumns();
          if (isset($columns[$next]) || in_array($next, $table_mapping->getReservedColumns())) {
            // Use it.
            $sql_column = $table_mapping->getFieldColumnName($field_storage, $next);
            // Do not process it again.
            $key++;
          }
        }

        $table = $this->ensureEntityTable($index_prefix, $sql_column, $type, $langcode, $base_table, $entity_id_field, $entity_tables);

        // If there is a field storage (some specifiers are not), check for case
        // sensitivity.
        if ($field_storage) {
          $column = $field_storage->getMainPropertyName();
          $base_field_property_definitions = $field_storage->getPropertyDefinitions();
          if (isset($base_field_property_definitions[$column])) {
            $this->caseSensitiveFields[$field] = $base_field_property_definitions[$column]->getSetting('case_sensitive');
          }
        }

      }
      // If there are more specifiers to come, it's a relationship.
      if ($field_storage && $key < $count) {
        // Computed fields have prepared their property definition already, do
        // it for properties as well.
        if (!$propertyDefinitions) {
          $propertyDefinitions = $field_storage->getPropertyDefinitions();
          $relationship_specifier = $specifiers[$key + 1];
          $next_index_prefix = $relationship_specifier;
        }
        $entity_type_id = NULL;
        // Relationship specifier can also contain the entity type ID, i.e.
        // entity:node, entity:user or entity:taxonomy.
        if (strpos($relationship_specifier, ':') !== FALSE) {
          list($relationship_specifier, $entity_type_id) = explode(':', $relationship_specifier, 2);
        }
        // Check for a valid relationship.
        if (isset($propertyDefinitions[$relationship_specifier]) && $propertyDefinitions[$relationship_specifier] instanceof DataReferenceDefinitionInterface) {
          // If it is, use the entity type if specified already, otherwise use
          // the definition.
          $target_definition = $propertyDefinitions[$relationship_specifier]->getTargetDefinition();
          if (!$entity_type_id && $target_definition instanceof EntityDataDefinitionInterface) {
            $entity_type_id = $target_definition->getEntityTypeId();
          }
          $entity_type = $this->entityManager->getDefinition($entity_type_id);
          $field_storage_definitions = $this->entityManager->getFieldStorageDefinitions($entity_type_id);
          // Add the new entity base table using the table and sql column.
          // An additional $field_storage argument is being passed to
          // addNextBaseTable() in order to improve its functionality, for
          // example by allowing extra processing based on the field type of the
          // storage. In order to maintain backwards compatibility in 8.4.x, the
          // new argument has not been added to the signature of that method,
          // and it will be added only in 8.5.x.
          // @todo Add the $field_storage argument to addNextBaseTable() in
          //   8.5.x. https://www.drupal.org/node/2909425
          $base_table = $this->addNextBaseTable($entity_type, $table, $sql_column, $field_storage);
          $propertyDefinitions = [];
          $key++;
          $index_prefix .= "$next_index_prefix.";
        }
        else {
          throw new QueryException("Invalid specifier '$relationship_specifier'");
        }
      }
    }
    return "$table.$sql_column";
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldCaseSensitive($field_name) {
    if (isset($this->caseSensitiveFields[$field_name])) {
      return $this->caseSensitiveFields[$field_name];
    }
  }

  /**
   * Join entity table if necessary and return the alias for it.
   *
   * @param string $property
   *
   * @return string
   *
   * @throws \Drupal\Core\Entity\Query\QueryException
   */
  protected function ensureEntityTable($index_prefix, $property, $type, $langcode, $base_table, $id_field, $entity_tables) {
    foreach ($entity_tables as $table => $mapping) {
      if (isset($mapping[$property])) {
        if (!isset($this->entityTables[$index_prefix . $table])) {
          $this->entityTables[$index_prefix . $table] = $this->addJoin($type, $table, "%alias.$id_field = $base_table.$id_field", $langcode);
        }
        return $this->entityTables[$index_prefix . $table];
      }
    }
    throw new QueryException("'$property' not found");
  }

  /**
   * Join field table if necessary.
   *
   * @param $field_name
   *   Name of the field.
   * @return string
   * @throws \Drupal\Core\Entity\Query\QueryException
   */
  protected function ensureFieldTable($index_prefix, &$field, $type, $langcode, $base_table, $entity_id_field, $field_id_field, $delta) {
    $field_name = $field->getName();
    if (!isset($this->fieldTables[$index_prefix . $field_name])) {
      $entity_type_id = $this->sqlQuery->getMetaData('entity_type');
      /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
      $table_mapping = $this->entityManager->getStorage($entity_type_id)->getTableMapping();
      $table = !$this->sqlQuery->getMetaData('all_revisions') ? $table_mapping->getDedicatedDataTableName($field) : $table_mapping->getDedicatedRevisionTableName($field);
      if ($field->getCardinality() != 1) {
        $this->sqlQuery->addMetaData('simple_query', FALSE);
      }
      $this->fieldTables[$index_prefix . $field_name] = $this->addJoin($type, $table, "%alias.$field_id_field = $base_table.$entity_id_field", $langcode, $delta);
    }
    return $this->fieldTables[$index_prefix . $field_name];
  }

  protected function addJoin($type, $table, $join_condition, $langcode, $delta = NULL) {
    $arguments = [];
    if ($langcode) {
      $entity_type_id = $this->sqlQuery->getMetaData('entity_type');
      $entity_type = $this->entityManager->getDefinition($entity_type_id);
      // Only the data table follows the entity language key, dedicated field
      // tables have an hard-coded 'langcode' column.
      $langcode_key = $entity_type->getDataTable() == $table ? $entity_type->getKey('langcode') : 'langcode';
      $placeholder = ':langcode' . $this->sqlQuery->nextPlaceholder();
      $join_condition .= ' AND %alias.' . $langcode_key . ' = ' . $placeholder;
      $arguments[$placeholder] = $langcode;
    }
    if (isset($delta)) {
      $placeholder = ':delta' . $this->sqlQuery->nextPlaceholder();
      $join_condition .= ' AND %alias.delta = ' . $placeholder;
      $arguments[$placeholder] = $delta;
    }
    return $this->sqlQuery->addJoin($type, $table, NULL, $join_condition, $arguments);
  }

  /**
   * Gets the schema for the given table.
   *
   * @param string $table
   *   The table name.
   *
   * @return array|bool
   *   The table field mapping for the given table or FALSE if not available.
   */
  protected function getTableMapping($table, $entity_type_id) {
    $storage = $this->entityManager->getStorage($entity_type_id);
    if ($storage instanceof SqlEntityStorageInterface) {
      $mapping = $storage->getTableMapping()->getAllColumns($table);
    }
    else {
      return FALSE;
    }
    return array_flip($mapping);
  }

  /**
   * Add the next entity base table.
   *
   * For example, when building the SQL query for
   * @code
   * condition('uid.entity.name', 'foo', 'CONTAINS')
   * @endcode
   *
   * this adds the users table.
   *
   * @param \Drupal\Core\Entity\EntityType $entity_type
   *   The entity type being joined, in the above example, User.
   * @param string $table
   *   This is the table being joined, in the above example, {users}.
   * @param string $sql_column
   *   This is the SQL column in the existing table being joined to.
   *
   * @return string
   *   The alias of the next entity table joined in.
   */
  protected function addNextBaseTable(EntityType $entity_type, $table, $sql_column) {
    $join_condition = '%alias.' . $entity_type->getKey('id') . " = $table.$sql_column";
    return $this->sqlQuery->leftJoin($entity_type->getBaseTable(), NULL, $join_condition);
  }

}
