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
      'bar',
      'baz.cnenzrgreN',
      'baz.cnenzrgreO',
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
  public function getContext($parameter) {
    if (!is_string($parameter) || strlen($parameter) ===  0) {
      throw new \Exception();
    }
    return 'baz.' . str_rot13($parameter);
  }

}
