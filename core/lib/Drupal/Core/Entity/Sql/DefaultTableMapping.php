<?php

namespace Drupal\Core\Entity\Sql;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines a default table mapping class.
 */
class DefaultTableMapping implements TableMappingInterface {

  /**
   * The entity type definition.
   *
   * @var \Drupal\Core\Entity\ContentEntityTypeInterface
   */
  protected $entityType;

  /**
   * The field storage definitions of this mapping.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionInterface[]
   */
  protected $fieldStorageDefinitions = [];

  /**
   * The prefix to be used by all the tables of this mapping.
   *
   * @var string
   */
  protected $prefix;

  /**
   * The base table of the entity.
   *
   * @var string
   */
  protected $baseTable;

  /**
   * The table that stores revisions, if the entity supports revisions.
   *
   * @var string
   */
  protected $revisionTable;

  /**
   * The table that stores field data, if the entity has multilingual support.
   *
   * @var string
   */
  protected $dataTable;

  /**
   * The table that stores revision field data if the entity supports revisions
   * and has multilingual support.
   *
   * @var string
   */
  protected $revisionDataTable;

  /**
   * A list of field names per table.
   *
   * This corresponds to the return value of
   * TableMappingInterface::getFieldNames() except that this variable is
   * additionally keyed by table name.
   *
   * @var array[]
   */
  protected $fieldNames = [];

  /**
   * A list of database columns which store denormalized data per table.
   *
   * This corresponds to the return value of
   * TableMappingInterface::getExtraColumns() except that this variable is
   * additionally keyed by table name.
   *
   * @var array[]
   */
  protected $extraColumns = [];

  /**
   * A mapping of column names per field name.
   *
   * This corresponds to the return value of
   * TableMappingInterface::getColumnNames() except that this variable is
   * additionally keyed by field name.
   *
   * This data is derived from static::$storageDefinitions, but is stored
   * separately to avoid repeated processing.
   *
   * @var array[]
   */
  protected $columnMapping = [];

  /**
   * A list of all database columns per table.
   *
   * This corresponds to the return value of
   * TableMappingInterface::getAllColumns() except that this variable is
   * additionally keyed by table name.
   *
   * This data is derived from static::$storageDefinitions, static::$fieldNames,
   * and static::$extraColumns, but is stored separately to avoid repeated
   * processing.
   *
   * @var array[]
   */
  protected $allColumns = [];

  /**
   * Constructs a DefaultTableMapping.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface[] $storage_definitions
   *   A list of field storage definitions that should be available for the
   *   field columns of this table mapping.
   * @param string $prefix
   *   (optional) A prefix to be used by all the tables of this mapping.
   *   Defaults to an empty string.
   */
  public function __construct(ContentEntityTypeInterface $entity_type, array $storage_definitions, $prefix = '') {
    $this->entityType = $entity_type;
    $this->fieldStorageDefinitions = $storage_definitions;
    $this->prefix = $prefix;

    // @todo Remove table names from the entity type definition in
    //   https://www.drupal.org/node/2232465.
    $this->baseTable = $this->prefix . $entity_type->getBaseTable() ?: $entity_type->id();
    if ($entity_type->isRevisionable()) {
      $this->revisionTable = $this->prefix . $entity_type->getRevisionTable() ?: $entity_type->id() . '_revision';
    }
    if ($entity_type->isTranslatable()) {
      $this->dataTable = $this->prefix . $entity_type->getDataTable() ?: $entity_type->id() . '_field_data';
    }
    if ($entity_type->isRevisionable() && $entity_type->isTranslatable()) {
      $this->revisionDataTable = $this->prefix . $entity_type->getRevisionDataTable() ?: $entity_type->id() . '_field_revision';
    }
  }

  /**
   * Initializes the table mapping.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface[] $storage_definitions
   *   A list of field storage definitions that should be available for the
   *   field columns of this table mapping.
   * @param string $prefix
   *   (optional) A prefix to be used by all the tables of this mapping.
   *   Defaults to an empty string.
   *
   * @return static
   *
   * @internal
   */
  public static function create(ContentEntityTypeInterface $entity_type, array $storage_definitions, $prefix = '') {
    $table_mapping = new static($entity_type, $storage_definitions, $prefix);

    $revisionable = $entity_type->isRevisionable();
    $translatable = $entity_type->isTranslatable();

    $id_key = $entity_type->getKey('id');
    $revision_key = $entity_type->getKey('revision');
    $bundle_key = $entity_type->getKey('bundle');
    $uuid_key = $entity_type->getKey('uuid');
    $langcode_key = $entity_type->getKey('langcode');

    $shared_table_definitions = array_filter($storage_definitions, function (FieldStorageDefinitionInterface $definition) use ($table_mapping) {
      return $table_mapping->allowsSharedTableStorage($definition);
    });

    $key_fields = array_values(array_filter([$id_key, $revision_key, $bundle_key, $uuid_key, $langcode_key]));
    $all_fields = array_keys($shared_table_definitions);
    $revisionable_fields = array_keys(array_filter($shared_table_definitions, function (FieldStorageDefinitionInterface $definition) {
      return $definition->isRevisionable();
    }));
    // Make sure the key fields come first in the list of fields.
    $all_fields = array_merge($key_fields, array_diff($all_fields, $key_fields));

    $revision_metadata_fields = $revisionable ? array_values($entity_type->getRevisionMetadataKeys()) : [];
    $revision_metadata_fields = array_intersect($revision_metadata_fields, array_keys($storage_definitions));

    if (!$revisionable && !$translatable) {
      // The base layout stores all the base field values in the base table.
      $table_mapping->setFieldNames($table_mapping->baseTable, $all_fields);
    }
    elseif ($revisionable && !$translatable) {
      // The revisionable layout stores all the base field values in the base
      // table, except for revision metadata fields. Revisionable fields
      // denormalized in the base table but also stored in the revision table
      // together with the entity ID and the revision ID as identifiers.
      $table_mapping->setFieldNames($table_mapping->baseTable, array_diff($all_fields, $revision_metadata_fields));
      $revision_key_fields = [$id_key, $revision_key];
      $table_mapping->setFieldNames($table_mapping->revisionTable, array_merge($revision_key_fields, $revisionable_fields));
    }
    elseif (!$revisionable && $translatable) {
      // Multilingual layouts store key field values in the base table. The
      // other base field values are stored in the data table, no matter
      // whether they are translatable or not. The data table holds also a
      // denormalized copy of the bundle field value to allow for more
      // performant queries. This means that only the UUID is not stored on
      // the data table.
      $table_mapping
        ->setFieldNames($table_mapping->baseTable, $key_fields)
        ->setFieldNames($table_mapping->dataTable, array_values(array_diff($all_fields, [$uuid_key])));
    }
    elseif ($revisionable && $translatable) {
      // The revisionable multilingual layout stores key field values in the
      // base table and the revision table holds the entity ID, revision ID and
      // langcode ID along with revision metadata. The revision data table holds
      // data field values for all the revisionable fields and the data table
      // holds the data field values for all non-revisionable fields. The data
      // field values of revisionable fields are denormalized in the data
      // table, as well.
      $table_mapping->setFieldNames($table_mapping->baseTable, $key_fields);

      // Like in the multilingual, non-revisionable case the UUID is not
      // in the data table. Additionally, do not store revision metadata
      // fields in the data table.
      $data_fields = array_values(array_diff($all_fields, [$uuid_key], $revision_metadata_fields));
      $table_mapping->setFieldNames($table_mapping->dataTable, $data_fields);

      $revision_base_fields = array_merge([$id_key, $revision_key, $langcode_key], $revision_metadata_fields);
      $table_mapping->setFieldNames($table_mapping->revisionTable, $revision_base_fields);

      $revision_data_key_fields = [$id_key, $revision_key, $langcode_key];
      $revision_data_fields = array_diff($revisionable_fields, $revision_metadata_fields, [$langcode_key]);
      $table_mapping->setFieldNames($table_mapping->revisionDataTable, array_merge($revision_data_key_fields, $revision_data_fields));
    }

    // Add dedicated tables.
    $dedicated_table_definitions = array_filter($table_mapping->fieldStorageDefinitions, function (FieldStorageDefinitionInterface $definition) use ($table_mapping) {
      return $table_mapping->requiresDedicatedTableStorage($definition);
    });
    $extra_columns = [
      'bundle',
      'deleted',
      'entity_id',
      'revision_id',
      'langcode',
      'delta',
    ];
    foreach ($dedicated_table_definitions as $field_name => $definition) {
      $tables = [$table_mapping->getDedicatedDataTableName($definition)];
      if ($revisionable && $definition->isRevisionable()) {
        $tables[] = $table_mapping->getDedicatedRevisionTableName($definition);
      }
      foreach ($tables as $table_name) {
        $table_mapping->setFieldNames($table_name, [$field_name]);
        $table_mapping->setExtraColumns($table_name, $extra_columns);
      }
    }

    return $table_mapping;
  }

  /**
   * Gets the base table name.
   *
   * @return string
   *   The base table name.
   *
   * @internal
   */
  public function getBaseTable() {
    return $this->baseTable;
  }

  /**
   * Gets the revision table name.
   *
   * @return string|null
   *   The revision table name.
   *
   * @internal
   */
  public function getRevisionTable() {
    return $this->revisionTable;
  }

  /**
   * Gets the data table name.
   *
   * @return string|null
   *   The data table name.
   *
   * @internal
   */
  public function getDataTable() {
    return $this->dataTable;
  }

  /**
   * Gets the revision data table name.
   *
   * @return string|null
   *   The revision data table name.
   *
   * @internal
   */
  public function getRevisionDataTable() {
    return $this->revisionDataTable;
  }

  /**
   * {@inheritdoc}
   */
  public function getTableNames() {
    return array_unique(array_merge(array_keys($this->fieldNames), array_keys($this->extraColumns)));
  }

  /**
   * {@inheritdoc}
   */
  public function getAllColumns($table_name) {
    if (!isset($this->allColumns[$table_name])) {
      $this->allColumns[$table_name] = [];

      foreach ($this->getFieldNames($table_name) as $field_name) {
        $this->allColumns[$table_name] = array_merge($this->allColumns[$table_name], array_values($this->getColumnNames($field_name)));
      }

      // There is just one field for each dedicated storage table, thus
      // $field_name can only refer to it.
      if (isset($field_name) && $this->requiresDedicatedTableStorage($this->fieldStorageDefinitions[$field_name])) {
        // Unlike in shared storage tables, in dedicated ones field columns are
        // positioned last.
        $this->allColumns[$table_name] = array_merge($this->getExtraColumns($table_name), $this->allColumns[$table_name]);
      }
      else {
        $this->allColumns[$table_name] = array_merge($this->allColumns[$table_name], $this->getExtraColumns($table_name));
      }
    }
    return $this->allColumns[$table_name];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldNames($table_name) {
    if (isset($this->fieldNames[$table_name])) {
      return $this->fieldNames[$table_name];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldTableName($field_name) {
    $result = NULL;

    if (isset($this->fieldStorageDefinitions[$field_name])) {
      // Since a field may be stored in more than one table, we inspect tables
      // in order of relevance: the data table if present is the main place
      // where field data is stored, otherwise the base table is responsible for
      // storing field data. Revision metadata is an exception as it's stored
      // only in the revision table.
      $storage_definition = $this->fieldStorageDefinitions[$field_name];
      $table_names = array_filter([
        $this->dataTable,
        $this->baseTable,
        $this->revisionTable,
        $this->getDedicatedDataTableName($storage_definition),
      ]);

      // Collect field columns.
      $field_columns = [];
      foreach (array_keys($storage_definition->getColumns()) as $property_name) {
        $field_columns[] = $this->getFieldColumnName($storage_definition, $property_name);
      }

      foreach ($table_names as $table_name) {
        $columns = $this->getAllColumns($table_name);
        // We assume finding one field column belonging to the mapping is enough
        // to identify the field table.
        if (array_intersect($columns, $field_columns)) {
          $result = $table_name;
          break;
        }
      }
    }

    if (!isset($result)) {
      throw new SqlContentEntityStorageException("Table information not available for the '$field_name' field.");
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllFieldTableNames($field_name) {
    return array_keys(array_filter($this->fieldNames, function ($table_fields) use ($field_name) {
      return in_array($field_name, $table_fields, TRUE);
    }));
  }

  /**
   * {@inheritdoc}
   */
  public function getColumnNames($field_name) {
    if (!isset($this->columnMapping[$field_name])) {
      $this->columnMapping[$field_name] = [];
      if (isset($this->fieldStorageDefinitions[$field_name]) && !$this->fieldStorageDefinitions[$field_name]->hasCustomStorage()) {
        foreach (array_keys($this->fieldStorageDefinitions[$field_name]->getColumns()) as $property_name) {
          $this->columnMapping[$field_name][$property_name] = $this->getFieldColumnName($this->fieldStorageDefinitions[$field_name], $property_name);
        }
      }
    }
    return $this->columnMapping[$field_name];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldColumnName(FieldStorageDefinitionInterface $storage_definition, $property_name) {
    $field_name = $storage_definition->getName();

    if ($this->allowsSharedTableStorage($storage_definition)) {
      $column_name = count($storage_definition->getColumns()) == 1 ? $field_name : $field_name . '__' . $property_name;
    }
    elseif ($this->requiresDedicatedTableStorage($storage_definition)) {
      if ($property_name == TableMappingInterface::DELTA) {
        $column_name = 'delta';
      }
      else {
        $column_name = !in_array($property_name, $this->getReservedColumns()) ? $field_name . '_' . $property_name : $property_name;
      }
    }
    else {
      throw new SqlContentEntityStorageException("Column information not available for the '$field_name' field.");
    }

    return $column_name;
  }

  /**
   * Adds field columns for a table to the table mapping.
   *
   * @param string $table_name
   *   The name of the table to add the field column for.
   * @param string[] $field_names
   *   A list of field names to add the columns for.
   *
   * @return $this
   *
   * @internal
   *
   * @todo Make this method protected in drupal:9.0.0.
   * @see https://www.drupal.org/node/3067336
   */
  public function setFieldNames($table_name, array $field_names) {
    $this->fieldNames[$table_name] = $field_names;
    // Force the re-computation of the column list.
    unset($this->allColumns[$table_name]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtraColumns($table_name) {
    if (isset($this->extraColumns[$table_name])) {
      return $this->extraColumns[$table_name];
    }
    return [];
  }

  /**
   * Adds extra columns for a table to the table mapping.
   *
   * @param string $table_name
   *   The name of table to add the extra columns for.
   * @param string[] $column_names
   *   The list of column names.
   *
   * @return $this
   *
   * @internal
   *
   * @todo Make this method protected in drupal:9.0.0.
   * @see https://www.drupal.org/node/3067336
   */
  public function setExtraColumns($table_name, array $column_names) {
    $this->extraColumns[$table_name] = $column_names;
    // Force the re-computation of the column list.
    unset($this->allColumns[$table_name]);
    return $this;
  }

  /**
   * Checks whether the given field can be stored in a shared table.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition.
   *
   * @return bool
   *   TRUE if the field can be stored in a shared table, FALSE otherwise.
   */
  public function allowsSharedTableStorage(FieldStorageDefinitionInterface $storage_definition) {
    return !$storage_definition->hasCustomStorage() && $storage_definition->isBaseField() && !$storage_definition->isMultiple() && !$storage_definition->isDeleted();
  }

  /**
   * Checks whether the given field has to be stored in a dedicated table.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition.
   *
   * @return bool
   *   TRUE if the field has to be stored in a dedicated table, FALSE otherwise.
   */
  public function requiresDedicatedTableStorage(FieldStorageDefinitionInterface $storage_definition) {
    return !$storage_definition->hasCustomStorage() && !$this->allowsSharedTableStorage($storage_definition);
  }

  /**
   * Gets a list of dedicated table names for this mapping.
   *
   * @return string[]
   *   An array of table names.
   */
  public function getDedicatedTableNames() {
    $table_mapping = $this;
    $definitions = array_filter($this->fieldStorageDefinitions, function ($definition) use ($table_mapping) {
      return $table_mapping->requiresDedicatedTableStorage($definition);
    });
    $data_tables = array_map(function ($definition) use ($table_mapping) {
      return $table_mapping->getDedicatedDataTableName($definition);
    }, $definitions);
    $revision_tables = array_map(function ($definition) use ($table_mapping) {
      return $table_mapping->getDedicatedRevisionTableName($definition);
    }, $definitions);
    $dedicated_tables = array_merge(array_values($data_tables), array_values($revision_tables));
    return $dedicated_tables;
  }

  /**
   * {@inheritdoc}
   */
  public function getReservedColumns() {
    return ['deleted'];
  }

  /**
   * Generates a table name for a field data table.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition.
   * @param bool $is_deleted
   *   (optional) Whether the table name holding the values of a deleted field
   *   should be returned.
   *
   * @return string
   *   A string containing the generated name for the database table.
   */
  public function getDedicatedDataTableName(FieldStorageDefinitionInterface $storage_definition, $is_deleted = FALSE) {
    if ($is_deleted) {
      // When a field is a deleted, the table is renamed to
      // {field_deleted_data_UNIQUE_STORAGE_ID}. To make sure we don't end up
      // with table names longer than 64 characters, we hash the unique storage
      // identifier and return the first 10 characters so we end up with a short
      // unique ID.
      return "field_deleted_data_" . substr(hash('sha256', $storage_definition->getUniqueStorageIdentifier()), 0, 10);
    }
    else {
      return $this->generateFieldTableName($storage_definition, FALSE);
    }
  }

  /**
   * Generates a table name for a field revision archive table.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition.
   * @param bool $is_deleted
   *   (optional) Whether the table name holding the values of a deleted field
   *   should be returned.
   *
   * @return string
   *   A string containing the generated name for the database table.
   */
  public function getDedicatedRevisionTableName(FieldStorageDefinitionInterface $storage_definition, $is_deleted = FALSE) {
    if ($is_deleted) {
      // When a field is a deleted, the table is renamed to
      // {field_deleted_revision_UNIQUE_STORAGE_ID}. To make sure we don't end
      // up with table names longer than 64 characters, we hash the unique
      // storage identifier and return the first 10 characters so we end up with
      // a short unique ID.
      return "field_deleted_revision_" . substr(hash('sha256', $storage_definition->getUniqueStorageIdentifier()), 0, 10);
    }
    else {
      return $this->generateFieldTableName($storage_definition, TRUE);
    }
  }

  /**
   * Generates a safe and unambiguous field table name.
   *
   * The method accounts for a maximum table name length of 64 characters, and
   * takes care of disambiguation.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition.
   * @param bool $revision
   *   TRUE for revision table, FALSE otherwise.
   *
   * @return string
   *   The final table name.
   */
  protected function generateFieldTableName(FieldStorageDefinitionInterface $storage_definition, $revision) {
    // The maximum length of an entity type ID is 32 characters.
    $entity_type_id = substr($storage_definition->getTargetEntityTypeId(), 0, EntityTypeInterface::ID_MAX_LENGTH);
    $separator = $revision ? '_revision__' : '__';

    $table_name = $this->prefix . $entity_type_id . $separator . $storage_definition->getName();
    // Limit the string to 48 characters, keeping a 16 characters margin for db
    // prefixes.
    if (strlen($table_name) > 48) {
      // Use a shorter separator and a hash of the field storage unique
      // identifier.
      $separator = $revision ? '_r__' : '__';
      $field_hash = substr(hash('sha256', $storage_definition->getUniqueStorageIdentifier()), 0, 10);

      $table_name = $this->prefix . $entity_type_id . $separator . $field_hash;

      // If the resulting table name is still longer than 48 characters, use the
      // following pattern:
      // - prefix: max 34 chars;
      // - separator: max 4 chars;
      // - field_hash: max 10 chars.
      if (strlen($table_name) > 48) {
        $prefix = substr($this->prefix, 0, 34);
        $table_name = $prefix . $separator . $field_hash;
      }
    }
    return $table_name;
  }

}
