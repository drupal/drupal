<?php

namespace Drupal\views\Tests\Update;

use Drupal\system\Tests\Update\UpdatePathTestBase;
use Drupal\views\Views;

/**
 * Tests that views cacheability metadata post update hook runs properly.
 *
 * @see views_post_update_update_cacheability_metadata().
 *
 * @group Update
 */
class CacheabilityMetadataUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [__DIR__ . '/../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz'];
  }

  /**
   * Tests that views cacheability metadata updated properly.
   */
  public function testUpdateHookN() {
    $this->runUpdates();
    foreach (Views::getAllViews() as $view) {
      $displays = $view->get('display');
      foreach (array_keys($displays) as $display_id) {
        $display = $view->getDisplay($display_id);
        $this->assertFalse(isset($display['cache_metadata']['cacheable']));
        $this->assertTrue(isset($display['cache_metadata']['contexts']));
        $this->assertTrue(isset($display['cache_metadata']['max-age']));
        $this->assertTrue(isset($display['cache_metadata']['tags']));
      }
    }
  }

}
