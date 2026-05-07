<?php

declare(strict_types=1);

namespace Drupal\Tests;

use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Provides a base class and helpers for Drupal unit tests.
 *
 * Module tests extending UnitTestCase must exist in the
 * Drupal\Tests\your_module\Unit namespace and live in the
 * modules/your_module/tests/src/Unit directory.
 *
 * Tests for core/lib/Drupal classes extending UnitTestCase must exist in the
 * \Drupal\Tests\Core namespace and live in the core/lib/tests/Drupal/Tests/Core
 * directory.
 *
 * Using Symfony's dump() function in Unit tests will produce output on the
 * command line.
 *
 * @ingroup testing
 */
abstract class UnitTestCase extends TestCase {

  use DrupalTestCaseTrait;
  use ProphecyTrait;
  use RandomGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Ensure that an instantiated container in the global state of \Drupal from
    // a previous test does not leak into this test.
    \Drupal::unsetContainer();

    // Ensure that the NullFileCache implementation is used for the FileCache as
    // unit tests should not be relying on caches implicitly.
    FileCacheFactory::setConfiguration([FileCacheFactory::DISABLE_CACHE => TRUE]);
    // Ensure that FileCacheFactory has a prefix.
    FileCacheFactory::setPrefix('prefix');

    chdir($this->root);
  }

  /**
   * Returns a stub config factory that behaves according to the passed array.
   *
   * Use this to generate a config factory that will return the desired values
   * for the given config names.
   *
   * @param array $configs
   *   An associative array of configuration settings whose keys are
   *   configuration object names and whose values are key => value arrays for
   *   the configuration object in question. Defaults to an empty array.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\Stub
   *   A stub configuration factory object.
   */
  public function getConfigFactoryStub(array $configs = []) {
    $config_get_map = [];
    $config_editable_map = [];
    // Construct the desired configuration object stubs, each with its own
    // desired return map.
    foreach ($configs as $config_name => $config_values) {
      // Define a closure over the $config_values, which will be used as a
      // returnCallback below. This function will mimic
      // \Drupal\Core\Config\Config::get and allow using dotted keys.
      $config_get = function ($key = '') use ($config_values) {
        // Allow to pass in no argument.
        if (empty($key)) {
          return $config_values;
        }
        // See if we have the key as is.
        if (isset($config_values[$key])) {
          return $config_values[$key];
        }
        $parts = explode('.', $key);
        $value = NestedArray::getValue($config_values, $parts, $key_exists);
        return $key_exists ? $value : NULL;
      };

      $immutable_config_object = $this->createStub(ImmutableConfig::class);
      $immutable_config_object
        ->method('get')
        ->willReturnCallback($config_get);
      $config_get_map[] = [$config_name, $immutable_config_object];

      $mutable_config_object = $this->createStub(Config::class);
      $mutable_config_object
        ->method('get')
        ->willReturnCallback($config_get);
      $config_editable_map[] = [$config_name, $mutable_config_object];
    }
    // Construct a config factory with the array of configuration object stubs
    // as its return map.
    $config_factory = $this->createStub(ConfigFactoryInterface::class);
    $config_factory
      ->method('get')
      ->willReturnMap($config_get_map);
    $config_factory
      ->method('getEditable')
      ->willReturnMap($config_editable_map);
    return $config_factory;
  }

  /**
   * Returns a stub translation manager that just returns the passed string.
   *
   * @return \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\Stub
   *   A stub translation object.
   */
  public function getStringTranslationStub() {
    $translation = $this->createStub('Drupal\Core\StringTranslation\TranslationInterface');
    $translation
      ->method('translate')
      ->willReturnCallback(function ($string, array $args = [], array $options = []) use ($translation): TranslatableMarkup {
        // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
        return new TranslatableMarkup($string, $args, $options, $translation);
      });
    $translation
      ->method('translateString')
      ->willReturnCallback(function (TranslatableMarkup $wrapper) {
        return $wrapper->getUntranslatedString();
      });
    $translation
      ->method('formatPlural')
      ->willReturnCallback(function ($count, $singular, $plural, array $args = [], array $options = []) use ($translation): PluralTranslatableMarkup {
        $wrapper = new PluralTranslatableMarkup($count, $singular, $plural, $args, $options, $translation);
        return $wrapper;
      });
    return $translation;
  }

  /**
   * Sets up a container with a cache tags invalidator.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_validator
   *   The cache tags invalidator.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface|\PHPUnit\Framework\MockObject\MockObject
   *   The container with the cache tags invalidator service.
   */
  protected function getContainerWithCacheTagsInvalidator(CacheTagsInvalidatorInterface $cache_tags_validator) {
    $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
    $container->expects($this->atLeastOnce())
      ->method('get')
      ->with('cache_tags.invalidator')
      ->willReturn($cache_tags_validator);

    \Drupal::setContainer($container);
    return $container;
  }

  /**
   * Returns a stub class resolver.
   *
   * @return \Drupal\Core\DependencyInjection\ClassResolverInterface|\PHPUnit\Framework\MockObject\MockObject
   *   The class resolver stub.
   */
  protected function getClassResolverStub() {
    $class_resolver = $this->createStub(ClassResolverInterface::class);
    $class_resolver
      ->method('getInstanceFromDefinition')
      ->willReturnCallback(function ($class) {
        if (is_subclass_of($class, 'Drupal\Core\DependencyInjection\ContainerInjectionInterface')) {
          return $class::create(new ContainerBuilder());
        }
        else {
          return new $class();
        }
      });
    return $class_resolver;
  }

  /**
   * Set up a traversable class mock to return specific items when iterated.
   *
   * Test doubles for types extending \Traversable are required to implement
   * \Iterator which requires setting up five methods. Instead, this helper
   * can be used.
   *
   * @param \PHPUnit\Framework\MockObject\Stub&\Iterator $mock
   *   A mocked object mocking a traversable class.
   * @param array $items
   *   The items to return when this object is iterated.
   *
   * @return \PHPUnit\Framework\MockObject\Stub&\Iterator
   *   The same mocked object ready to be iterated.
   *
   * @template T of \PHPUnit\Framework\MockObject\Stub&\Iterator
   * @phpstan-param T $mock
   * @phpstan-return T
   * @see https://github.com/sebastianbergmann/phpunit-mock-objects/issues/103
   */
  protected function setupMockIterator(Stub&\Iterator $mock, array $items): Stub&\Iterator {
    $iterator = new \ArrayIterator($items);
    foreach (get_class_methods(\Iterator::class) as $method) {
      $mock->method($method)->willReturnCallback([$iterator, $method]);
    }
    return $mock;
  }

}
