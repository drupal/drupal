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
    $key = $this->getKey($section_storage);
    $tempstore = $this->getTempstore($section_storage)->get($key);
    if (!empty($tempstore['section_storage'])) {
      $storage_type = $section_storage->getStorageType();
      $section_storage = $tempstore['section_storage'];

      if (!($section_storage instanceof SectionStorageInterface)) {
        throw new \UnexpectedValueException(sprintf('The entry with storage type "%s" and ID "%s" is invalid', $storage_type, $key));
      }
    }
    return $section_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function has(SectionStorageInterface $section_storage) {
    $key = $this->getKey($section_storage);
    $tempstore = $this->getTempstore($section_storage)->get($key);
    return !empty($tempstore['section_storage']);
  }

  /**
   * {@inheritdoc}
   */
  public function set(SectionStorageInterface $section_storage) {
    $key = $this->getKey($section_storage);
    $this->getTempstore($section_storage)->set($key, ['section_storage' => $section_storage]);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(SectionStorageInterface $section_storage) {
    $key = $this->getKey($section_storage);
    $this->getTempstore($section_storage)->delete($key);
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
    $collection = 'layout_builder.section_storage.' . $section_storage->getStorageType();
    return $this->tempStoreFactory->get($collection);
  }

  /**
   * Gets the string to use as the tempstore key.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   *
   * @return string
   *   A unique string representing the section storage. This should include as
   *   much identifying information as possible about this particular storage,
   *   including information like the current language.
   */
  protected function getKey(SectionStorageInterface $section_storage) {
    if ($section_storage instanceof TempStoreIdentifierInterface) {
      return $section_storage->getTempstoreKey();
    }

    return $section_storage->getStorageId();
  }

}
