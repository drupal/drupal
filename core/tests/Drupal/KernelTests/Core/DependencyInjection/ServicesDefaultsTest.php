<?php

namespace Drupal\KernelTests\Core\DependencyInjection;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Tests services _defaults definition.
 *
 * @group DependencyInjection
 */
class ServicesDefaultsTest extends KernelTestBase {

  protected static $modules = ['services_defaults_test'];

  /**
   * Tests that 'services_defaults_test.service' has its dependencies injected.
   */
  public function testAutowiring() {
    // Ensure interface autowiring works.
    $this->assertSame(
      $this->container->get('Drupal\services_defaults_test\TestInjection'),
      $this->container->get('Drupal\services_defaults_test\TestService')->getTestInjection()
    );
    // Ensure defaults autowire works.
    $this->assertSame(
      $this->container->get('Drupal\services_defaults_test\TestInjection2'),
      $this->container->get('Drupal\services_defaults_test\TestService')->getTestInjection2()
    );
  }

  /**
   * Tests that default tags for 'services_defaults_test.service' are applied.
   */
  public function testDefaultTags() {
    // Ensure default tags work.
    $testServiceDefinition = $this->container->getDefinition('Drupal\services_defaults_test\TestService');
    $testInjection1Definition = $this->container->getDefinition('Drupal\services_defaults_test\TestInjection');
    $testInjection2Definition = $this->container->getDefinition('Drupal\services_defaults_test\TestInjection2');

    $this->assertTrue($testServiceDefinition->hasTag('foo.tag1'));
    $this->assertTrue($testServiceDefinition->hasTag('bar.tag2'));

    $this->assertSame(
      $testServiceDefinition->getTags(),
      $testInjection1Definition->getTags());

    // Ensure overridden tag works.
    $this->assertTrue($testInjection2Definition->hasTag('zee.bang'));
  }

  /**
   * Tests that service from 'services_defaults_test.service' is private.
   */
  public function testPrivateServices() {
    // Ensure default and overridden public flag works.
    $this->expectException(ServiceNotFoundException::class);
    $this->container->getDefinition('Drupal\services_defaults_test\TestPrivateService');
  }

}
