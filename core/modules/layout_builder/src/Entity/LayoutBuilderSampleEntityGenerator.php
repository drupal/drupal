<?php

namespace Drupal\layout_builder\Entity;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;

/**
 * Generates a sample entity for use by the Layout Builder.
 */
class LayoutBuilderSampleEntityGenerator implements SampleEntityGeneratorInterface {

  /**
   * The shared tempstore factory.
   *
   * @var \Drupal\Core\TempStore\SharedTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * LayoutBuilderSampleEntityGenerator constructor.
   *
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(SharedTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function get($entity_type_id, $bundle_id) {
    $tempstore = $this->tempStoreFactory->get('layout_builder.sample_entity');
    if ($entity = $tempstore->get("$entity_type_id.$bundle_id")) {
      return $entity;
    }

    $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
    if (!$entity_storage instanceof ContentEntityStorageInterface) {
      throw new \InvalidArgumentException(sprintf('The "%s" entity storage is not supported', $entity_type_id));
    }

    $entity = $entity_storage->createWithSampleValues($bundle_id);
    // Mark the sample entity as being a preview.
    $entity->in_preview = TRUE;
    $tempstore->set("$entity_type_id.$bundle_id", $entity);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($entity_type_id, $bundle_id) {
    $tempstore = $this->tempStoreFactory->get('layout_builder.sample_entity');
    $tempstore->delete("$entity_type_id.$bundle_id");
    return $this;
  }

}
