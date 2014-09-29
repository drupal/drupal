<?php

/**
 * @file
 * Contains \Drupal\field_ui\FieldStorageConfigListBuilder.
 */

namespace Drupal\field_ui;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of fields.
 *
 * @see \Drupal\field\Entity\Field
 * @see field_ui_entity_info()
 */
class FieldStorageConfigListBuilder extends ConfigEntityListBuilder {

  /**
   * An array of information about field types.
   *
   * @var array
   */
  protected $fieldTypes;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

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
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The 'field type' plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, FieldTypePluginManagerInterface $field_type_manager) {
    parent::__construct($entity_type, $entity_manager->getStorage($entity_type->id()));

    $this->entityManager = $entity_manager;
    $this->bundles = entity_get_bundles();
    $this->fieldTypeManager = $field_type_manager;
    $this->fieldTypes = $this->fieldTypeManager->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('plugin.manager.field.field_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = t('Field name');
    $header['type'] = array(
      'data' => t('Field type'),
      'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
    );
    $header['usage'] = t('Used in');
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $field_storage) {
    if ($field_storage->locked) {
      $row['class'] = array('menu-disabled');
      $row['data']['id'] =  t('@field_name (Locked)', array('@field_name' => $field_storage->field_name));
    }
    else {
      $row['data']['id'] = $field_storage->field_name;
    }

    $field_type = $this->fieldTypes[$field_storage->type];
    $row['data']['type'] = t('@type (module: @module)', array('@type' => $field_type['label'], '@module' => $field_type['provider']));

    $usage = array();
    foreach ($field_storage->getBundles() as $bundle) {
      if ($route_info = FieldUI::getOverviewRouteInfo($field_storage->entity_type, $bundle)) {
        $usage[] = \Drupal::l($this->bundles[$field_storage->entity_type][$bundle]['label'], $route_info);
      }
      else {
        $usage[] = $this->bundles[$field_storage->entity_type][$bundle]['label'];
      }
    }
    $usage_escaped = '';
    $separator = '';
    foreach ($usage as $usage_item) {
      $usage_escaped .=  $separator . SafeMarkup::escape($usage_item);
      $separator = ', ';
    }
    $row['data']['usage'] = SafeMarkup::set($usage_escaped);
    return $row;
  }

}
