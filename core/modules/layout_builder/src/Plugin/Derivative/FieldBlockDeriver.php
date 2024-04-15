<?php

namespace Drupal\layout_builder\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides entity field block definitions for every field.
 *
 * @internal
 *   Plugin derivers are internal.
 */
class FieldBlockDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;
  use LoggerChannelTrait;

  /**
   * The entity type repository.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The field type manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * The formatter manager.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager
   */
  protected $formatterManager;

  /**
   * Constructs new FieldBlockDeriver.
   *
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repository
   *   The entity type repository.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type manager.
   * @param \Drupal\Core\Field\FormatterPluginManager $formatter_manager
   *   The formatter manager.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $entityViewDisplayStorage
   *   The entity view display storage.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    EntityTypeRepositoryInterface $entity_type_repository,
    EntityFieldManagerInterface $entity_field_manager,
    FieldTypePluginManagerInterface $field_type_manager,
    FormatterPluginManager $formatter_manager,
    protected ConfigEntityStorageInterface $entityViewDisplayStorage,
    protected ModuleHandlerInterface $moduleHandler,
  ) {
    $this->entityTypeRepository = $entity_type_repository;
    $this->entityFieldManager = $entity_field_manager;
    $this->fieldTypeManager = $field_type_manager;
    $this->formatterManager = $formatter_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.repository'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('plugin.manager.field.formatter'),
      $container->get('entity_type.manager')->getStorage('entity_view_display'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $entity_type_labels = $this->entityTypeRepository->getEntityTypeLabels();
    foreach ($this->getFieldMap() as $entity_type_id => $entity_field_map) {
      foreach ($entity_field_map as $field_name => $field_info) {
        // Skip fields without any formatters.
        $options = $this->formatterManager->getOptions($field_info['type']);
        if (empty($options)) {
          continue;
        }

        foreach ($field_info['bundles'] as $bundle) {
          $derivative = $base_plugin_definition;
          $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
          if (empty($field_definitions[$field_name])) {
            $this->getLogger('field')->error('Field %field_name exists but is missing a corresponding field definition and may be misconfigured.', ['%field_name' => "$entity_type_id.$bundle.$field_name"]);
            continue;
          }
          $field_definition = $field_definitions[$field_name];

          // Store the default formatter on the definition.
          $derivative['default_formatter'] = '';
          $field_type_definition = $this->fieldTypeManager->getDefinition($field_info['type']);
          if (isset($field_type_definition['default_formatter'])) {
            $derivative['default_formatter'] = $field_type_definition['default_formatter'];
          }

          $derivative['category'] = $this->t('@entity fields', ['@entity' => $entity_type_labels[$entity_type_id]]);

          $derivative['admin_label'] = $field_definition->getLabel();

          // Add a dependency on the field if it is configurable.
          if ($field_definition instanceof FieldConfigInterface) {
            $derivative['config_dependencies'][$field_definition->getConfigDependencyKey()][] = $field_definition->getConfigDependencyName();
          }
          // For any field that is not display configurable, mark it as
          // unavailable to place in the block UI.
          $derivative['_block_ui_hidden'] = !$field_definition->isDisplayConfigurable('view');

          $context_definition = EntityContextDefinition::fromEntityTypeId($entity_type_id)->setLabel($entity_type_labels[$entity_type_id]);
          $context_definition->addConstraint('Bundle', [$bundle]);
          $derivative['context_definitions'] = [
            'entity' => $context_definition,
            'view_mode' => new ContextDefinition('string'),
          ];

          $derivative_id = $entity_type_id . PluginBase::DERIVATIVE_SEPARATOR . $bundle . PluginBase::DERIVATIVE_SEPARATOR . $field_name;
          $this->derivatives[$derivative_id] = $derivative;
        }
      }
    }
    return $this->derivatives;
  }

  /**
   * Returns the entity field map for deriving block definitions.
   *
   * @return array
   *   The entity field map.
   *
   * @see \Drupal\Core\Entity\EntityFieldManagerInterface::getFieldMap()
   */
  protected function getFieldMap(): array {
    $field_map = $this->entityFieldManager->getFieldMap();

    // If all fields are exposed as field blocks, just return the field map
    // without any further processing.
    if ($this->moduleHandler->moduleExists('layout_builder_expose_all_field_blocks')) {
      return $field_map;
    }

    // Load all entity view displays which are using Layout Builder.
    /** @var \Drupal\layout_builder\Entity\LayoutEntityDisplayInterface[] $displays */
    $displays = $this->entityViewDisplayStorage->loadByProperties([
      'third_party_settings.layout_builder.enabled' => TRUE,
    ]);
    $layout_bundles = [];
    foreach ($displays as $display) {
      $bundle = $display->getTargetBundle();
      $layout_bundles[$display->getTargetEntityTypeId()][$bundle] = $bundle;
    }

    // Process $field_map, removing any entity types which are not using Layout
    // Builder.
    $field_map = array_intersect_key($field_map, $layout_bundles);

    foreach ($field_map as $entity_type_id => $fields) {
      foreach ($fields as $field_name => $field_info) {
        $field_map[$entity_type_id][$field_name]['bundles'] = array_intersect($field_info['bundles'], $layout_bundles[$entity_type_id]);

        // If no bundles are using Layout Builder, remove this field from the
        // field map.
        if (empty($field_info['bundles'])) {
          unset($field_map[$entity_type_id][$field_name]);
        }
      }
    }
    return $field_map;
  }

}
