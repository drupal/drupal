<?php

namespace Drupal\layout_builder;

/**
 * Provides an interface for loading layouts from tempstore.
 *
 * @internal
 *   Layout Builder is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
interface LayoutTempstoreRepositoryInterface {

  /**
   * Gets the tempstore version of a section storage, if it exists.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage to check for in tempstore.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface
   *   Either the version of this section storage from tempstore, or the passed
   *   section storage if none exists.
   *
   * @throw \UnexpectedValueException
   *   Thrown if a value exists, but is not a section storage.
   */
  public function get(SectionStorageInterface $section_storage);

  /**
   * Stores this section storage in tempstore.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage to set in tempstore.
   */
  public function set(SectionStorageInterface $section_storage);

  /**
   * Checks for the existence of a tempstore version of a section storage.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage to check for in tempstore.
   *
   * @return bool
   *   TRUE if there is a tempstore version of this section storage.
   */
  public function has(SectionStorageInterface $section_storage);

  /**
   * Removes the tempstore version of a section storage.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage to remove from tempstore.
   */
  public function delete(SectionStorageInterface $section_storage);

}
