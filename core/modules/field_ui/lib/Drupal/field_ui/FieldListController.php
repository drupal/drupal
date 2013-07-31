<?php

/**
 * @file
 * Contains \Drupal\field_ui\FieldListController.
 */

namespace Drupal\field_ui;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\field\FieldInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of fields.
 */
class FieldListController extends ConfigEntityListController {

  /**
   * An array of information about field types.
   *
   * @var array
   */
  protected $fieldTypes;

  /**
   * An array of field data.
   *
   * @var array
   */
  protected $fieldInfo;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * An array of entity bundle information.
   *
   * @var array
   */
  protected $bundles;

  /**
   * Constructs a new EntityListController object.
   *
   * @param string $entity_type
   *   The type of entity to be listed.
   * @param array $entity_info
   *   An array of entity info for the entity type.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke hooks on.
   * @param \Drupal\field\FieldInfo $field_info
   *   The field info service.
   */
  public function __construct($entity_type, array $entity_info, EntityManager $entity_manager, ModuleHandlerInterface $module_handler, FieldInfo $field_info) {
    parent::__construct($entity_type, $entity_info, $entity_manager->getStorageController($entity_type), $module_handler);

    $this->fieldTypes = field_info_field_types();
    $this->fieldInfo = $field_info->getFieldMap();
    $this->entityManager = $entity_manager;
    $this->bundles = entity_get_bundles();
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $entity_type,
      $entity_info,
      $container->get('plugin.manager.entity'),
      $container->get('module_handler'),
      $container->get('field.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $row['id'] = t('Field name');
    $row['type'] = array(
      'data' => t('Field type'),
      'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
    );
    $row['usage'] = t('Used in');
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    if ($entity->locked) {
      $row['class'] = array('menu-disabled');
      $row['data']['id'] =  t('@field_name (Locked)', array('@field_name' => $entity->id()));
    }
    else {
      $row['data']['id'] = $entity->id();
    }

    $field_type = $this->fieldTypes[$entity->getFieldType()];
    $row['data']['type'] = t('@type (module: @module)', array('@type' => $field_type['label'], '@module' => $field_type['provider']));

    $usage = array();
    foreach($this->fieldInfo[$entity->id()]['bundles'] as $entity_type => $field_bundles) {
      foreach($field_bundles as $bundle) {
        $admin_path = $this->entityManager->getAdminPath($entity_type, $bundle);
        $usage[] = $admin_path ? l($this->bundles[$entity_type][$bundle]['label'], $admin_path . '/fields') : $this->bundles[$entity_type][$bundle]['label'];
      }
    }
    $row['data']['usage'] = implode(', ', $usage);
    return $row;
  }

}
