<?php

namespace Drupal\Tests\simpletest\Kernel\Cache\Context;

use Drupal\KernelTests\KernelTestBase;
use Drupal\simpletest\Cache\Context\TestDiscoveryCacheContext;
use Drupal\simpletest\TestDiscovery;

/**
 * @group simpletest
 */
class TestDiscoveryCacheContextTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['simpletest'];

  /**
   * Tests that test context hashes are unique.
   */
  public function testContext() {
    // Mock test discovery.
    $discovery = $this->getMockBuilder(TestDiscovery::class)
      ->setMethods(['getTestClasses'])
      ->disableOriginalConstructor()
      ->getMock();
    // Set getTestClasses() to return different results on subsequent calls.
    // This emulates changed tests in the filesystem.
    $discovery->expects($this->any())
      ->method('getTestClasses')
      ->willReturnOnConsecutiveCalls(
        ['group1' => ['Test']],
        ['group2' => ['Test2']]
      );

    // Make our cache context object.
    $cache_context = new TestDiscoveryCacheContext($discovery, $this->container->get('private_key'));

    // Generate a context hash.
    $context_hash = $cache_context->getContext();

    // Since the context stores the hash, we have to reset it.
    $hash_ref = new \ReflectionProperty($cache_context, 'hash');
    $hash_ref->setAccessible(TRUE);
    $hash_ref->setValue($cache_context, NULL);

    // And then assert that we did not generate the same hash for different
    // content.
    $this->assertNotSame($context_hash, $cache_context->getContext());
  }

}
