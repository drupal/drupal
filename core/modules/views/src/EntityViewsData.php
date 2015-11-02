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

    $base_table = $this->entityType->getBaseTable();
    $base_field = $this->entityType->getKey('id');
    $data_table = $this->entityType->getDataTable();
    $revision_table = $this->entityType->getRevisionTable();
    $revision_data_table = $this->entityType->getRevisionDataTable();
    $revision_field = $this->entityType->getKey('revision');

    // Setup base information of the views data.
    $data[$base_table]['table']['group'] = $this->entityType->getLabel();
    $data[$base_table]['table']['provider'] = $this->entityType->getProvider();

    $views_base_table = $base_table;
    if ($data_table) {
      $views_base_table = $data_table;
    }
    $data[$views_base_table]['table']['base'] = [
      'field' => $base_field,
      'title' => $this->entityType->getLabel(),
      'cache_contexts' => $this->entityType->getListCacheContexts(),
    ];
    $data[$base_table]['table']['entity revision'] = FALSE;

    if ($label_key = $this->entityType->getKey('label')) {
      if ($data_table) {
        $data[$views_base_table]['table']['base']['defaults'] = array(
          'field' => $label_key,
          'table' => $data_table,
        );
      }
      else {
        $data[$views_base_table]['table']['base']['defaults'] = array(
          'field' => $label_key,
        );
      }
    }

    // Entity types must implement a list_builder in order to use Views'
    // entity operations field.
    if ($this->entityType->hasListBuilderClass()) {
      $data[$base_table]['operations'] = array(
        'field' => array(
          'title' => $this->t('Operations links'),
          'help' => $this->t('Provides links to perform entity operations.'),
          'id' => 'entity_operations',
        ),
      );
    }

    // Setup relations to the revisions/property data.
    if ($data_table) {
      $data[$base_table]['table']['join'][$data_table] = [
        'left_field' => $base_field,
        'field' => $base_field,
        'type' => 'INNER'
      ];
      $data[$data_table]['table']['group'] = $this->entityType->getLabel();
      $data[$data_table]['table']['provider'] = $this->entityType->getProvider();
      $data[$data_table]['table']['entity revision'] = FALSE;
    }
    if ($revision_table) {
      $data[$revision_table]['table']['group'] = $this->t('@entity_type revision', ['@entity_type' => $this->entityType->getLabel()]);
      $data[$revision_table]['table']['provider'] = $this->entityType->getProvider();

      $views_revision_base_table = $revision_table;
      if ($revision_data_table) {
        $views_revision_base_table = $revision_data_table;
      }
      $data[$views_revision_base_table]['table']['entity revision'] = TRUE;
      $data[$views_revision_base_table]['table']['base'] = array(
        'field' => $revision_field,
        'title' => $this->t('@entity_type revisions', array('@entity_type' => $this->entityType->getLabel())),
      );
      // Join the revision table to the base table.
      $data[$views_revision_base_table]['table']['join'][$views_base_table] = array(
        'left_field' => $revision_field,
        'field' => $revision_field,
        'type' => 'INNER',
      );

      if ($revision_data_table) {
        $data[$revision_data_table]['table']['group'] = $this->t('@entity_type revision', ['@entity_type' => $this->entityType->getLabel()]);
        $data[$revision_data_table]['table']['entity revision'] = TRUE;

        $data[$revision_data_table]['table']['join'][$revision_table] = array(
          'left_field' => $revision_field,
          'field' => $revision_field,
          'type' => 'INNER',
        );
      }
    }

    $this->addEntityLinks($data[$base_table]);

    // Load all typed data definitions of all fields. This should cover each of
    // the entity base, revision, data tables.
    $field_definitions = $this->entityManager->getBaseFieldDefinitions($this->entityType->id());
    if ($table_mapping = $this->storage->getTableMapping()) {
      // Iterate over each table we have so far and collect field data for each.
      // Based on whether the field is in the field_definitions provided by the
      // entity manager.
      // @todo We should better just rely on information coming from the entity
      //   storage.
      // @todo https://www.drupal.org/node/2337511
      foreach ($table_mapping->getTableNames() as $table) {
        foreach ($table_mapping->getFieldNames($table) as $field_name) {
          $this->mapFieldDefinition($table, $field_name, $field_definitions[$field_name], $table_mapping, $data[$table]);
        }
      }
    }

    // Add the entity type key to each table generated.
    $entity_type_id = $this->entityType->id();
    array_walk($data, function(&$table_data) use ($entity_type_id){
      $table_data['table']['entity type'] = $entity_type_id;
    });

    return $data;
  }

  /**
   * Sets the entity links in case corresponding link templates exist.
   *
   * @param array $data
   *   The views data of the base table.
   */
  protected function addEntityLinks(array &$data) {
    $entity_type_id = $this->entityType->id();
    $t_arguments = ['@entity_type_label' => $this->entityType->getLabel()];
    if ($this->entityType->hasLinkTemplate('canonical')) {
      $data['view_' . $entity_type_id] = [
        'field' => [
          'title' => $this->t('Link to @entity_type_label', $t_arguments),
          'help' => $this->t('Provide a view link to the @entity_type_label.', $t_arguments),
          'id' => 'entity_link',
        ],
      ];
    }
    if ($this->entityType->hasLinkTemplate('edit-form')) {
      $data['edit_' . $entity_type_id] = [
        'field' => [
          'title' => $this->t('Link to edit @entity_type_label', $t_arguments),
          'help' => $this->t('Provide an edit link to the @entity_type_label.', $t_arguments),
          'id' => 'entity_link_edit',
        ],
      ];
    }
    if ($this->entityType->hasLinkTemplate('delete-form')) {
      $data['delete_' . $entity_type_id] = [
        'field' => [
          'title' => $this->t('Link to delete @entity_type_label', $t_arguments),
          'help' => $this->t('Provide a delete link to the @entity_type_label.', $t_arguments),
          'id' => 'entity_link_delete',
        ],
      ];
    }
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
    // Add all properties to views table data. We need an entry for each
    // column of each field, with the first one given special treatment.
    // @todo Introduce concept of the "main" column for a field, rather than
    //   assuming the first one is the main column. See also what the
    //   mapSingleFieldViewsData() method does with $first.
    $multiple = (count($field_column_mapping) > 1);
    $first = TRUE;
    foreach ($field_column_mapping as $field_column_name => $schema_field_name) {
      $views_field_name = ($multiple) ? $field_name . '__' . $field_column_name : $field_name;
      $table_data[$views_field_name] = $this->mapSingleFieldViewsData($table, $field_name, $field_definition_type, $field_column_name, $field_schema['columns'][$field_column_name]['type'], $first, $field_definition);

      $table_data[$views_field_name]['entity field'] = $field_name;
      $first = FALSE;
    }
  }

  /**
   * Provides the views data for a given data type and schema field.
   *
   * @param string $table
   *   The table of the field to handle.
   * @param string $field_name
   *   The machine name of the field being processed.
   * @param string $field_type
   *   The type of field being handled.
   * @param string $column_name
   *   For fields containing multiple columns, the column name being processed.
   * @param string $column_type
   *   Within the field, the column type being handled.
   * @param bool $first
   *   TRUE if this is the first column within the field.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return array
   *   The modified views data field definition.
   */
  protected function mapSingleFieldViewsData($table, $field_name, $field_type, $column_name, $column_type, $first, FieldDefinitionInterface $field_definition) {
    $views_field = array();

    // Provide a nicer, less verbose label for the first column within a field.
    // @todo Introduce concept of the "main" column for a field, rather than
    //   assuming the first one is the main column.
    if ($first) {
      $views_field['title'] = $field_definition->getLabel();
    }
    else {
      $views_field['title'] = $field_definition->getLabel() . " ($column_name)";
    }

    if ($description = $field_definition->getDescription()) {
      $views_field['help'] = $description;
    }

    // Set up the field, sort, argument, and filters, based on
    // the column and/or field data type.
    // @todo Allow field types to customize this.
    // @see https://www.drupal.org/node/2337515
    switch ($field_type) {
      // Special case a few field types.
      case 'timestamp':
      case 'created':
      case 'changed':
        $views_field['field']['id'] = 'field';
        $views_field['argument']['id'] = 'date';
        $views_field['filter']['id'] = 'date';
        $views_field['sort']['id'] = 'date';
        break;

      case 'language':
        $views_field['field']['id'] = 'field';
        $views_field['argument']['id'] = 'language';
        $views_field['filter']['id'] = 'language';
        $views_field['sort']['id'] = 'standard';
        break;

      case 'boolean':
        $views_field['field']['id'] = 'field';
        $views_field['argument']['id'] = 'numeric';
        $views_field['filter']['id'] = 'boolean';
        $views_field['sort']['id'] = 'standard';
        break;

      case 'uri':
        // Let's render URIs as URIs by default, not links.
        $views_field['field']['id'] = 'field';
        $views_field['field']['default_formatter'] = 'string';

        $views_field['argument']['id'] = 'string';
        $views_field['filter']['id'] = 'string';
        $views_field['sort']['id'] = 'standard';
        break;

      case 'text':
      case 'text_with_summary':
        // Treat these three long text fields the same.
        $field_type = 'text_long';
        // Intentional fall-through here to the default processing!

      default:
        // For most fields, the field type is generic enough to just use
        // the column type to determine the filters etc.
        switch ($column_type) {

          case 'int':
          case 'integer':
          case 'smallint':
          case 'tinyint':
          case 'mediumint':
          case 'float':
          case 'double':
          case 'decimal':
            $views_field['field']['id'] = 'field';
            $views_field['argument']['id'] = 'numeric';
            $views_field['filter']['id'] = 'numeric';
            $views_field['sort']['id'] = 'standard';
            break;

          case 'char':
          case 'string':
          case 'varchar':
          case 'varchar_ascii':
          case 'tinytext':
          case 'text':
          case 'mediumtext':
          case 'longtext':
            $views_field['field']['id'] = 'field';
            $views_field['argument']['id'] = 'string';
            $views_field['filter']['id'] = 'string';
            $views_field['sort']['id'] = 'standard';
            break;

          default:
            $views_field['field']['id'] = 'field';
            $views_field['argument']['id'] = 'standard';
            $views_field['filter']['id'] = 'standard';
            $views_field['sort']['id'] = 'standard';
        }
    }

    // Do post-processing for a few field types.

    $process_method = 'processViewsDataFor' . Container::camelize($field_type);
    if (method_exists($this, $process_method)) {
      $this->{$process_method}($table, $field_definition, $views_field, $column_name);
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
   * @param string $field_column_name
   *   The field column being processed.
   */
  protected function processViewsDataForLanguage($table, FieldDefinitionInterface $field_definition, array &$views_field, $field_column_name) {
    // Apply special titles for the langcode field.
    if ($field_definition->getName() == $this->entityType->getKey('langcode')) {
      if ($table == $this->entityType->getDataTable() || $table == $this->entityType->getRevisionDataTable()) {
        $views_field['title'] = $this->t('Translation language');
      }
      if ($table == $this->entityType->getBaseTable() || $table == $this->entityType->getRevisionTable()) {
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
   * @param string $field_column_name
   *   The field column being processed.
   */
  protected function processViewsDataForEntityReference($table, FieldDefinitionInterface $field_definition, array &$views_field, $field_column_name) {

    // @todo Should the actual field handler respect that this just renders a
    //   number?
    // @todo Create an optional entity field handler, that can render the
    //   entity.
    // @see https://www.drupal.org/node/2322949

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
        $views_field['field']['id'] = 'field';
        $views_field['argument']['id'] = 'numeric';
        $views_field['filter']['id'] = 'numeric';
        $views_field['sort']['id'] = 'standard';
      }
      else {
        $views_field['field']['id'] = 'field';
        $views_field['argument']['id'] = 'string';
        $views_field['filter']['id'] = 'string';
        $views_field['sort']['id'] = 'standard';
      }
    }

    if ($field_definition->getName() == $this->entityType->getKey('bundle')) {
      $views_field['filter']['id'] = 'bundle';
    }
  }

  /**
   * Processes the views data for a text field with formatting.
   *
   * @param string $table
   *   The table the field is added to.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $views_field
   *   The views field data.
   * @param string $field_column_name
   *   The field column being processed.
   */
  protected function processViewsDataForTextLong($table, FieldDefinitionInterface $field_definition, array &$views_field, $field_column_name) {
    // Connect the text field to its formatter.
    if ($field_column_name == 'value') {
      $views_field['field']['format'] = $field_definition->getName() . '__format';
      $views_field['field']['id'] = 'field';
    }
  }

  /**
   * Processes the views data for a UUID field.
   *
   * @param string $table
   *   The table the field is added to.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $views_field
   *   The views field data.
   * @param string $field_column_name
   *   The field column being processed.
   */
  protected function processViewsDataForUuid($table, FieldDefinitionInterface $field_definition, array &$views_field, $field_column_name) {
    // It does not make sense for UUID fields to be click sortable.
    $views_field['field']['click sortable'] = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewsTableForEntityType(EntityTypeInterface $entity_type) {
    return $entity_type->getDataTable() ?: $entity_type->getBaseTable();
  }

}
