<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\DependencyInjection\ContainerBuilderTest.
 */

namespace Drupal\Tests\Core\DependencyInjection;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\Core\DependencyInjection\Fixture\BazClass;
use Drupal\Tests\Core\DependencyInjection\Fixture\BarClass;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @coversDefaultClass \Drupal\Core\DependencyInjection\ContainerBuilder
 * @group DependencyInjection
 */
class ContainerBuilderTest extends UnitTestCase {

  /**
   * Tests set with a synchronized service.
   *
   * @covers ::set
   */
  public function testSetOnSynchronizedService() {
    $container = new ContainerBuilder();
    $container->register('baz', '\Drupal\Tests\Core\DependencyInjection\Fixture\BazClass')
      ->setSynchronized(TRUE);
    $container->register('bar', '\Drupal\Tests\Core\DependencyInjection\Fixture\BarClass')
      ->addMethodCall('setBaz', array(new Reference('baz')));

    // Ensure that we can set services on a compiled container.
    $container->compile();

    $container->set('baz', $baz = new BazClass());
    $this->assertSame($baz, $container->get('bar')->getBaz());

    $container->set('baz', $baz = new BazClass());
    $this->assertSame($baz, $container->get('bar')->getBaz());
  }

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

}
