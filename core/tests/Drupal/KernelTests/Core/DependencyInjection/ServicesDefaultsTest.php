<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\DependencyInjection;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Tests services _defaults definition.
 *
 * @group DependencyInjection
 */
class ServicesDefaultsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['services_defaults_test'];

  /**
   * Tests that 'services_defaults_test.service' has its dependencies injected.
   */
  public function testAutowiring(): void {
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

    // Ensure that disabling autowiring works.
    $this->assertNotSame(
      $this->container->get('Drupal\services_defaults_test\TestInjection'),
      $this->container->get('services_default_test.no_autowire')->getTestInjection()
    );
    $this->assertSame(
      $this->container->get('services_default_test.no_autowire.arg'),
      $this->container->get('services_default_test.no_autowire')->getTestInjection()
    );
    $this->assertSame(
      $this->container->get('Drupal\services_defaults_test\TestInjection2'),
      $this->container->get('services_default_test.no_autowire')->getTestInjection2()
    );

  }

  /**
   * Tests that default tags for 'services_defaults_test.service' are applied.
   */
  public function testDefaultTags(): void {
    // Ensure default tags work.
    $testServiceDefinition = $this->container->getDefinition('Drupal\services_defaults_test\TestService');
    $testInjection1Definition = $this->container->getDefinition('Drupal\services_defaults_test\TestInjection');
    $testInjection2Definition = $this->container->getDefinition('Drupal\services_defaults_test\TestInjection2');

    $this->assertTrue($testServiceDefinition->hasTag('foo.tag1'));
    $this->assertTrue($testServiceDefinition->hasTag('bar.tag2'));
    $this->assertSame([['test' => 123]], $testServiceDefinition->getTag('bar.tag2'));
    $this->assertTrue($testServiceDefinition->hasTag('bar.tag3'));
    $this->assertSame([['value' => NULL]], $testServiceDefinition->getTag('bar.tag3'));

    $this->assertSame($testServiceDefinition->getTags(), $testInjection1Definition->getTags());

    // Ensure overridden tag works.
    $this->assertTrue($testInjection2Definition->hasTag('zee.bang'));
    $this->assertTrue($testInjection2Definition->hasTag('foo.tag1'));
    $this->assertTrue($testInjection2Definition->hasTag('bar.tag2'));
    $this->assertSame([['test' => 321], ['test' => 123]], $testInjection2Definition->getTag('bar.tag2'));
    $this->assertTrue($testInjection2Definition->hasTag('bar.tag3'));
    $this->assertSame([['value' => NULL]], $testInjection2Definition->getTag('bar.tag3'));
  }

  /**
   * Tests that service from 'services_defaults_test.service' is private.
   */
  public function testPrivateServices(): void {
    // Ensure default and overridden public flag works.
    $this->expectException(ServiceNotFoundException::class);
    $this->container->getDefinition('Drupal\services_defaults_test\TestPrivateService');
  }

}
