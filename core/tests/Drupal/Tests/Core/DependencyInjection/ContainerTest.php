<?php

namespace Drupal\Tests\Core\DependencyInjection;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\Core\DependencyInjection\Fixture\BarClass;

/**
 * @coversDefaultClass \Drupal\Core\DependencyInjection\Container
 * @group DependencyInjection
 */
class ContainerTest extends UnitTestCase {

  /**
   * Tests serialization.
   */
  public function testSerialize() {
    $container = new Container();
    $this->expectException(\AssertionError::class);
    serialize($container);
  }

  /**
   * @covers ::set
   */
  public function testSet() {
    $container = new Container();
    $class = new BarClass();
    $container->set('bar', $class);
    // Ensure that _serviceId is set on the object.
    $this->assertEquals('bar', $class->_serviceId);
  }

}
