<?php

namespace Drupal\Tests\path_alias\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Path\AliasManager as CoreAliasManager;
use Drupal\path_alias\AliasManager;
use Drupal\path_alias\Entity\PathAlias;
use Drupal\path_alias_deprecated_test\AliasManagerDecorator;
use Drupal\path_alias_deprecated_test\NewAliasManager;
use Drupal\path_alias_deprecated_test\OverriddenAliasManager;
use Drupal\path_alias_deprecated_test\PathAliasDeprecatedTestServiceProvider;

/**
 * Tests deprecation of path alias core services and the related BC logic.
 *
 * @group path_alias
 * @group legacy
 */
class DeprecatedServicesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['path_alias', 'path_alias_deprecated_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('path_alias');
  }

  /**
   * @expectedDeprecation The "path.alias_manager" service is deprecated. Use "path_alias.manager" instead. See https://drupal.org/node/3092086
   * @expectedDeprecation The "path_processor_alias" service is deprecated. Use "path_alias.path_processor" instead. See https://drupal.org/node/3092086
   * @expectedDeprecation The "path_subscriber" service is deprecated. Use "path_alias.subscriber" instead. See https://drupal.org/node/3092086
   */
  public function testAliasServicesDeprecation() {
    $this->container->get('path.alias_manager');
    $this->container->get('path_processor_alias');
    $this->container->get('path_subscriber');
  }

  /**
   * @expectedDeprecation The "path.alias_manager" service is deprecated. Use "path_alias.manager" instead. See https://drupal.org/node/3092086
   * @expectedDeprecation The \Drupal\Core\Path\AliasManager class is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Instead, use \Drupal\path_alias\AliasManager. See https://drupal.org/node/3092086
   */
  public function testOverriddenServiceImplementation() {
    $class = $this->setServiceClass(OverriddenAliasManager::class);
    $this->assertServiceClass('path.alias_manager', $class);
    $this->assertServiceClass('path_alias.manager', AliasManager::class);
  }

  /**
   * @expectedDeprecation The "path.alias_manager" service is deprecated. Use "path_alias.manager" instead. See https://drupal.org/node/3092086
   */
  public function testNewServiceImplementation() {
    $class = $this->setServiceClass(NewAliasManager::class);
    $this->assertServiceClass('path.alias_manager', $class);
    $this->assertServiceClass('path_alias.manager', AliasManager::class);
  }

  /**
   * @expectedDeprecation The "path_alias_deprecated_test.path.alias_manager.inner" service is deprecated. Use "path_alias.manager" instead. See https://drupal.org/node/3092086
   * @expectedDeprecation The \Drupal\Core\Path\AliasManager class is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Instead, use \Drupal\path_alias\AliasManager. See https://drupal.org/node/3092086
   */
  public function testDecoratorForOverriddenServiceImplementation() {
    $this->setServiceClass(OverriddenAliasManager::class, TRUE);
    $this->assertServiceClass('path.alias_manager', AliasManagerDecorator::class);
    $this->assertServiceClass('path_alias.manager', AliasManager::class);
  }

  /**
   * @expectedDeprecation The "path_alias_deprecated_test.path.alias_manager.inner" service is deprecated. Use "path_alias.manager" instead. See https://drupal.org/node/3092086
   */
  public function testDecoratorForNewServiceImplementation() {
    $this->setServiceClass(NewAliasManager::class, TRUE);
    $this->assertServiceClass('path.alias_manager', AliasManagerDecorator::class);
    $this->assertServiceClass('path_alias.manager', AliasManager::class);
  }

  /**
   * @expectedDeprecation The "path.alias_manager" service is deprecated. Use "path_alias.manager" instead. See https://drupal.org/node/3092086
   */
  public function testDefaultImplementations() {
    $this->assertServiceClass('path.alias_manager', CoreAliasManager::class);
    $this->assertServiceClass('path_alias.manager', AliasManager::class);
  }

  /**
   * No deprecation message expected.
   */
  public function testRegularImplementation() {
    $this->assertServiceClass('path_alias.manager', AliasManager::class);
  }

  /**
   * Test that the new alias manager and the legacy ones share the same state.
   *
   * @expectedDeprecation The \Drupal\Core\Path\AliasManager class is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Instead, use \Drupal\path_alias\AliasManager. See https://drupal.org/node/3092086
   */
  public function testAliasManagerSharedState() {
    /** @var \Drupal\Core\Path\AliasManager $legacy_alias_manager */
    $legacy_alias_manager = $this->container->get('path.alias_manager');
    /** @var \Drupal\path_alias\AliasManager $alias_manager */
    $alias_manager = $this->container->get('path_alias.manager');

    $cache_key = $this->randomMachineName();
    $alias_manager->setCacheKey($cache_key);
    $this->assertSharedProperty('preload-paths:' . $cache_key, $legacy_alias_manager, 'cacheKey');

    $invalid_alias = '/' . $this->randomMachineName();
    $alias_manager->getPathByAlias($invalid_alias);
    $this->assertSharedProperty(['en' => [$invalid_alias => TRUE]], $legacy_alias_manager, 'noPath');

    $this->assertSharedProperty(FALSE, $legacy_alias_manager, 'preloadedPathLookups');

    /** @var \Drupal\path_alias\Entity\PathAlias $alias */
    $alias = PathAlias::create([
      'path' => '/' . $this->randomMachineName(),
      'alias' => $invalid_alias . '2',
    ]);
    $alias->save();

    $this->assertSharedProperty([], $legacy_alias_manager, 'preloadedPathLookups');

    /** @var \Drupal\Core\State\StateInterface $state */
    $state = $this->container->get('state');
    $state->set('router.path_roots', [ltrim($alias->getPath(), '/')]);

    $alias_manager->getAliasByPath($alias->getPath());
    $this->assertSharedProperty(['en' => [$alias->getPath() => $alias->getAlias()]], $legacy_alias_manager, 'lookupMap');

    $invalid_path = $alias->getPath() . '/' . $this->randomMachineName();
    $alias_manager->getAliasByPath($invalid_path);
    $this->assertSharedProperty(['en' => [$invalid_path => TRUE]], $legacy_alias_manager, 'noAlias');
  }

  /**
   * Asserts that a shared property has the expected value.
   *
   * @param mixed $expected
   *   The property expected value.
   * @param \Drupal\Core\Path\AliasManager $legacy_alias_manager
   *   An instance of the legacy alias manager.
   * @param string $property
   *   The property name.
   */
  protected function assertSharedProperty($expected, CoreAliasManager $legacy_alias_manager, $property) {
    $reflector = new \ReflectionProperty(get_class($legacy_alias_manager), $property);
    $reflector->setAccessible(TRUE);
    $this->assertSame($expected, $reflector->getValue($legacy_alias_manager));
  }

  /**
   * Asserts that the specified service is implemented by the expected class.
   *
   * @param string $service_id
   *   A service ID.
   * @param string $expected_class
   *   The name of the expected class.
   */
  protected function assertServiceClass($service_id, $expected_class) {
    $service = $this->container->get($service_id);
    $this->assertSame(get_class($service), $expected_class);
  }

  /**
   * Sets the specified implementation for the service being tested.
   *
   * @param string $class
   *   The name of the implementation class.
   * @param bool $use_decorator
   *   (optional) Whether using a decorator service to wrap the specified class.
   *   Defaults to no decorator.
   *
   * @return string
   *   The specified class name.
   */
  protected function setServiceClass($class, $use_decorator = FALSE) {
    PathAliasDeprecatedTestServiceProvider::$newClass = $class;
    PathAliasDeprecatedTestServiceProvider::$useDecorator = $use_decorator;
    $this->container->get('kernel')->rebuildContainer();
    return $class;
  }

}
