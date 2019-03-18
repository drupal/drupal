<?php

namespace Drupal\field_ui;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of fields.
 *
 * @see \Drupal\field\Entity\Field
 * @see field_ui_entity_info()
 */
class FieldStorageConfigListBuilder extends ConfigEntityListBuilder {
  use DeprecatedServicePropertyTrait;

  /**
   * {@inheritdoc}
   */
  protected $deprecatedProperties = [
    'entityManager' => 'entity.manager',
  ];

  /**
   * An array of information about field types.
   *
   * @var array
   */
  protected $fieldTypes;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * An array of entity bundle information.
   *
   * @var array
   */
  protected $bundles;

  /**
   * The field type manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * Constructs a new FieldStorageConfigListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The 'field type' plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager, FieldTypePluginManagerInterface $field_type_manager, EntityTypeBundleInfoInterface $bundle_info_service) {
    parent::__construct($entity_type, $entity_type_manager->getStorage($entity_type->id()));

    $this->entityTypeManager = $entity_type_manager;
    $this->bundles = $bundle_info_service->getAllBundleInfo();
    $this->fieldTypeManager = $field_type_manager;
    $this->fieldTypes = $this->fieldTypeManager->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Field name');
    $header['entity_type'] = $this->t('Entity type');
    $header['type'] = [
      'data' => $this->t('Field type'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    $header['usage'] = $this->t('Used in');
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $field_storage) {
    if ($field_storage->isLocked()) {
      $row['class'] = ['menu-disabled'];
      $row['data']['id'] = $this->t('@field_name (Locked)', ['@field_name' => $field_storage->getName()]);
    }
    else {
      $row['data']['id'] = $field_storage->getName();
    }

    $entity_type_id = $field_storage->getTargetEntityTypeId();
    // Adding the entity type.
    $row['data']['entity_type'] = $entity_type_id;

    $field_type = $this->fieldTypes[$field_storage->getType()];
    $row['data']['type'] = $this->t('@type (module: @module)', ['@type' => $field_type['label'], '@module' => $field_type['provider']]);

    $usage = [];
    foreach ($field_storage->getBundles() as $bundle) {
      if ($route_info = FieldUI::getOverviewRouteInfo($entity_type_id, $bundle)) {
        $usage[] = \Drupal::l($this->bundles[$entity_type_id][$bundle]['label'], $route_info);
      }
      else {
        $usage[] = $this->bundles[$entity_type_id][$bundle]['label'];
      }
    }
    $row['data']['usage']['data'] = [
      '#theme' => 'item_list',
      '#items' => $usage,
      '#context' => ['list_style' => 'comma-list'],
    ];
    return $row;
  }

}
