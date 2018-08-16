<?php

namespace Drupal\simpletest\Tests;

use Drupal\entity_test\Entity\EntityTest;
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
  public static $modules = ['simpletest', 'simpletest_test', 'entity_test'];

  /**
   * Tests loading entities created in a batch in simpletest_test_install().
   */
  public function testLoadingEntitiesCreatedInBatch() {
    $entity1 = EntityTest::load(1);
    $this->assertNotNull($entity1, 'Successfully loaded entity 1.');
    $entity2 = EntityTest::load(2);
    $this->assertNotNull($entity2, 'Successfully loaded entity 2.');
  }

}
