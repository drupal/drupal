<?php

namespace Drupal\Tests\layout_builder\Kernel;

@trigger_error(__NAMESPACE__ . '\SectionStorageTestBase is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Tests\layout_builder\Kernel\SectionListTestBase instead. See https://www.drupal.org/node/3091432', E_USER_DEPRECATED);

/**
 * Provides a base class for testing implementations of section storage.
 *
 * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
 *   \Drupal\Tests\layout_builder\Kernel\SectionListTestBase instead.
 *
 * @see https://www.drupal.org/node/3091432
 */
abstract class SectionStorageTestBase extends SectionListTestBase {

  /**
   * The section list implementation.
   *
   * @var \Drupal\layout_builder\SectionListInterface
   */
  protected $sectionStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Provide backwards compatibility.
    $this->sectionStorage = $this->sectionList;
  }

  /**
   * Sets up the section list.
   *
   * @param array $section_data
   *   An array of section data.
   *
   * @return \Drupal\layout_builder\SectionListInterface
   *   The section list.
   */
  abstract protected function getSectionStorage(array $section_data);

  /**
   * {@inheritdoc}
   */
  protected function getSectionList(array $section_data) {
    // Provide backwards compatibility.
    return $this->getSectionStorage($section_data);
  }

}
