<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the update path with a broken router.
 *
 * @group Update
 */
class UpdatePathWithBrokenRoutingTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../../tests/fixtures/update/drupal-8.broken_routing.php',
    ];
  }

  /**
   * Tests running update.php with some form of broken routing.
   */
  public function testWithBrokenRouting() {
    // Simulate a broken router, and make sure the front page is
    // inaccessible.
    \Drupal::state()->set('update_script_test_broken_inbound', TRUE);
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['route_match', 'rendered']);
    $this->drupalGet('<front>');
    $this->assertResponse(500);

    $this->runUpdates();

    // Remove the simulation of the broken router, and make sure we can get to
    // the front page again.
    \Drupal::state()->set('update_script_test_broken_inbound', FALSE);
    $this->drupalGet('<front>');
    $this->assertResponse(200);
  }

}
