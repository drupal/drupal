<?php

namespace Drupal\field_ui;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\field\FieldConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides lists of field config entities.
 */
class FieldConfigListBuilder extends ConfigEntityListBuilder {

  /**
   * The name of the entity type the listed fields are attached to.
   *
   * @var string
   */
  protected $targetEntityTypeId;

  /**
   * The name of the bundle the listed fields are attached to.
   *
   * @var string
   */
  protected $targetBundle;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface|null $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager, FieldTypePluginManagerInterface $field_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($entity_type, $entity_type_manager->getStorage($entity_type->id()));

    $this->entityTypeManager = $entity_type_manager;
    $this->fieldTypeManager = $field_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render($target_entity_type_id = NULL, $target_bundle = NULL) {
    $this->targetEntityTypeId = $target_entity_type_id;
    $this->targetBundle = $target_bundle;

    $build = parent::render();
    $build['table']['#attributes']['id'] = 'field-overview';
    $build['table']['#empty'] = $this->t('No fields are present yet.');
    $build['#attached']['library'][] = 'field_ui/drupal.field_ui';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = array_filter($this->entityFieldManager->getFieldDefinitions($this->targetEntityTypeId, $this->targetBundle), function ($field_definition) {
      return $field_definition instanceof FieldConfigInterface;
    });

    // Sort the entities using the entity class's sort() method.
    // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
    uasort($entities, [$this->entityType->getClass(), 'sort']);
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'label' => $this->t('Label'),
      'field_name' => [
        'data' => $this->t('Machine name'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'settings_summary' => $this->t('Field type'),
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $field_config) {
    /** @var \Drupal\field\FieldConfigInterface $field_config */
    $field_storage = $field_config->getFieldStorageDefinition();

    $storage_summary = $this->fieldTypeManager->getStorageSettingsSummary($field_storage);
    $instance_summary = $this->fieldTypeManager->getFieldSettingsSummary($field_config);
    $summary_list = [...$storage_summary, ...$instance_summary];

    $settings_summary = [
      'data' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->fieldTypeManager->getDefinitions()[$field_storage->getType()]['label'],
          ...$summary_list,
        ],
      ],
      'class' => ['field-settings-summary-cell'],
    ];

    $row = [
      'id' => Html::getClass($field_config->getName()),
      'data' => [
        'label' => $field_config->getLabel(),
        'field_name' => $field_config->getName(),
        'settings_summary' => $settings_summary,
      ],
    ];

    // Add the operations.
    $row['data'] = $row['data'] + parent::buildRow($field_config);

    if ($field_storage->isLocked()) {
      $row['data']['operations'] = ['data' => ['#markup' => $this->t('Locked')]];
      $row['class'][] = 'menu-disabled';
    }

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\field\FieldConfigInterface $entity */
    $operations = parent::getDefaultOperations($entity);

    if ($entity->access('update') && $entity->hasLinkTemplate("{$entity->getTargetEntityTypeId()}-field-edit-form")) {
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'weight' => 10,
        'url' => $entity->toUrl("{$entity->getTargetEntityTypeId()}-field-edit-form"),
        'attributes' => [
          'title' => $this->t('Edit field settings.'),
        ],
      ];
    }
    if ($entity->access('delete') && $entity->hasLinkTemplate("{$entity->getTargetEntityTypeId()}-field-delete-form")) {
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'weight' => 100,
        'url' => $entity->toUrl("{$entity->getTargetEntityTypeId()}-field-delete-form"),
        'attributes' => [
          'title' => $this->t('Delete field.'),
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 880,
          ]),
        ],
      ];
    }

    return $operations;
  }

}
