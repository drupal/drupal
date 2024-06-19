<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that update functions are run in the proper order.
 *
 * @group Update
 */
class DependencyOrderingTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'update_test_0',
    'update_test_1',
    'update_test_2',
    'update_test_3',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once $this->root . '/core/includes/update.inc';
  }

  /**
   * Tests that updates within a single module run in the correct order.
   */
  public function testUpdateOrderingSingleModule(): void {
    $starting_updates = [
      'update_test_1' => 8001,
    ];
    $expected_updates = [
      'update_test_1_update_8001',
      'update_test_1_update_8002',
      'update_test_1_update_8003',
    ];
    $actual_updates = array_keys(update_resolve_dependencies($starting_updates));
    $this->assertEquals($expected_updates, $actual_updates, 'Updates within a single module run in the correct order.');
  }

  /**
   * Tests that dependencies between modules are resolved correctly.
   */
  public function testUpdateOrderingModuleInterdependency(): void {
    $starting_updates = [
      'update_test_2' => 8001,
      'update_test_3' => 8001,
    ];
    $update_order = array_keys(update_resolve_dependencies($starting_updates));
    // Make sure that each dependency is satisfied.
    $first_dependency_satisfied = array_search('update_test_2_update_8001', $update_order) < array_search('update_test_3_update_8001', $update_order);
    $this->assertTrue($first_dependency_satisfied, 'The dependency of the second module on the first module is respected by the update function order.');
    $second_dependency_satisfied = array_search('update_test_3_update_8001', $update_order) < array_search('update_test_2_update_8002', $update_order);
    $this->assertTrue($second_dependency_satisfied, 'The dependency of the first module on the second module is respected by the update function order.');
  }

}
