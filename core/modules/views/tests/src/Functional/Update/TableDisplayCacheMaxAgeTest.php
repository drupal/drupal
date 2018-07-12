<?php

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the upgrade path for cache max age with table displays.
 *
 * @see views_post_update_table_display_cache_max_age()
 *
 * @group Update
 * @group legacy
 */
class TableDisplayCacheMaxAgeTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/table-cache-max-age.php',
    ];
  }

  /**
   * Tests the upgrade path for cache max age with table displays.
   */
  public function testViewsPostUpdateTableDisplayMaxCacheAge() {
    $view = View::load('test_table_max_age');
    $data = $view->toArray();
    $this->assertSame(0, $data['display']['default']['cache_metadata']['max-age']);

    $this->runUpdates();

    // Load and initialize our test view.
    $view = View::load('test_table_max_age');
    $data = $view->toArray();
    // Check that the field is using the expected max age value.
    $this->assertSame(-1, $data['display']['default']['cache_metadata']['max-age']);
  }

}
