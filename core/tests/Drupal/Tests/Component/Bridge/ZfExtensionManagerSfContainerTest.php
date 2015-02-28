<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Bridge\ZfExtensionManagerSfContainerTest.
 */

namespace Drupal\Tests\Component\Bridge;

use Drupal\Component\Bridge\ZfExtensionManagerSfContainer;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\Component\Bridge\ZfExtensionManagerSfContainer
 * @group Bridge
 */
class ZfExtensionManagerSfContainerTest extends UnitTestCase {

  /**
   * @covers ::setContainer
   * @covers ::get
   */
  public function testGet() {
    $service = new \stdClass();
    $service->value = 'myvalue';
    $container = new ContainerBuilder();
    $container->set('foo', $service);
    $bridge = new ZfExtensionManagerSfContainer();
    $bridge->setContainer($container);
    $this->assertEquals($service, $bridge->get('foo'));
  }

  /**
   * @covers ::setContainer
   * @covers ::has
   */
  public function testHas() {
    $service = new \stdClass();
    $service->value = 'myvalue';
    $container = new ContainerBuilder();
    $container->set('foo', $service);
    $bridge = new ZfExtensionManagerSfContainer();
    $bridge->setContainer($container);
    $this->assertTrue($bridge->has('foo'));
    $this->assertFalse($bridge->has('bar'));
  }

  /**
   * @covers ::__construct
   * @covers ::has
   * @covers ::get
   */
  public function testPrefix() {
    $service = new \stdClass();
    $service->value = 'myvalue';
    $container = new ContainerBuilder();
    $container->set('foo.bar', $service);
    $bridge = new ZfExtensionManagerSfContainer('foo.');
    $bridge->setContainer($container);
    $this->assertTrue($bridge->has('bar'));
    $this->assertFalse($bridge->has('baz'));
    $this->assertEquals($service, $bridge->get('bar'));
  }

  /**
   * @covers ::canonicalizeName
   * @dataProvider canonicalizeNameProvider
   */
  public function testCanonicalizeName($name, $canonical_name) {
    $service = new \stdClass();
    $service->value = 'myvalue';
    $container = new ContainerBuilder();
    $container->set($canonical_name, $service);
    $bridge = new ZfExtensionManagerSfContainer();
    $bridge->setContainer($container);
    $this->assertTrue($bridge->has($name));
    $this->assertEquals($service, $bridge->get($name));
  }

  /**
   * Data provider for testReverseProxyEnabled.
   *
   * Replacements:
   *   array('-' => '', '_' => '', ' ' => '', '\\' => '', '/' => '')
   */
  public function canonicalizeNameProvider() {
    return array(
      array(
        'foobar',
        'foobar',
      ),
      array(
        'foo-bar',
        'foobar',
      ),
      array(
        'foo_bar',
        'foobar',
      ),
      array(
        'foo bar',
        'foobar',
      ),
      array(
        'foo\\bar',
        'foobar',
      ),
      array(
        'foo/bar',
        'foobar',
      ),
      // There is also a strtolower in canonicalizeName.
      array(
        'Foo/bAr',
        'foobar',
      ),
      array(
        'foo/-_\\ bar',
        'foobar',
      ),
    );
  }
}
