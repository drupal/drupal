<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\DependencyInjection\ContainerBuilderTest.
 */

namespace Drupal\Tests\Core\DependencyInjection;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Routing\Tests\Fixtures\AnnotatedClasses\BarClass;

require_once __DIR__ . '../../../../../../vendor/symfony/dependency-injection/Symfony/Component/DependencyInjection/Tests/Fixtures/includes/classes.php';

/**
 * @coversDefaultClass \Drupal\Core\DependencyInjection\ContainerBuilder
 * @group DependencyInjection
 */
class ContainerBuilderTest extends UnitTestCase {

  /**
   * Tests set with a synchronized service.
   */
  public function testSetOnSynchronizedService() {
    $container = new ContainerBuilder();
    $container->register('baz', 'BazClass')
      ->setSynchronized(TRUE);
    $container->register('bar', 'BarClass')
      ->addMethodCall('setBaz', array(new Reference('baz')));

    // Ensure that we can set services on a compiled container.
    $container->compile();

    $container->set('baz', $baz = new \BazClass());
    $this->assertSame($baz, $container->get('bar')->getBaz());

    $container->set('baz', $baz = new \BazClass());
    $this->assertSame($baz, $container->get('bar')->getBaz());
  }

  /**
   * Tests the get method.
   *
   * @see \Drupal\Core\DependencyInjection\Container::get()
   */
  public function testGet() {
    $container = new ContainerBuilder();
    $container->register('bar', 'BarClass');

    $result = $container->get('bar');
    $this->assertTrue($result instanceof \BarClass);
  }

  /**
   * Tests the set() method.
   *
   * @covers ::set
   */
  public function testSet() {
    $container = new ContainerBuilder();
    $class = new BarClass();
    $container->set('bar', $class);
    $this->assertEquals('bar', $class->_serviceId);
  }

}
