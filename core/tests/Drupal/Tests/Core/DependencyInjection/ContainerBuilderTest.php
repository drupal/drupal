<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\DependencyInjection;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\Core\DependencyInjection\Fixture\BarClass;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Tests Drupal\Core\DependencyInjection\ContainerBuilder.
 */
#[CoversClass(ContainerBuilder::class)]
#[Group('DependencyInjection')]
class ContainerBuilderTest extends UnitTestCase {

  /**
   * Tests get.
   */
  public function testGet(): void {
    $container = new ContainerBuilder();
    $container->register('bar', 'Drupal\Tests\Core\DependencyInjection\Fixture\BarClass');

    $result = $container->get('bar');
    $this->assertInstanceOf(BarClass::class, $result);
  }

  /**
   * Tests set parameter exception.
   */
  public function testSetParameterException(): void {
    $container = new ContainerBuilder();
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Parameter names must be lowercase: Buzz');
    $container->setParameter('Buzz', 'buzz');
  }

  /**
   * Tests register.
   */
  public function testRegister(): void {
    $container = new ContainerBuilder();
    $service = $container->register('bar');
    $this->assertTrue($service->isPublic());
  }

  /**
   * Tests set definition.
   */
  public function testSetDefinition(): void {
    // Test a service with public set to true.
    $container = new ContainerBuilder();
    $definition = new Definition();
    $definition->setPublic(TRUE);
    $service = $container->setDefinition('foo', $definition);
    $this->assertTrue($service->isPublic());

    // Test a service with public set to false.
    $definition = new Definition();
    $definition->setPublic(FALSE);
    $service = $container->setDefinition('foo', $definition);
    $this->assertFalse($service->isPublic());
  }

  /**
   * Tests set alias.
   */
  public function testSetAlias(): void {
    $container = new ContainerBuilder();
    $container->register('bar');
    $alias = $container->setAlias('foo', 'bar');
    $this->assertTrue($alias->isPublic());
  }

  /**
   * Tests serialization.
   */
  public function testSerialize(): void {
    $container = new ContainerBuilder();
    $this->expectException(\AssertionError::class);
    serialize($container);
  }

  /**
   * Tests constructor and resource tracking disabling.
   *
   * This test runs in a separate process to ensure the aliased class does not
   * affect any other tests.
   */
  #[PreserveGlobalState(FALSE)]
  #[RunInSeparateProcess]
  public function testConstructor(): void {
    class_alias(TestInterface::class, 'Symfony\Component\Config\Resource\ResourceInterface');
    $container = new ContainerBuilder();
    $this->assertFalse($container->isTrackingResources());
  }

}

/**
 * A test interface for testing ContainerBuilder::__construct().
 *
 * @see \Drupal\Tests\Core\DependencyInjection\ContainerBuilderTest::testConstructor()
 */
interface TestInterface {
}
