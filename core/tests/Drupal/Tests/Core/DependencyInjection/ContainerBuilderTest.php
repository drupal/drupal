<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\DependencyInjection\ContainerBuilderTest.
 */

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
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage Service ID names must be lowercase: Bar
   */
  public function testSetException() {
    $container = new ContainerBuilder();
    $class = new BarClass();
    $container->set('Bar', $class);
    $this->assertNotEquals('bar', $class->_serviceId);
  }

  /**
   * @covers ::setParameter
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage Parameter names must be lowercase: Buzz
   */
  public function testSetParameterException() {
    $container = new ContainerBuilder();
    $container->setParameter('Buzz', 'buzz');
  }

  /**
   * @covers ::register
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage Service ID names must be lowercase: Bar
   */
  public function testRegisterException() {
    $container = new ContainerBuilder();
    $container->register('Bar');
  }

  /**
   * Tests serialization.
   *
   * @expectedException \AssertionError
   */
  public function testSerialize() {
    $container = new ContainerBuilder();
    serialize($container);
  }

}
