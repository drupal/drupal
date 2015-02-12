<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\DependencyInjection\ContainerTest.
 */

namespace Drupal\Tests\Core\DependencyInjection;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Tests\Fixtures\AnnotatedClasses\BarClass;

/**
 * @coversDefaultClass \Drupal\Core\DependencyInjection\Container
 * @group DependencyInjection
 */
class ContainerTest extends UnitTestCase {

  /**
   * The tested container.
   *
   * @var \Drupal\Core\DependencyInjection\Container
   */
  public $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->container = new Container();
  }

  /**
   * Tests serialization.
   *
   * @expectedException \PHPUnit_Framework_Error
   */
  public function testSerialize() {
    serialize($this->container);
  }

  /**
   * Tests the set() method.
   *
   * @covers ::set
   */
  public function testSet() {
    $class = new BarClass();
    $this->container->set('bar', $class);
    $this->assertEquals('bar', $class->_serviceId);
  }

}
