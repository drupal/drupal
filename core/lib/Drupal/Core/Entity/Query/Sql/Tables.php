<?php

namespace Drupal\Core\Entity\Query\Sql;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\Query\QueryException;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Entity\Sql\TableMappingInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceDefinitionInterface;

/**
 * Adds tables and fields to the SQL entity query.
 */
class Tables implements TablesInterface {

  use DeprecatedServicePropertyTrait;

  /**
   * {@inheritdoc}
   */
  protected $deprecatedProperties = ['entityManager' => 'entity.manager'];

  /**
   * @var \Drupal\Core\Database\Query\SelectInterface
   */
  protected $sqlQuery;

  /**
   * Entity table array.
   *
   * This array contains at most two entries: one for the data, one for the
   * properties. Its keys are unique references to the tables, values are
   * aliases.
   *
   * @see \Drupal\Core\Entity\Query\Sql\Tables::ensureEntityTable().
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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

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
    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->entityFieldManager = \Drupal::service('entity_field.manager');
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
    $entity_type = $this->entityTypeManager->getActiveDefinition($entity_type_id);

    $field_storage_definitions = $this->entityFieldManager->getActiveFieldStorageDefinitions($entity_type_id);
    for ($key = 0; $key <= $count; $key++) {
      // This can either be the name of an entity base field or a configurable
      // field.
      $specifier = $specifiers[$key];
      if (isset($field_storage_definitions[$specifier])) {
        $field_storage = $field_storage_definitions[$specifier];
        $column = $field_storage->getMainPropertyName();
      }
      else {
        $field_storage = FALSE;
        $column = NULL;
      }

      // If there is revision support, all the revisions are being queried, and
      // the field is revisionable or the revision ID field itself, then use the
      // revision ID. Otherwise, the entity ID will do.
      $query_revisions = $all_revisions && $field_storage && ($field_storage->isRevisionable() || $field_storage->getName() === $entity_type->getKey('revision'));
      if ($query_revisions) {
        // This contains the relevant SQL field to be used when joining entity
        // tables.
        $entity_id_field = $entity_type->getKey('revision');
        // This contains the relevant SQL field to be used when joining field
        // tables.
        $field_id_field = 'revision_id';
      }
      else {
        $entity_id_field = $entity_type->getKey('id');
        $field_id_field = 'entity_id';
      }

      /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
      $table_mapping = $this->entityTypeManager->getStorage($entity_type_id)->getTableMapping();

      // Check whether this field is stored in a dedicated table.
      if ($field_storage && $table_mapping->requiresDedicatedTableStorage($field_storage)) {
        $delta = NULL;

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
      }
      // The field is stored in a shared table.
      else {
        // ensureEntityTable() decides whether an entity property will be
        // queried from the data table or the base table based on where it
        // finds the property first. The data table is preferred, which is why
        // it gets added before the base table.
        $entity_tables = [];
        $revision_table = NULL;
        if ($query_revisions) {
          $data_table = $entity_type->getRevisionDataTable();
          $entity_base_table = $entity_type->getRevisionTable();
        }
        else {
          $data_table = $entity_type->getDataTable();
          $entity_base_table = $entity_type->getBaseTable();

          if ($field_storage && $field_storage->isRevisionable() && in_array($field_storage->getName(), $entity_type->getRevisionMetadataKeys())) {
            $revision_table = $entity_type->getRevisionTable();
          }
        }
        if ($data_table) {
          $this->sqlQuery->addMetaData('simple_query', FALSE);
          $entity_tables[$data_table] = $this->getTableMapping($data_table, $entity_type_id);
        }
        if ($revision_table) {
          $entity_tables[$revision_table] = $this->getTableMapping($revision_table, $entity_type_id);
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
              $this->sqlQuery->alwaysFalse();
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
      }

      // If there is a field storage (some specifiers are not) and a field
      // column, check for case sensitivity.
      if ($field_storage && $column) {
        $property_definitions = $field_storage->getPropertyDefinitions();
        if (isset($property_definitions[$column])) {
          $this->caseSensitiveFields[$field] = $property_definitions[$column]->getSetting('case_sensitive');
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
          $entity_type = $this->entityTypeManager->getActiveDefinition($entity_type_id);
          $field_storage_definitions = $this->entityFieldManager->getActiveFieldStorageDefinitions($entity_type_id);
          // Add the new entity base table using the table and sql column.
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
   * Joins the entity table, if necessary, and returns the alias for it.
   *
   * @param string $index_prefix
   *   The table array index prefix. For a base table this will be empty,
   *   for a target entity reference like 'field_tags.entity:taxonomy_term.name'
   *   this will be 'entity:taxonomy_term.target_id.'.
   * @param string $property
   *   The field property/column.
   * @param string $type
   *   The join type, can either be INNER or LEFT.
   * @param string $langcode
   *   The langcode we use on the join.
   * @param string $base_table
   *   The table to join to. It can be either the table name, its alias or the
   *   'base_table' placeholder.
   * @param string $id_field
   *   The name of the ID field/property for the current entity. For instance:
   *   tid, nid, etc.
   * @param array $entity_tables
   *   Array of entity tables (data and base tables) where decide the entity
   *   property will be queried from. The first table containing the property
   *   will be used, so the order is important and the data table is always
   *   preferred.
   *
   * @return string
   *   The alias of the joined table.
   *
   * @throws \Drupal\Core\Entity\Query\QueryException
   *   When an invalid property has been passed.
   */
  protected function ensureEntityTable($index_prefix, $property, $type, $langcode, $base_table, $id_field, $entity_tables) {
    foreach ($entity_tables as $table => $mapping) {
      if (isset($mapping[$property])) {
        // Ensure a table joined multiple times through different index prefixes
        // has unique entityTables entries by concatenating the index prefix
        // and the base table alias. In this way i.e. if we join to the same
        // entity table several times for different entity reference fields,
        // each join gets a separate alias.
        $key = $index_prefix . ($base_table === 'base_table' ? $table : $base_table);
        if (!isset($this->entityTables[$key])) {
          $this->entityTables[$key] = $this->addJoin($type, $table, "%alias.$id_field = $base_table.$id_field", $langcode);
        }
        return $this->entityTables[$key];
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
      $table_mapping = $this->entityTypeManager->getStorage($entity_type_id)->getTableMapping();
      $table = !$this->sqlQuery->getMetaData('all_revisions') ? $table_mapping->getDedicatedDataTableName($field) : $table_mapping->getDedicatedRevisionTableName($field);
      if ($field->getCardinality() != 1) {
        $this->sqlQuery->addMetaData('simple_query', FALSE);
      }
      $this->fieldTables[$index_prefix . $field_name] = $this->addJoin($type, $table, "%alias.$field_id_field = $base_table.$entity_id_field", $langcode, $delta);
    }
    return $this->fieldTables[$index_prefix . $field_name];
  }

  /**
   * Adds a join to a given table.
   *
   * @param string $type
   *   The join type.
   * @param string $table
   *   The table to join to.
   * @param string $join_condition
   *   The condition on which to join to.
   * @param string $langcode
   *   The langcode we use on the join.
   * @param string|null $delta
   *   (optional) A delta which should be used as additional condition.
   *
   * @return string
   *   Returns the alias of the joined table.
   */
  protected function addJoin($type, $table, $join_condition, $langcode, $delta = NULL) {
    $arguments = [];
    if ($langcode) {
      $entity_type_id = $this->sqlQuery->getMetaData('entity_type');
      $entity_type = $this->entityTypeManager->getActiveDefinition($entity_type_id);
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
   * @return array|false
   *   An associative array of table field mapping for the given table, keyed by
   *   columns name and values are just incrementing integers. If the table
   *   mapping is not available, FALSE is returned.
   */
  protected function getTableMapping($table, $entity_type_id) {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
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
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage
   *   The field storage definition for the field referencing this column.
   *
   * @return string
   *   The alias of the next entity table joined in.
   */
  protected function addNextBaseTable(EntityType $entity_type, $table, $sql_column, FieldStorageDefinitionInterface $field_storage) {
    $join_condition = '%alias.' . $entity_type->getKey('id') . " = $table.$sql_column";
    return $this->sqlQuery->leftJoin($entity_type->getBaseTable(), NULL, $join_condition);
  }

}
