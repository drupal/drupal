<?php

namespace Drupal\Tests\simpletest\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\simpletest\TestDiscovery;

/**
 * Verify deprecations within the simpletest module.
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

  /**
   * @expectedDeprecation Drupal\simpletest\TestDiscovery is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Test\TestDiscovery instead. See https://www.drupal.org/node/2949692
   * @expectedDeprecation The "test_discovery" service relies on the deprecated "Drupal\simpletest\TestDiscovery" class. It should either be deprecated or its implementation upgraded.
   */
  public function testDeprecatedServices() {
    $this->assertInstanceOf(TestDiscovery::class, $this->container->get('test_discovery'));
  }

}
