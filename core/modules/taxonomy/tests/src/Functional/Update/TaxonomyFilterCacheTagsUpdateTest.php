<?php

namespace Drupal\Tests\taxonomy\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the upgrade path for adding cache tags to views.
 *
 * For the views with "taxonomy_index_tid" filter plugin, we are re-saving
 * and adding necessary cache tags.
 *
 * @see views_post_update_add_tid_cache_tags()
 *
 * @group taxonomy
 */
class TaxonomyFilterCacheTagsUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/test-taxonomy-exposed-filter-plugin.php',
    ];
  }

  /**
   * Tests the upgrade path for adding cache tags to views.
   *
   * For the views with "taxonomy_index_tid" filter plugin, we are re-saving
   * and adding necessary cache tags.
   */
  public function testViewsPostUpdateAddTidCacheTags(): void {
    /** @var \Drupal\views\Entity\View $view */
    $config = \Drupal::configFactory()->get('views.view.test_filter_taxonomy_index_tid');
    $cache_tags = [
      'config:taxonomy.vocabulary.tags',
      'taxonomy_term_list:tags',
    ];
    $this->assertNotEquals($cache_tags, $config->getCacheTags());

    $this->runUpdates();

    /** @var \Drupal\views\Entity\View $view */
    $view = View::load('test_filter_taxonomy_index_tid');
    $view_executable = $view->getExecutable();
    $view_executable->initDisplay();
    $cache_metadata = $view_executable->getDisplay()->calculateCacheMetadata();

    $cache_tags = [
      'config:taxonomy.vocabulary.tags',
      'taxonomy_term_list:tags',
    ];
    $this->assertEquals($cache_tags, $cache_metadata->getCacheTags());

  }

}
