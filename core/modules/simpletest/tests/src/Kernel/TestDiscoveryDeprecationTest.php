<?php

namespace Drupal\Tests\simpletest\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\simpletest\TestDiscovery;

/**
 * @group simpletest
 * @group legacy
 *
 * @coversDefaultClass \Drupal\simpletest\TestDiscovery
 */
class TestDiscoveryDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['simpletest', 'simpletest_deprecation_test'];

  /**
   * @expectedDeprecation The deprecated alter hook hook_simpletest_alter() is implemented in these functions: simpletest_deprecation_test_simpletest_alter. Convert your test to a PHPUnit-based one and implement test listeners. See: https://www.drupal.org/node/2939892
   * @covers ::getTestClasses
   */
  public function testHookSimpletestAlter() {
    $test_discovery = $this->container->get('test_discovery');

    $this->assertEquals(TestDiscovery::class, get_class($test_discovery));

    // The simpletest_test module implements hook_simpletest_alter(), which
    // should trigger a deprecation error during getTestClasses().
    $this->assertNotEmpty($test_discovery->getTestClasses());
  }

}
