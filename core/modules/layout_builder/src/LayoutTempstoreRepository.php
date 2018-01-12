<?php

namespace Drupal\layout_builder;

use Drupal\Core\TempStore\SharedTempStoreFactory;

/**
 * Provides a mechanism for loading layouts from tempstore.
 *
 * @internal
 */
class LayoutTempstoreRepository implements LayoutTempstoreRepositoryInterface {

  /**
   * The shared tempstore factory.
   *
   * @var \Drupal\Core\TempStore\SharedTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * LayoutTempstoreRepository constructor.
   *
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $temp_store_factory
   *   The shared tempstore factory.
   */
  public function __construct(SharedTempStoreFactory $temp_store_factory) {
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function get(SectionStorageInterface $section_storage) {
    $id = $section_storage->getStorageId();
    $tempstore = $this->getTempstore($section_storage)->get($id);
    if (!empty($tempstore['section_storage'])) {
      $storage_type = $section_storage->getStorageType();
      $section_storage = $tempstore['section_storage'];

      if (!($section_storage instanceof SectionStorageInterface)) {
        throw new \UnexpectedValueException(sprintf('The entry with storage type "%s" and ID "%s" is invalid', $storage_type, $id));
      }
    }
    return $section_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function set(SectionStorageInterface $section_storage) {
    $id = $section_storage->getStorageId();
    $this->getTempstore($section_storage)->set($id, ['section_storage' => $section_storage]);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(SectionStorageInterface $section_storage) {
    $id = $section_storage->getStorageId();
    $this->getTempstore($section_storage)->delete($id);
  }

  /**
   * Gets the shared tempstore.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   *
   * @return \Drupal\Core\TempStore\SharedTempStore
   *   The tempstore.
   */
  protected function getTempstore(SectionStorageInterface $section_storage) {
    $collection = 'layout_builder.' . $section_storage->getStorageType();
    return $this->tempStoreFactory->get($collection);
  }

}
