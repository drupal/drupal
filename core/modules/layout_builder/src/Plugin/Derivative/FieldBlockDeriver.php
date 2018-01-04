<?php

namespace Drupal\layout_builder\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
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
      foreach ($this->entityFieldManager->getFieldStorageDefinitions($entity_type_id) as $field_storage_definition) {
        $derivative = $base_plugin_definition;
        $field_name = $field_storage_definition->getName();

        // The blocks are based on fields. However, we are looping through field
        // storages for which no fields may exist. If that is the case, skip
        // this field storage.
        if (!isset($entity_field_map[$field_name])) {
          continue;
        }
        $field_info = $entity_field_map[$field_name];

        // Skip fields without any formatters.
        $options = $this->formatterManager->getOptions($field_storage_definition->getType());
        if (empty($options)) {
          continue;
        }

        // Store the default formatter on the definition.
        $derivative['default_formatter'] = '';
        $field_type_definition = $this->fieldTypeManager->getDefinition($field_storage_definition->getType());
        if (isset($field_type_definition['default_formatter'])) {
          $derivative['default_formatter'] = $field_type_definition['default_formatter'];
        }

        // Get the admin label for both base and configurable fields.
        if ($field_storage_definition->isBaseField()) {
          $admin_label = $field_storage_definition->getLabel();
        }
        else {
          // We take the field label used on the first bundle.
          $first_bundle = reset($field_info['bundles']);
          $bundle_field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $first_bundle);

          // The field storage config may exist, but it's possible that no
          // fields are actually using it. If that's the case, skip to the next
          // field.
          if (empty($bundle_field_definitions[$field_name])) {
            continue;
          }
          $admin_label = $bundle_field_definitions[$field_name]->getLabel();
        }

        // Set plugin definition for derivative.
        $derivative['category'] = $this->t('@entity', ['@entity' => $entity_type_labels[$entity_type_id]]);
        $derivative['admin_label'] = $admin_label;
        $bundles = array_keys($field_info['bundles']);

        // For any field that is not display configurable, mark it as
        // unavailable to place in the block UI.
        $block_ui_hidden = TRUE;
        foreach ($bundles as $bundle) {
          $field_definition = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle)[$field_name];
          if ($field_definition->isDisplayConfigurable('view')) {
            $block_ui_hidden = FALSE;
            break;
          }
        }
        $derivative['_block_ui_hidden'] = $block_ui_hidden;
        $derivative['bundles'] = $bundles;
        $context_definition = new ContextDefinition('entity:' . $entity_type_id, $entity_type_labels[$entity_type_id], TRUE);
        // Limit available blocks by bundles to which the field is attached.
        // @todo To workaround https://www.drupal.org/node/2671964 this only
        //   adds a bundle constraint if the entity type has bundles. When an
        //   entity type has no bundles, the entity type ID itself is used.
        if (count($bundles) > 1 || !isset($field_info['bundles'][$entity_type_id])) {
          $context_definition->addConstraint('Bundle', $bundles);
        }
        $derivative['context'] = [
          'entity' => $context_definition,
        ];

        $derivative_id = $entity_type_id . PluginBase::DERIVATIVE_SEPARATOR . $field_name;
        $this->derivatives[$derivative_id] = $derivative;
      }
    }
    return $this->derivatives;
  }

}
