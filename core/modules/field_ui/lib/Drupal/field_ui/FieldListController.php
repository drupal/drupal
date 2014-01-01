<?php

/**
 * @file
 * Contains \Drupal\field_ui\FieldListController.
 */

namespace Drupal\field_ui;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldTypePluginManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
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
   * @var \Drupal\Core\Field\FieldTypePluginManager
   */
  protected $fieldTypeManager;

  /**
   * Constructs a new EntityListController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_info
   *   The entity info for the entity type.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke hooks on.
   * @param \Drupal\Core\Field\FieldTypePluginManager $field_type_manager
   *   The 'field type' plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_info, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, FieldTypePluginManager $field_type_manager) {
    parent::__construct($entity_info, $entity_manager->getStorageController($entity_info->id()), $module_handler);

    $this->entityManager = $entity_manager;
    $this->bundles = entity_get_bundles();
    $this->fieldTypeManager = $field_type_manager;
    $this->fieldTypes = $this->fieldTypeManager->getConfigurableDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_info) {
    return new static(
      $entity_info,
      $container->get('entity.manager'),
      $container->get('module_handler'),
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
      $admin_path = $this->entityManager->getAdminPath($field->entity_type, $bundle);
      $usage[] = $admin_path ? l($this->bundles[$field->entity_type][$bundle]['label'], $admin_path . '/fields') : $this->bundles[$field->entity_type][$bundle]['label'];
    }
    $row['data']['usage'] = implode(', ', $usage);
    return $row;
  }

}
