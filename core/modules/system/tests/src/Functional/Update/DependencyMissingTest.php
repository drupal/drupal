<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that missing update dependencies are correctly flagged.
 *
 * @group Update
 */
class DependencyMissingTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['update_test_0', 'update_test_2'];

  protected function setUp() {
    // Only install update_test_2.module, even though its updates have a
    // dependency on update_test_3.module.
    parent::setUp();
    require_once \Drupal::root() . '/core/includes/update.inc';
  }

  public function testMissingUpdate() {
    $starting_updates = [
      'update_test_2' => 8001,
    ];
    $update_graph = update_resolve_dependencies($starting_updates);
    $this->assertTrue($update_graph['update_test_2_update_8001']['allowed'], "The module's first update function is allowed to run, since it does not have any missing dependencies.");
    $this->assertFalse($update_graph['update_test_2_update_8002']['allowed'], "The module's second update function is not allowed to run, since it has a direct dependency on a missing update.");
    $this->assertFalse($update_graph['update_test_2_update_8003']['allowed'], "The module's third update function is not allowed to run, since it has an indirect dependency on a missing update.");
  }

}
