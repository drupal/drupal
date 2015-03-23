<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Cache\CacheContextsTest.
 */

namespace Drupal\Tests\Core\Cache;

use Drupal\Core\Cache\CacheContexts;
use Drupal\Core\Cache\CacheContextInterface;
use Drupal\Core\Cache\CalculatedCacheContextInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * @coversDefaultClass \Drupal\Core\Cache\CacheContexts
 * @group Cache
 */
class CacheContextsTest extends UnitTestCase {

  /**
   * @covers ::optimizeTokens
   *
   * @dataProvider providerTestOptimizeTokens
   */
  public function testOptimizeTokens(array $context_tokens, array $optimized_context_tokens) {
    $container = $this->getMockBuilder('Drupal\Core\DependencyInjection\Container')
      ->disableOriginalConstructor()
      ->getMock();
    $container->expects($this->any())
      ->method('get')
      ->will($this->returnValueMap([
        ['a', Container::EXCEPTION_ON_INVALID_REFERENCE, new FooCacheContext()],
        ['a.b', Container::EXCEPTION_ON_INVALID_REFERENCE, new FooCacheContext()],
        ['a.b.c', Container::EXCEPTION_ON_INVALID_REFERENCE, new BazCacheContext()],
        ['x', Container::EXCEPTION_ON_INVALID_REFERENCE, new BazCacheContext()],
      ]));
    $cache_contexts = new CacheContexts($container, $this->getContextsFixture());

    $this->assertSame($optimized_context_tokens, $cache_contexts->optimizeTokens($context_tokens));
  }

  /**
   * Provides a list of context token sets.
   */
  public function providerTestOptimizeTokens() {
    return [
      [['a', 'x'], ['a', 'x']],
      [['a.b', 'x'], ['a.b', 'x']],

      // Direct ancestor, single-level hierarchy.
      [['a', 'a.b'], ['a']],
      [['a.b', 'a'], ['a']],

      // Direct ancestor, multi-level hierarchy.
      [['a.b', 'a.b.c'], ['a.b']],
      [['a.b.c', 'a.b'], ['a.b']],

      // Indirect ancestor.
      [['a', 'a.b.c'], ['a']],
      [['a.b.c', 'a'], ['a']],

      // Direct & indirect ancestors.
      [['a', 'a.b', 'a.b.c'], ['a']],
      [['a', 'a.b.c', 'a.b'], ['a']],
      [['a.b', 'a', 'a.b.c'], ['a']],
      [['a.b', 'a.b.c', 'a'], ['a']],
      [['a.b.c', 'a.b', 'a'], ['a']],
      [['a.b.c', 'a', 'a.b'], ['a']],

      // Using parameters.
      [['a', 'a.b.c:foo'], ['a']],
      [['a.b.c:foo', 'a'], ['a']],
      [['a.b.c:foo', 'a.b.c'], ['a.b.c']],
    ];
  }

  /**
   * @covers ::convertTokensToKeys
   */
  public function testConvertTokensToKeys() {
    $container = $this->getMockContainer();
    $cache_contexts = new CacheContexts($container, $this->getContextsFixture());

    $new_keys = $cache_contexts->convertTokensToKeys([
      'foo',
      'baz:parameterA',
      'baz:parameterB',
    ]);

    $expected = [
      'baz.cnenzrgreN',
      'baz.cnenzrgreO',
      'bar',
    ];
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

    $cache_contexts->convertTokensToKeys(["non-cache-context"]);
  }

  /**
   * @covers ::convertTokensToKeys
   *
   * @expectedException \Exception
   *
   * @dataProvider providerTestInvalidCalculatedContext
   */
  public function testInvalidCalculatedContext($context_token) {
    $container = $this->getMockContainer();
    $cache_contexts = new CacheContexts($container, $this->getContextsFixture());

    $cache_contexts->convertTokensToKeys([$context_token]);
  }

  /**
   * Provides a list of invalid 'baz' cache contexts: the parameter is missing.
   */
  public function providerTestInvalidCalculatedContext() {
    return [
      ['baz'],
      ['baz:'],
    ];
  }

  public function testAvailableContextStrings() {
    $cache_contexts = new CacheContexts($this->getMockContainer(), $this->getContextsFixture());
    $contexts = $cache_contexts->getAll();
    $this->assertEquals(array("foo", "baz"), $contexts);
  }

  public function testAvailableContextLabels() {
    $container = $this->getMockContainer();
    $cache_contexts = new CacheContexts($container, $this->getContextsFixture());
    $labels = $cache_contexts->getLabels();
    $expected = array("foo" => "Foo");
    $this->assertEquals($expected, $labels);
  }

  protected function getContextsFixture() {
    return array('foo', 'baz');
  }

  protected function getMockContainer() {
    $container = $this->getMockBuilder('Drupal\Core\DependencyInjection\Container')
      ->disableOriginalConstructor()
      ->getMock();
    $container->expects($this->any())
      ->method('get')
      ->will($this->returnValueMap([
        ['cache_context.foo', Container::EXCEPTION_ON_INVALID_REFERENCE, new FooCacheContext()],
        ['cache_context.baz', Container::EXCEPTION_ON_INVALID_REFERENCE, new BazCacheContext()],
      ]));
    return $container;
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

/**
 * Fake calculated cache context class.
 */
class BazCacheContext implements CalculatedCacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return 'Baz';
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($parameter = NULL) {
    if (!is_string($parameter) || strlen($parameter) ===  0) {
      throw new \Exception();
    }
    return 'baz.' . str_rot13($parameter);
  }

}
