<?php

namespace Drupal\Tests\simpletest\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Verify deprecation of simpletest.
 *
 * @group simpletest
 * @group legacy
 */
class SimpletestDeprecationTest extends KernelTestBase {

  public static $modules = ['simpletest'];

  /**
   * @expectedDeprecation The simpletest_phpunit_configuration_filepath function is deprecated since version 8.4.x and will be removed in 9.0.0.
   * @expectedDeprecation The simpletest_test_get_all function is deprecated in version 8.3.x and will be removed in 9.0.0. Use \Drupal::service('test_discovery')->getTestClasses($extension, $types) instead.
   * @expectedDeprecation The simpletest_classloader_register function is deprecated in version 8.3.x and will be removed in 9.0.0. Use \Drupal::service('test_discovery')->registerTestNamespaces() instead.
   */
  public function testDeprecatedFunctions() {
    $this->assertNotEmpty(simpletest_phpunit_configuration_filepath());
    $this->assertNotEmpty(simpletest_test_get_all());
    simpletest_classloader_register();
  }

}
