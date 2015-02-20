<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Cache\CacheContextsTest.
 */

namespace Drupal\Tests\Core\Cache;

use Drupal\Core\Cache\CacheContexts;
use Drupal\Core\Cache\CacheContextInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Cache\CacheContexts
 * @group Cache
 */
class CacheContextsTest extends UnitTestCase {

  public function testContextPlaceholdersAreReplaced() {
    $container = $this->getMockContainer();
    $container->expects($this->once())
              ->method("get")
              ->with("cache_context.foo")
              ->will($this->returnValue(new FooCacheContext()));

    $cache_contexts = new CacheContexts($container, $this->getContextsFixture());

    $new_keys = $cache_contexts->convertTokensToKeys(
      ['foo']
    );

    $expected = ['bar'];
    $this->assertEquals($expected, $new_keys);
  }

  /**
   * @covers ::convertTokensToKeys
   *
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage "non-cache-context" is not a valid cache context ID.
   */
  public function testInvalidContext() {
    $container = $this->getMockContainer();
    $cache_contexts = new CacheContexts($container, $this->getContextsFixture());

    $cache_contexts->convertTokensToKeys(
      ["non-cache-context"]
    );
  }

  public function testAvailableContextStrings() {
    $cache_contexts = new CacheContexts($this->getMockContainer(), $this->getContextsFixture());
    $contexts = $cache_contexts->getAll();
    $this->assertEquals(array("foo"), $contexts);
  }

  public function testAvailableContextLabels() {
    $container = $this->getMockContainer();
    $container->expects($this->once())
              ->method("get")
              ->with("cache_context.foo")
              ->will($this->returnValue(new FooCacheContext()));

    $cache_contexts = new CacheContexts($container, $this->getContextsFixture());
    $labels = $cache_contexts->getLabels();
    $expected = array("foo" => "Foo");
    $this->assertEquals($expected, $labels);
  }

  protected function getContextsFixture() {
    return array('foo');
  }

  protected function getMockContainer() {
    return $this->getMockBuilder('Drupal\Core\DependencyInjection\Container')
                ->disableOriginalConstructor()
                ->getMock();
  }
}

/**
 * Fake cache context class.
 */
class FooCacheContext implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return 'Foo';
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return 'bar';
  }

}

