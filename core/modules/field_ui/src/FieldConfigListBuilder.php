<?php

/**
 * @file
 * Contains \Drupal\field_ui\FieldConfigListBuilder.
 */

namespace Drupal\field_ui;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides lists of field config entities.
 */
class FieldConfigListBuilder extends ConfigEntityListBuilder {

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
    // The actual field config overview is rendered by
    // \Drupal\field_ui\FieldOverview, so we should not use this class to build
    // lists.
    throw new \Exception('This class is only used for operations and not for building lists.');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\field\FieldConfigInterface $entity */
    $operations = parent::getDefaultOperations($entity);

    $operations['storage-settings'] = array(
      'title' => $this->t('Storage settings'),
      'weight' => 20,
      'attributes' => array('title' => $this->t('Edit storage settings.')),
      'url' => $entity->urlInfo('storage-edit-form'),
    );
    $operations['edit']['attributes']['title'] = $this->t('Edit field settings.');
    $operations['delete']['attributes']['title'] = $this->t('Delete field.');

    return $operations;
  }

}
