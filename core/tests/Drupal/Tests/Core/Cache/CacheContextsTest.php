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

/**
 * Tests the CacheContexts service.
 *
 * @group Cache
 *
 * @see \Drupal\Core\Cache\CacheContexts
 */
class CacheContextsTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'CacheContext test',
      'description' => 'Tests cache contexts.',
      'group' => 'Cache',
    );
  }

  public function testContextPlaceholdersAreReplaced() {
    $container = $this->getMockContainer();
    $container->expects($this->once())
              ->method("get")
              ->with("cache_context.foo")
              ->will($this->returnValue(new FooCacheContext()));

    $cache_contexts = new CacheContexts($container, $this->getContextsFixture());

    $new_keys = $cache_contexts->convertTokensToKeys(
      array("non-cache-context", "cache_context.foo")
    );

    $expected = array("non-cache-context", "bar");
    $this->assertEquals($expected, $new_keys);
  }

  public function testAvailableContextStrings() {
    $cache_contexts = new CacheContexts($this->getMockContainer(), $this->getContextsFixture());
    $contexts = $cache_contexts->getAll();
    $this->assertEquals(array("cache_context.foo"), $contexts);
  }

  public function testAvailableContextLabels() {
    $container = $this->getMockContainer();
    $container->expects($this->once())
              ->method("get")
              ->with("cache_context.foo")
              ->will($this->returnValue(new FooCacheContext()));

    $cache_contexts = new CacheContexts($container, $this->getContextsFixture());
    $labels = $cache_contexts->getLabels();
    $expected = array("cache_context.foo" => "Foo");
    $this->assertEquals($expected, $labels);
  }

  protected function getContextsFixture() {
    return array('cache_context.foo');
  }

  protected function getMockContainer() {
    return $this->getMockBuilder('Drupal\Core\DependencyInjection\Container')
                ->disableOriginalConstructor()
                ->getMock();
  }
}
