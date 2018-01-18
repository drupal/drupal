<?php

namespace Drupal\Tests\Core\DependencyInjection;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\Core\DependencyInjection\Fixture\BarClass;

/**
 * @coversDefaultClass \Drupal\Core\DependencyInjection\ContainerBuilder
 * @group DependencyInjection
 */
class ContainerBuilderTest extends UnitTestCase {

  /**
   * @covers ::get
   */
  public function testGet() {
    $container = new ContainerBuilder();
    $container->register('bar', 'Drupal\Tests\Core\DependencyInjection\Fixture\BarClass');

    $result = $container->get('bar');
    $this->assertTrue($result instanceof BarClass);
  }

  /**
   * @covers ::set
   */
  public function testSet() {
    $container = new ContainerBuilder();
    $class = new BarClass();
    $container->set('bar', $class);
    $this->assertEquals('bar', $class->_serviceId);
  }

  /**
   * @covers ::set
   */
  public function testSetException() {
    $container = new ContainerBuilder();
    $class = new BarClass();
    $this->setExpectedException(\InvalidArgumentException::class, 'Service ID names must be lowercase: Bar');
    $container->set('Bar', $class);
  }

  /**
   * @covers ::setParameter
   */
  public function testSetParameterException() {
    $container = new ContainerBuilder();
    $this->setExpectedException(\InvalidArgumentException::class, 'Parameter names must be lowercase: Buzz');
    $container->setParameter('Buzz', 'buzz');
  }

  /**
   * @covers ::register
   */
  public function testRegisterException() {
    $container = new ContainerBuilder();
    $this->setExpectedException(\InvalidArgumentException::class, 'Service ID names must be lowercase: Bar');
    $container->register('Bar');
  }

  /**
   * @covers ::register
   */
  public function testRegister() {
    $container = new ContainerBuilder();
    $service = $container->register('bar');
    $this->assertTrue($service->isPublic());
  }

  /**
   * @covers ::setAlias
   */
  public function testSetAlias() {
    $container = new ContainerBuilder();
    $container->register('bar');
    $alias = $container->setAlias('foo', 'bar');
    $this->assertTrue($alias->isPublic());
  }

  /**
   * Tests serialization.
   */
  public function testSerialize() {
    $container = new ContainerBuilder();
    $this->setExpectedException(\AssertionError::class);
    serialize($container);
  }

}
