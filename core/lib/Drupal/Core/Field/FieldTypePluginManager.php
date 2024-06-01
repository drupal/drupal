<?php

namespace Drupal\Core\Field;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\TypedData\TypedDataManagerInterface;

/**
 * Plugin manager for 'field type' plugins.
 *
 * @ingroup field_types
 */
class FieldTypePluginManager extends DefaultPluginManager implements FieldTypePluginManagerInterface {

  use CategorizingPluginManagerTrait {
    getGroupedDefinitions as protected getGroupedDefinitionsTrait;
  }

  /**
   * Constructs the FieldTypePluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typedDataManager
   *   The typed data manager.
   * @param \Drupal\Core\Field\FieldTypeCategoryManagerInterface $fieldTypeCategoryManager
   *   The field type category plugin manager.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    protected ModuleHandlerInterface $module_handler,
    protected TypedDataManagerInterface $typedDataManager,
    protected FieldTypeCategoryManagerInterface $fieldTypeCategoryManager,
  ) {
    parent::__construct(
      'Plugin/Field/FieldType',
      $namespaces,
      $module_handler,
      FieldItemInterface::class,
      FieldType::class,
      'Drupal\Core\Field\Annotation\FieldType',
    );

    $this->alterInfo('field_info');
    $this->setCacheBackend($cache_backend, 'field_types_plugins');
  }

  /**
   * {@inheritdoc}
   *
   * Creates a field item, which is not part of an entity or field item list.
   *
   * @param string $field_type
   *   The field type, for which a field item should be created.
   * @param array $configuration
   *   The plugin configuration array, i.e. an array with the following keys:
   *   - field_definition: The field definition object, i.e. an instance of
   *     Drupal\Core\Field\FieldDefinitionInterface.
   *
   * @return \Drupal\Core\Field\FieldItemInterface
   *   The instantiated object.
   */
  public function createInstance($field_type, array $configuration = []) {
    $configuration['data_definition'] = $configuration['field_definition']->getItemDefinition();
    return $this->typedDataManager->createInstance("field_item:$field_type", $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function createFieldItemList(FieldableEntityInterface $entity, $field_name, $values = NULL) {
    // Leverage prototyping of the Typed Data API for fast instantiation.
    return $this->typedDataManager->getPropertyInstance($entity->getTypedData(), $field_name, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function createFieldItem(FieldItemListInterface $items, $index, $values = NULL) {
    // Leverage prototyping of the Typed Data API for fast instantiation.
    return $this->typedDataManager->getPropertyInstance($items, $index, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    if (!isset($definition['list_class'])) {
      $definition['list_class'] = '\Drupal\Core\Field\FieldItemList';
    }

    if (empty($definition['category'])) {
      $definition['category'] = FieldTypeCategoryManagerInterface::FALLBACK_CATEGORY;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultStorageSettings($type) {
    $plugin_definition = $this->getDefinition($type, FALSE);
    if (!empty($plugin_definition['class'])) {
      $plugin_class = DefaultFactory::getPluginClass($type, $plugin_definition);
      return $plugin_class::defaultStorageSettings();
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFieldSettings($type) {
    $plugin_definition = $this->getDefinition($type, FALSE);
    if (!empty($plugin_definition['class'])) {
      $plugin_class = DefaultFactory::getPluginClass($type, $plugin_definition);
      return $plugin_class::defaultFieldSettings();
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageSettingsSummary(FieldStorageDefinitionInterface $storage_definition): array {
    $plugin_definition = $this->getDefinition($storage_definition->getType(), FALSE);
    if (!empty($plugin_definition['class'])) {
      $plugin_class = DefaultFactory::getPluginClass($storage_definition->getType(), $plugin_definition);
      return $plugin_class::storageSettingsSummary($storage_definition);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldSettingsSummary(FieldDefinitionInterface $field_definition): array {
    $plugin_definition = $this->getDefinition($field_definition->getType(), FALSE);
    if (!empty($plugin_definition['class'])) {
      $plugin_class = DefaultFactory::getPluginClass($field_definition->getType(), $plugin_definition);
      return $plugin_class::fieldSettingsSummary($field_definition);
    }
    return [];
  }

  /**
   * Gets sorted field type definitions grouped by category.
   *
   * In addition to grouping, both categories and its entries are sorted,
   * whereas plugin definitions are sorted by label.
   *
   * @param array[]|null $definitions
   *   (optional) The plugin definitions to group. If omitted, all plugin
   *   definitions are used.
   * @param string $label_key
   *   (optional) The array key to use as the label of the field type.
   * @param string $category_label_key
   *   (optional) The array key to use as the label of the category.
   *
   * @return array[]
   *   Keys are category names, and values are arrays of which the keys are
   *   plugin IDs and the values are plugin definitions.
   */
  public function getGroupedDefinitions(?array $definitions = NULL, $label_key = 'label', $category_label_key = 'label') {
    $grouped_categories = $this->getGroupedDefinitionsTrait($definitions, $label_key);
    $category_info = $this->fieldTypeCategoryManager->getDefinitions();

    // Ensure that all the referenced categories exist.
    foreach ($grouped_categories as $group => $definitions) {
      if (!isset($category_info[$group])) {
        assert(FALSE, "\"$group\" must be defined in MODULE_NAME.field_type_categories.yml");
        if (!isset($grouped_categories[FieldTypeCategoryManagerInterface::FALLBACK_CATEGORY])) {
          $grouped_categories[FieldTypeCategoryManagerInterface::FALLBACK_CATEGORY] = [];
        }
        $grouped_categories[FieldTypeCategoryManagerInterface::FALLBACK_CATEGORY] += $definitions;
        unset($grouped_categories[$group]);
      }
    }

    $normalized_grouped_categories = [];
    foreach ($grouped_categories as $group => $definitions) {
      $normalized_grouped_categories[(string) $category_info[$group][$category_label_key]] = $definitions;
    }

    return $normalized_grouped_categories;
  }

  /**
   * {@inheritdoc}
   */
  public function getUiDefinitions() {
    $definitions = $this->getDefinitions();

    // Filter out definitions that can not be configured in Field UI.
    $definitions = array_filter($definitions, function ($definition) {
      return empty($definition['no_ui']);
    });

    // Add preconfigured definitions.
    foreach ($definitions as $id => $definition) {
      if (is_subclass_of($definition['class'], '\Drupal\Core\Field\PreconfiguredFieldUiOptionsInterface')) {
        foreach ($this->getPreconfiguredOptions($definition['id']) as $key => $option) {
          $definitions["field_ui:$id:$key"] = array_intersect_key(
            $option,
            ['label' => 0, 'category' => 1, 'weight' => 1, 'description' => 0]
          ) + $definition;
        }
      }
    }

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeUiDefinitions(string $entity_type_id): array {
    $ui_definitions = $this->getUiDefinitions();
    $this->moduleHandler->alter('field_info_entity_type_ui_definitions', $ui_definitions, $entity_type_id);
    return $ui_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreconfiguredOptions($field_type) {
    $options = [];
    $class = $this->getPluginClass($field_type);
    if (is_subclass_of($class, '\Drupal\Core\Field\PreconfiguredFieldUiOptionsInterface')) {
      $options = $class::getPreconfiguredOptions();
      $this->moduleHandler->alter('field_ui_preconfigured_options', $options, $field_type);
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginClass($type) {
    return $this->getDefinition($type)['class'];
  }

}
