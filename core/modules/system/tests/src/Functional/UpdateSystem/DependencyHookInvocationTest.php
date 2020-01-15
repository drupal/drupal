<?php

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the hook invocation for determining update dependencies works
 * correctly.
 *
 * @group Update
 */
class DependencyHookInvocationTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['update_test_0', 'update_test_1', 'update_test_2'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp() {
    parent::setUp();
    require_once $this->root . '/core/includes/update.inc';
  }

  /**
   * Test the structure of the array returned by hook_update_dependencies().
   */
  public function testHookUpdateDependencies() {
    $update_dependencies = update_retrieve_dependencies();
    $this->assertTrue($update_dependencies['update_test_0'][8001]['update_test_1'] == 8001, 'An update function that has a dependency on two separate modules has the first dependency recorded correctly.');
    $this->assertTrue($update_dependencies['update_test_0'][8001]['update_test_2'] == 8002, 'An update function that has a dependency on two separate modules has the second dependency recorded correctly.');
    $this->assertTrue($update_dependencies['update_test_0'][8002]['update_test_1'] == 8003, 'An update function that depends on more than one update from the same module only has the dependency on the higher-numbered update function recorded.');
  }

}
