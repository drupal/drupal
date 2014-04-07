<?php

/**
 * @file
 * Contains \Drupal\field_ui\FieldInstanceConfigListBuilder.
 */

namespace Drupal\field_ui;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides lists of field instance config entities.
 */
class FieldInstanceConfigListBuilder extends ConfigEntityListBuilder {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager) {
    parent::__construct($entity_type, $entity_manager->getStorage($entity_type->id()));
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static($entity_type, $container->get('entity.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    // The actual field instance config overview is rendered by
    // \Drupal\field_ui\FieldOverview, so we should not use this class to build
    // lists.
    throw new \Exception('This class is only used for operations and not for building lists.');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\field\FieldInstanceConfigInterface $entity */
    $operations = parent::getDefaultOperations($entity);

    $target_entity_type_bundle_entity_type_id = $this->entityManager->getDefinition($entity->getTargetEntityTypeId())->getBundleEntityType();
    $route_parameters = array(
      $target_entity_type_bundle_entity_type_id => $entity->targetBundle(),
      'field_instance_config' => $entity->id(),
    );
    $operations['edit'] = array(
      'title' => $this->t('Edit'),
      'route_name' => 'field_ui.instance_edit_' . $entity->getTargetEntityTypeId(),
      'route_parameters' => $route_parameters,
      'attributes' => array('title' => $this->t('Edit instance settings.')),
    );
    $operations['field-settings'] = array(
      'title' => $this->t('Field settings'),
      'route_name' => 'field_ui.field_edit_' . $entity->getTargetEntityTypeId(),
      'route_parameters' => $route_parameters,
      'attributes' => array('title' => $this->t('Edit field settings.')),
    );
    $operations['delete'] = array(
      'title' => $this->t('Delete'),
      'route_name' => 'field_ui.delete_' . $entity->getTargetEntityTypeId(),
      'route_parameters' => $route_parameters,
      'attributes' => array('title' => $this->t('Delete instance.')),
    );

    return $operations;
  }

}
