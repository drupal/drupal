<?php

namespace Drupal\layout_builder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\user\SharedTempStoreFactory;

/**
 * Provides a mechanism for loading layouts from tempstore.
 *
 * @internal
 */
class LayoutTempstoreRepository implements LayoutTempstoreRepositoryInterface {

  /**
   * The shared tempstore factory.
   *
   * @var \Drupal\user\SharedTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * LayoutTempstoreRepository constructor.
   *
   * @param \Drupal\user\SharedTempStoreFactory $temp_store_factory
   *   The shared tempstore factory.
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
  public function get(EntityInterface $entity) {
    $id = $this->generateTempstoreId($entity);
    $tempstore = $this->getTempstore($entity)->get($id);
    if (!empty($tempstore['entity'])) {
      $entity_type_id = $entity->getEntityTypeId();
      $entity = $tempstore['entity'];

      if (!($entity instanceof EntityInterface)) {
        throw new \UnexpectedValueException(sprintf('The entry with entity type "%s" and ID "%s" is not a valid entity', $entity_type_id, $id));
      }
    }
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getFromId($entity_type_id, $entity_id) {
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->loadRevision($entity_id);
    return $this->get($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function set(EntityInterface $entity) {
    $id = $this->generateTempstoreId($entity);
    $this->getTempstore($entity)->set($id, ['entity' => $entity]);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(EntityInterface $entity) {
    if ($this->get($entity)) {
      $id = $this->generateTempstoreId($entity);
      $this->getTempstore($entity)->delete($id);
    }
  }

  /**
   * Generates an ID for putting an entity in tempstore.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being stored.
   *
   * @return string
   *   The tempstore ID.
   */
  protected function generateTempstoreId(EntityInterface $entity) {
    $id = "{$entity->id()}.{$entity->language()->getId()}";
    if ($entity instanceof RevisionableInterface) {
      $id .= '.' . $entity->getRevisionId();
    }
    return $id;
  }

  /**
   * Gets the shared tempstore.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being stored.
   *
   * @return \Drupal\user\SharedTempStore
   *   The tempstore.
   */
  protected function getTempstore(EntityInterface $entity) {
    $collection = $entity->getEntityTypeId() . '.layout_builder__layout';
    return $this->tempStoreFactory->get($collection);
  }

}
