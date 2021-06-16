<?php

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\UpdatePathTestTrait;

/**
 * Tests the update path with a broken router.
 *
 * @group Update
 */
class UpdatePathWithBrokenRoutingTest extends BrowserTestBase {
  use UpdatePathTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->ensureUpdatesToRun();
  }

  /**
   * Tests running update.php with some form of broken routing.
   */
  public function testWithBrokenRouting() {
    // Simulate a broken router, and make sure the front page is
    // inaccessible.
    \Drupal::state()->set('update_script_test_broken_inbound', TRUE);
    $this->resetAll();
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(500);

    $this->runUpdates(Url::fromRoute('system.db_update', [], ['path_processing' => FALSE]));

    // Remove the simulation of the broken router, and make sure we can get to
    // the front page again.
    \Drupal::state()->set('update_script_test_broken_inbound', FALSE);
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
  }

}
