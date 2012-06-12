<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Update\DependencyMissingTest.
 */

namespace Drupal\system\Tests\Update;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for missing update dependencies.
 */
class DependencyMissingTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Missing update dependencies',
      'description' => 'Test that missing update dependencies are correctly flagged.',
      'group' => 'Update API',
    );
  }

  function setUp() {
    // Only install update_test_2.module, even though its updates have a
    // dependency on update_test_3.module.
    parent::setUp('update_test_2');
    require_once DRUPAL_ROOT . '/core/includes/update.inc';
  }

  function testMissingUpdate() {
    $starting_updates = array(
      'update_test_2' => 8000,
    );
    $update_graph = update_resolve_dependencies($starting_updates);
    $this->assertTrue($update_graph['update_test_2_update_8000']['allowed'], t("The module's first update function is allowed to run, since it does not have any missing dependencies."));
    $this->assertFalse($update_graph['update_test_2_update_8001']['allowed'], t("The module's second update function is not allowed to run, since it has a direct dependency on a missing update."));
    $this->assertFalse($update_graph['update_test_2_update_8002']['allowed'], t("The module's third update function is not allowed to run, since it has an indirect dependency on a missing update."));
  }
}
