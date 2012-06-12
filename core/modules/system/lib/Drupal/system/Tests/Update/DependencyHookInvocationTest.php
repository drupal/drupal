<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Update\DependencyHookInvocationTest.
 */

namespace Drupal\system\Tests\Update;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for the invocation of hook_update_dependencies().
 */
class DependencyHookInvocationTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Update dependency hook invocation',
      'description' => 'Test that the hook invocation for determining update dependencies works correctly.',
      'group' => 'Update API',
    );
  }

  function setUp() {
    parent::setUp('update_test_1', 'update_test_2');
    require_once DRUPAL_ROOT . '/core/includes/update.inc';
  }

  /**
   * Test the structure of the array returned by hook_update_dependencies().
   */
  function testHookUpdateDependencies() {
    $update_dependencies = update_retrieve_dependencies();
    $this->assertTrue($update_dependencies['system'][8000]['update_test_1'] == 8000, t('An update function that has a dependency on two separate modules has the first dependency recorded correctly.'));
    $this->assertTrue($update_dependencies['system'][8000]['update_test_2'] == 8001, t('An update function that has a dependency on two separate modules has the second dependency recorded correctly.'));
    $this->assertTrue($update_dependencies['system'][8001]['update_test_1'] == 8002, t('An update function that depends on more than one update from the same module only has the dependency on the higher-numbered update function recorded.'));
  }
}
