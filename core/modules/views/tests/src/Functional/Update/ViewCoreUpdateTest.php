<?php

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for removing the core key from views configuration.
 *
 * @see views_post_update_remove_core_key()
 *
 * @group Update
 * @group legacy
 */
class ViewCoreUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.4.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests that the core key is removed from views configuration.
   */
  public function testPostUpdate() {
    $this->assertArrayHasKey('core', \Drupal::config('views.view.frontpage')->get());
    $this->runUpdates();

    // Load and initialize our test view.
    $this->assertArrayNotHasKey('core', \Drupal::config('views.view.frontpage')->get());
  }

}
