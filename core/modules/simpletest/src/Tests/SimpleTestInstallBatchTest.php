<?php

/**
 * @file
 * Contains \Drupal\simpletest\Tests\SimpleTestInstallBatchTest.
 */

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests batch operations during tests execution.
 *
 * This demonstrates that a batch will be successfully executed during module
 * installation when running tests.
 *
 * @group simpletest
 */
class SimpleTestInstallBatchTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('simpletest', 'simpletest_test', 'entity_test');

  /**
   * Tests loading entities created in a batch in simpletest_test_install().
   */
  public function testLoadingEntitiesCreatedInBatch() {
    $entity1 = entity_load('entity_test', 1);
    $this->assertNotNull($entity1, 'Successfully loaded entity 1.');
    $entity2 = entity_load('entity_test', 2);
    $this->assertNotNull($entity2, 'Successfully loaded entity 2.');
  }

}
