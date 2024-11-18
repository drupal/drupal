<?php

declare(strict_types=1);

namespace Drupal\Tests;

use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\TestTools\Extension\DeprecationBridge\ExpectDeprecationTrait;
use Drupal\TestTools\TestVarDumper;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\VarDumper\VarDumper;

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

  use PhpUnitCompatibilityTrait;
  use ProphecyTrait;
  use ExpectDeprecationTrait;
  use RandomGeneratorTrait;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    VarDumper::setHandler(TestVarDumper::class . '::cliHandler');
  }

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

    $this->root = dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__)), 2);
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
   * @return \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Config\ConfigFactoryInterface
   *   A mock configuration factory object.
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

      $immutable_config_object = $this->getMockBuilder('Drupal\Core\Config\ImmutableConfig')
        ->disableOriginalConstructor()
        ->getMock();
      $immutable_config_object->expects($this->any())
        ->method('get')
        ->willReturnCallback($config_get);
      $config_get_map[] = [$config_name, $immutable_config_object];

      $mutable_config_object = $this->getMockBuilder('Drupal\Core\Config\Config')
        ->disableOriginalConstructor()
        ->getMock();
      $mutable_config_object->expects($this->any())
        ->method('get')
        ->willReturnCallback($config_get);
      $config_editable_map[] = [$config_name, $mutable_config_object];
    }
    // Construct a config factory with the array of configuration object stubs
    // as its return map.
    $config_factory = $this->createMock('Drupal\Core\Config\ConfigFactoryInterface');
    $config_factory->expects($this->any())
      ->method('get')
      ->willReturnMap($config_get_map);
    $config_factory->expects($this->any())
      ->method('getEditable')
      ->willReturnMap($config_editable_map);
    return $config_factory;
  }

  /**
   * Returns a stub config storage that returns the supplied configuration.
   *
   * @param array $configs
   *   An associative array of configuration settings whose keys are
   *   configuration object names and whose values are key => value arrays
   *   for the configuration object in question.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   A mocked config storage.
   */
  public function getConfigStorageStub(array $configs) {
    $config_storage = $this->createMock('Drupal\Core\Config\NullStorage');
    $config_storage->expects($this->any())
      ->method('listAll')
      ->willReturn(array_keys($configs));

    foreach ($configs as $name => $config) {
      $config_storage->expects($this->any())
        ->method('read')
        ->with($this->equalTo($name))
        ->willReturn($config);
    }
    return $config_storage;
  }

  /**
   * Returns a stub translation manager that just returns the passed string.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\StringTranslation\TranslationInterface
   *   A mock translation object.
   */
  public function getStringTranslationStub() {
    $translation = $this->createMock('Drupal\Core\StringTranslation\TranslationInterface');
    $translation->expects($this->any())
      ->method('translate')
      ->willReturnCallback(function ($string, array $args = [], array $options = []) use ($translation) {
        return new TranslatableMarkup($string, $args, $options, $translation);
      });
    $translation->expects($this->any())
      ->method('translateString')
      ->willReturnCallback(function (TranslatableMarkup $wrapper) {
        return $wrapper->getUntranslatedString();
      });
    $translation->expects($this->any())
      ->method('formatPlural')
      ->willReturnCallback(function ($count, $singular, $plural, array $args = [], array $options = []) use ($translation) {
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
    $container->expects($this->any())
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
    $class_resolver = $this->createMock('Drupal\Core\DependencyInjection\ClassResolverInterface');
    $class_resolver->expects($this->any())
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

}
