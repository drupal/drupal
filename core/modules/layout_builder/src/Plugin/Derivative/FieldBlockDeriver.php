<?php

namespace Drupal\layout_builder\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides entity field block definitions for every field.
 *
 * @internal
 */
class FieldBlockDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

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
   */
  public function __construct(EntityTypeRepositoryInterface $entity_type_repository, EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $field_type_manager, FormatterPluginManager $formatter_manager) {
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
      $container->get('plugin.manager.field.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $entity_type_labels = $this->entityTypeRepository->getEntityTypeLabels();
    foreach ($this->entityFieldManager->getFieldMap() as $entity_type_id => $entity_field_map) {
      foreach ($entity_field_map as $field_name => $field_info) {
        // Skip fields without any formatters.
        $options = $this->formatterManager->getOptions($field_info['type']);
        if (empty($options)) {
          continue;
        }

        foreach ($field_info['bundles'] as $bundle) {
          $derivative = $base_plugin_definition;
          $field_definition = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle)[$field_name];

          // Store the default formatter on the definition.
          $derivative['default_formatter'] = '';
          $field_type_definition = $this->fieldTypeManager->getDefinition($field_info['type']);
          if (isset($field_type_definition['default_formatter'])) {
            $derivative['default_formatter'] = $field_type_definition['default_formatter'];
          }

          $derivative['category'] = $this->t('@entity', ['@entity' => $entity_type_labels[$entity_type_id]]);

          $derivative['admin_label'] = $field_definition->getLabel();

          // Add a dependency on the field if it is configurable.
          if ($field_definition instanceof FieldConfigInterface) {
            $derivative['config_dependencies'][$field_definition->getConfigDependencyKey()][] = $field_definition->getConfigDependencyName();
          }
          // For any field that is not display configurable, mark it as
          // unavailable to place in the block UI.
          $derivative['_block_ui_hidden'] = !$field_definition->isDisplayConfigurable('view');

          // @todo Use EntityContextDefinition after resolving
          //   https://www.drupal.org/node/2932462.
          $context_definition = new ContextDefinition('entity:' . $entity_type_id, $entity_type_labels[$entity_type_id], TRUE);
          $context_definition->addConstraint('Bundle', [$bundle]);
          $derivative['context'] = [
            'entity' => $context_definition,
          ];

          $derivative_id = $entity_type_id . PluginBase::DERIVATIVE_SEPARATOR . $bundle . PluginBase::DERIVATIVE_SEPARATOR . $field_name;
          $this->derivatives[$derivative_id] = $derivative;
        }
      }
    }
    return $this->derivatives;
  }

}
