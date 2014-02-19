<?php

/**
 * @file
 * Contains \Drupal\field_ui\FieldConfigListController.
 */

namespace Drupal\field_ui;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of fields.
 */
class FieldConfigListController extends ConfigEntityListController {

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
   * Constructs a new FieldConfigListController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The 'field type' plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, FieldTypePluginManagerInterface $field_type_manager) {
    parent::__construct($entity_type, $entity_manager->getStorageController($entity_type->id()));

    $this->entityManager = $entity_manager;
    $this->bundles = entity_get_bundles();
    $this->fieldTypeManager = $field_type_manager;
    $this->fieldTypes = $this->fieldTypeManager->getConfigurableDefinitions();
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
  public function buildRow(EntityInterface $field) {
    if ($field->locked) {
      $row['class'] = array('menu-disabled');
      $row['data']['id'] =  t('@field_name (Locked)', array('@field_name' => $field->name));
    }
    else {
      $row['data']['id'] = $field->name;
    }

    $field_type = $this->fieldTypes[$field->type];
    $row['data']['type'] = t('@type (module: @module)', array('@type' => $field_type['label'], '@module' => $field_type['provider']));

    $usage = array();
    foreach ($field->getBundles() as $bundle) {
      if ($route_info = FieldUI::getOverviewRouteInfo($field->entity_type, $bundle)) {
        $usage[] = \Drupal::l($this->bundles[$field->entity_type][$bundle]['label'], $route_info['route_name'], $route_info['route_parameters'], $route_info['options']);
      }
      else {
        $usage[] = $this->bundles[$field->entity_type][$bundle]['label'];
      }
    }
    $row['data']['usage'] = implode(', ', $usage);
    return $row;
  }

}
