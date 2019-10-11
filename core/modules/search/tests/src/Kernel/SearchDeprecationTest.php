<?php

namespace Drupal\Tests\search\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deprecated search methods.
 *
 * @group legacy
 * @group search
 */
class SearchDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['search'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('search', [
      'search_index',
      'search_dataset',
      'search_total',
    ]);
    $this->installConfig(['search']);
  }

  /**
   * @expectedDeprecation search_index() is deprecated in drupal:8.8.0 and is removed in drupal:9.0.0. Use \Drupal\search\SearchIndex::index() instead. See https://www.drupal.org/node/3075696
   */
  public function testIndex() {
    $this->assertNull(search_index('_test_', 1, LanguageInterface::LANGCODE_NOT_SPECIFIED, "foo"));
  }

  /**
   * @expectedDeprecation search_index_clear() is deprecated in drupal:8.8.0 and is removed in drupal:9.0.0. Use \Drupal\search\SearchIndex::clear() instead. See https://www.drupal.org/node/3075696
   */
  public function testClear() {
    $this->assertNull(search_index_clear());
  }

  /**
   * @expectedDeprecation search_dirty() is deprecated in drupal:8.8.0 and is removed in drupal:9.0.0. Use custom implementation of \Drupal\search\SearchIndexInterface instead. See https://www.drupal.org/node/3075696
   */
  public function testDirty() {
    $this->assertNull(search_dirty("foo"));
    $this->assertEqual([], search_dirty());
  }

  /**
   * @expectedDeprecation search_update_totals() is deprecated in drupal:8.8.0 and is removed in drupal:9.0.0. Use custom implementation of \Drupal\search\SearchIndexInterface instead. See https://www.drupal.org/node/3075696
   */
  public function testUpdateTotals() {
    $this->assertNull(search_update_totals());
  }

  /**
   * @expectedDeprecation search_mark_for_reindex() is deprecated in drupal:8.8.0 and is removed in drupal:9.0.0. Use \Drupal\search\SearchIndex::markForReindex() instead. See https://www.drupal.org/node/3075696
   */
  public function testMarkForReindex() {
    $this->assertNull(search_mark_for_reindex('_test_', 1, LanguageInterface::LANGCODE_NOT_SPECIFIED));
  }

}
