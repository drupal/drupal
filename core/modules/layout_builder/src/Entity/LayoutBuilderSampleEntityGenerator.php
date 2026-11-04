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
   * LayoutBuilderSampleEntityGenerator constructor.
   *
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $tempStoreFactory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected SharedTempStoreFactory $tempStoreFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
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
