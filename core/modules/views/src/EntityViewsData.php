<?php

/**
 * @file
 * Contains \Drupal\views\EntityViewsData.
 */

namespace Drupal\views;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Entity\Sql\TableMappingInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides generic views integration for entities.
 */
class EntityViewsData implements EntityHandlerInterface, EntityViewsDataInterface {

  use StringTranslationTrait;

  /**
   * Entity type for this views controller instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The storage used for this entity type.
   *
   * @var \Drupal\Core\Entity\Sql\SqlEntityStorageInterface
   */
  protected $storage;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * The field storage definitions for all base fields of the entity type.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionInterface[]
   */
  protected $fieldStorageDefinitions;

  /**
   * Constructs an EntityViewsData object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to provide views integration for.
   * @param \Drupal\Core\Entity\Sql\SqlEntityStorageInterface $storage_controller
   *   The storage controller used for this entity type.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The translation manager.
   */
  function __construct(EntityTypeInterface $entity_type, SqlEntityStorageInterface $storage_controller, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, TranslationInterface $translation_manager) {
    $this->entityType = $entity_type;
    $this->entityManager = $entity_manager;
    $this->storage = $storage_controller;
    $this->moduleHandler = $module_handler;
    $this->setStringTranslation($translation_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('string_translation'),
      $container->get('typed_data_manager')
    );
  }

  /**
   * Gets the field storage definitions.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface[]
   */
  protected function getFieldStorageDefinitions() {
    if (!isset($this->fieldStorageDefinitions)) {
      $this->fieldStorageDefinitions = $this->entityManager->getFieldStorageDefinitions($this->entityType->id());
    }
    return $this->fieldStorageDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = [];

    // @todo In theory we should use the data table as base table, as this would
    //   save one pointless join (and one more for every relationship).
    // @see https://drupal.org/node/2337509
    $base_table = $this->entityType->getBaseTable();
    $base_field = $this->entityType->getKey('id');
    $data_table = $this->entityType->getDataTable();
    $revision_table = $this->entityType->getRevisionTable();
    $revision_data_table = $this->entityType->getRevisionDataTable();
    $revision_field = $this->entityType->getKey('revision');

    // Setup base information of the views data.
    $data[$base_table]['table']['entity type'] = $this->entityType->id();
    $data[$base_table]['table']['group'] = $this->entityType->getLabel();
    $data[$base_table]['table']['base'] = [
      'field' => $base_field,
      'title' => $this->entityType->getLabel(),
    ];

    if ($label_key = $this->entityType->getKey('label')) {
      if ($data_table) {
        $data[$base_table]['table']['base']['defaults'] = array(
          'field' => $label_key,
          'table' => $data_table,
        );
      }
      else {
        $data[$base_table]['table']['base']['defaults'] = array(
          'field' => $label_key,
        );
      }
    }

    // Setup relations to the revisions/property data.
    if ($data_table) {
      $data[$data_table]['table']['join'][$base_table] = [
        'left_field' => $base_field,
        'field' => $base_field,
        'type' => 'INNER'
      ];
      $data[$data_table]['table']['entity type'] = $this->entityType->id();
      $data[$data_table]['table']['group'] = $this->entityType->getLabel();
    }
    if ($revision_table) {
      $data[$revision_table]['table']['entity type'] = $this->entityType->id();
      $data[$revision_table]['table']['group'] = $this->t('@entity_type revision', ['@entity_type' => $this->entityType->getLabel()]);
      $data[$revision_table]['table']['base'] = array(
        'field' => $revision_field,
        'title' => $this->t('@entity_type revisions', array('@entity_type' => $this->entityType->getLabel())),
      );
      // Join the revision table to the base table.
      $data[$revision_table]['table']['join'][$base_table] = array(
        'left_field' => $revision_field,
        'field' => $revision_field,
        'type' => 'INNER',
      );

      if ($revision_data_table) {
        $data[$revision_data_table]['table']['entity type'] = $this->entityType->id();
        $data[$revision_data_table]['table']['group'] = $this->t('@entity_type revision', ['@entity_type' => $this->entityType->getLabel()]);

        $data[$revision_data_table]['table']['join'][$revision_table] = array(
          'left_field' => $revision_field,
          'field' => $revision_field,
          'type' => 'INNER',
        );
      }
    }

    // Load all typed data definitions of all fields. This should cover each of
    // the entity base, revision, data tables.
    $field_definitions = $this->entityManager->getBaseFieldDefinitions($this->entityType->id());
    if ($table_mapping = $this->storage->getTableMapping()) {
      // Iterate over each table we have so far and collect field data for each.
      // Based on whether the field is in the field_definitions provided by the
      // entity manager.
      // @todo We should better just rely on information coming from the entity
      //   storage.
      // @todo https://drupal.org/node/2337511
      foreach ($table_mapping->getTableNames() as $table) {
        foreach ($table_mapping->getFieldNames($table) as $field_name) {
          $this->mapFieldDefinition($table, $field_name, $field_definitions[$field_name], $table_mapping, $data[$table]);
        }
      }
    }

    return $data;
  }

  /**
   * Puts the views data for a single field onto the views data.
   *
   * @param string $table
   *   The table of the field to handle.
   * @param string $field_name
   *   The name of the field to handle.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition defined in Entity::baseFieldDefinitions()
   * @param \Drupal\Core\Entity\Sql\TableMappingInterface $table_mapping
   *   The table mapping information
   * @param array $table_data
   *   A reference to a specific entity table (for example data_table) inside
   *   the views data.
   */
  protected function mapFieldDefinition($table, $field_name, FieldDefinitionInterface $field_definition, TableMappingInterface $table_mapping, &$table_data) {
    // Create a dummy instance to retrieve property definitions.
    $field_column_mapping = $table_mapping->getColumnNames($field_name);
    $field_schema = $this->getFieldStorageDefinitions()[$field_name]->getSchema();

    $field_definition_type = $field_definition->getType();
    // Add all properties to views table data.
    $first = TRUE;
    foreach ($field_column_mapping as $field_column_name => $schema_field_name) {
      $schema = $field_schema['columns'][$field_column_name];
      // We want to both have an entry in the views data for the actual field,
      // but also each additional schema field, for example the file
      // description.
      // @todo Introduce a concept of the "main" schema field for a field item.
      //   This would be the FID for a file reference for example.
      // @see https://www.drupal.org/node/2337517
      if ($first) {
        $first = FALSE;
        $table_data[$field_name] = $this->mapSingleFieldViewsData($table, $field_definition_type, $schema_field_name, $field_definition, TRUE);
      }
      else {
        $table_data["$field_name.$field_column_name"] = $this->mapSingleFieldViewsData($table, $schema['type'], $schema_field_name, $field_definition, FALSE);
      }
    }
  }

  /**
   * Provides the views data for a given data type and schema field.
   *
   * @param string $table
   *   The table of the field to handle.
   * @param string $data_type
   *   The data type to generate views data for, for example "int". The data
   *   type comes directly from the schema definition of each field item.
   * @param string $schema_field_name
   *   The schema field name.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param bool $first
   *   Is it the first column of the schema.
   *
   * @return array
   *   The modified views data field definition.
   */
  protected function mapSingleFieldViewsData($table, $data_type, $schema_field_name, FieldDefinitionInterface $field_definition, $first) {
    $views_field = array();

    // Provide a nicer, less verbose label for the first field.
    if ($first) {
      $views_field['title'] = $field_definition->getLabel();
    }
    else {
      $views_field['title'] = $field_definition->getLabel() . " ($schema_field_name)";
    }

    if ($description = $field_definition->getDescription()) {
      $views_field['help'] = $description;
    }

    // @todo Allow field types to customize this.
    // @see https://www.drupal.org/node/2337515
    switch ($data_type) {
      case 'int':
      case 'integer':
      case 'smallint':
      case 'tinyint':
      case 'mediumint':
      case 'float':
      case 'double':
      case 'decimal':
        $views_field['field']['id'] = 'numeric';
        $views_field['argument']['id'] = 'numeric';
        $views_field['filter']['id'] = 'numeric';
        $views_field['sort']['id'] = 'standard';
        break;
      case 'char':
      case 'string':
      case 'varchar':
      case 'tinytext':
      case 'text':
      case 'mediumtext':
      case 'longtext':
        $views_field['field']['id'] = 'standard';
        $views_field['argument']['id'] = 'string';
        $views_field['filter']['id'] = 'string';
        $views_field['sort']['id'] = 'standard';
        break;
      case 'boolean':
        $views_field['field']['id'] = 'boolean';
        $views_field['argument']['id'] = 'numeric';
        $views_field['filter']['id'] = 'boolean';
        $views_field['sort']['id'] = 'standard';
        break;
      case 'uuid':
        $views_field['field']['id'] = 'standard';
        $views_field['argument']['id'] = 'string';
        $views_field['filter']['id'] = 'string';
        $views_field['sort']['id'] = 'standard';
        break;
      case 'language':
        $views_field['field']['id'] = 'language';
        $views_field['argument']['id'] = 'language';
        $views_field['filter']['id'] = 'language';
        $views_field['sort']['id'] = 'standard';
        break;
      case 'created':
      case 'changed':
      $views_field['field']['id'] = 'date';
      $views_field['argument']['id'] = 'date';
      $views_field['filter']['id'] = 'date';
      $views_field['sort']['id'] = 'date';
        break;
      case 'entity_reference':
        // @todo Should the actual field handler respect that this is just renders a number
        // @todo Create an optional entity field handler, that can render the
        //   entity.
        // @see https://www.drupal.org/node/2322949
        $views_field['field']['id'] = 'standard';
        $views_field['argument']['id'] = 'standard';
        $views_field['filter']['id'] = 'standard';
        $views_field['sort']['id'] = 'standard';
        break;
      case 'uri':
        $views_field['field']['id'] = 'standard';
        $views_field['argument']['id'] = 'string';
        $views_field['filter']['id'] = 'string';
        $views_field['sort']['id'] = 'standard';
        break;
      default:
        $views_field['field']['id'] = 'standard';
        $views_field['argument']['id'] = 'standard';
        $views_field['filter']['id'] = 'standard';
        $views_field['sort']['id'] = 'standard';
    }

    $process_method = 'processViewsDataFor' . Container::camelize($data_type);
    if (method_exists($this, $process_method)) {
      $this->{$process_method}($table, $field_definition, $views_field);
    }

    return $views_field;
  }

  /**
   * Processes the views data for a language field.
   *
   * @param string $table
   *   The table the language field is added to.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $views_field
   *   The views field data.
   */
  protected function processViewsDataForLanguage($table, FieldDefinitionInterface $field_definition, array &$views_field) {
    // Apply special titles for the langcode field.
    if ($field_definition->getName() == 'langcode') {
      if ($table == $this->entityType->getDataTable() || $table == $this->entityType->getBaseTable()) {
        $views_field['title'] = $this->t('Translation language');
      }
      if ($table == $this->entityType->getRevisionDataTable() || $table == $this->entityType->getRevisionTable()) {
        $views_field['title'] =  $this->t('Original language');
      }
    }
  }

  /**
   * Processes the views data for an entity reference field.
   *
   * @param string $table
   *   The table the language field is added to.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $views_field
   *   The views field data.
   */
  protected function processViewsDataForEntityReference($table, FieldDefinitionInterface $field_definition, array &$views_field) {
    if ($entity_type_id = $field_definition->getItemDefinition()->getSetting('target_type')) {
      $entity_type = $this->entityManager->getDefinition($entity_type_id);
      if ($entity_type instanceof ContentEntityType) {
        $views_field['relationship'] = [
          'base' => $this->getViewsTableForEntityType($entity_type),
          'base field' => $entity_type->getKey('id'),
          'label' => $entity_type->getLabel(),
          'title' => $entity_type->getLabel(),
          'id' => 'standard',
        ];
        $views_field['field']['id'] = 'numeric';
        $views_field['argument']['id'] = 'numeric';
        $views_field['filter']['id'] = 'numeric';
        $views_field['sort']['id'] = 'standard';
      }
      else {
        $views_field['field']['id'] = 'standard';
        $views_field['argument']['id'] = 'string';
        $views_field['filter']['id'] = 'string';
        $views_field['sort']['id'] = 'standard';
      }
    }

    if ($field_definition->getName() == $this->entityType->getKey('bundle')) {
      // @todo Use the other bundle handlers, once
      //   https://www.drupal.org/node/2322949 is in.
      $views_field['filter']['id'] = 'bundle';
    }
  }

  /**
   * Gets the table of an entity type to be used as base table in views.
   *
   * @todo Given that the base_table is pretty much useless as you often have to
   *   join to the data table anyway, it could make a lot of sense to start with
   *   the data table right from the beginning.
   * @see https://drupal.org/node/2337509
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return string
   *   The name of the base table in views.
   */
  protected function getViewsTableForEntityType(EntityTypeInterface $entity_type) {
    return $entity_type->getBaseTable();
  }

}
