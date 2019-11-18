<?php

namespace Drupal\Tests\path_alias\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\EventSubscriber\PathSubscriber;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Path\AliasManager as CoreAliasManager;
use Drupal\Core\Path\AliasManagerInterface as CoreAliasManagerInterface;
use Drupal\Core\Path\AliasWhitelist as CoreAliasWhitelist;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\PathProcessor\PathProcessorAlias;
use Drupal\Core\State\StateInterface;
use Drupal\path_alias\AliasManager;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\path_alias\AliasRepositoryInterface;
use Drupal\path_alias\AliasWhitelist;
use Drupal\path_alias\AliasWhitelistInterface;
use Drupal\path_alias\EventSubscriber\PathAliasSubscriber;
use Drupal\path_alias\PathProcessor\AliasPathProcessor;
use Drupal\system\Form\SiteInformationForm;
use Drupal\system\Plugin\Condition\RequestPath;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\argument_default\Raw;

/**
 * Tests deprecation of path alias core service classes.
 *
 * @group path_alias
 * @group legacy
 */
class DeprecatedClassesTest extends UnitTestCase {

  /**
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * @var \Drupal\path_alias\AliasRepositoryInterface
   */
  protected $aliasRepository;

  /**
   * @var \Drupal\path_alias\AliasWhitelistInterface
   */
  protected $aliasWhitelist;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->aliasManager = $this->prophesize(AliasManagerInterface::class)
      ->reveal();
    $this->aliasRepository = $this->prophesize(AliasRepositoryInterface::class)
      ->reveal();
    $this->aliasWhitelist = $this->prophesize(AliasWhitelistInterface::class)
      ->reveal();
    $this->cache = $this->prophesize(CacheBackendInterface::class)
      ->reveal();
    $this->currentPathStack = $this->prophesize(CurrentPathStack::class)
      ->reveal();
    $this->languageManager = $this->prophesize(LanguageManagerInterface::class)
      ->reveal();
    $this->lock = $this->prophesize(LockBackendInterface::class)
      ->reveal();
    $this->state = $this->prophesize(StateInterface::class)
      ->reveal();

    /** @var \Prophecy\Prophecy\ObjectProphecy $container */
    $container = $this->prophesize(ContainerBuilder::class);
    $container->get('path_alias.manager')
      ->willReturn($this->aliasManager);
    \Drupal::setContainer($container->reveal());
  }

  /**
   * @covers \Drupal\Core\EventSubscriber\PathSubscriber::__construct
   *
   * @expectedDeprecation The \Drupal\Core\EventSubscriber\PathSubscriber class is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Instead, use \Drupal\path_alias\EventSubscriber\PathAliasSubscriber. See https://drupal.org/node/3092086
   */
  public function testPathSubscriber() {
    new PathSubscriber($this->aliasManager, $this->currentPathStack);
  }

  /**
   * @covers \Drupal\path_alias\EventSubscriber\PathAliasSubscriber::__construct
   */
  public function testPathAliasSubscriber() {
    $object = new PathAliasSubscriber($this->aliasManager, $this->currentPathStack);
    $this->assertInstanceOf(PathSubscriber::class, $object);
  }

  /**
   * @covers \Drupal\Core\PathProcessor\PathProcessorAlias::__construct
   *
   * @expectedDeprecation The \Drupal\Core\PathProcessor\PathProcessorAlias class is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Instead, use \Drupal\path_alias\PathProcessor\AliasPathProcessor. See https://drupal.org/node/3092086
   */
  public function testPathProcessorAlias() {
    new PathProcessorAlias($this->aliasManager);
  }

  /**
   * @covers \Drupal\path_alias\PathProcessor\AliasPathProcessor::__construct
   */
  public function testAliasPathProcessor() {
    $object = new AliasPathProcessor($this->aliasManager);
    $this->assertInstanceOf(PathProcessorAlias::class, $object);
  }

  /**
   * @covers \Drupal\Core\Path\AliasManager::__construct
   *
   * @expectedDeprecation The \Drupal\Core\Path\AliasManager class is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Instead, use \Drupal\path_alias\AliasManager. See https://drupal.org/node/3092086
   */
  public function testCoreAliasManager() {
    new CoreAliasManager($this->aliasRepository, $this->aliasWhitelist, $this->languageManager, $this->cache);
  }

  /**
   * @covers \Drupal\path_alias\AliasManager::__construct
   */
  public function testAliasManager() {
    $object = new AliasManager($this->aliasRepository, $this->aliasWhitelist, $this->languageManager, $this->cache);
    $this->assertInstanceOf(CoreAliasManager::class, $object);
  }

  /**
   * @covers \Drupal\Core\Path\AliasWhitelist::__construct
   *
   * @expectedDeprecation The \Drupal\Core\Path\AliasWhitelist class is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Instead, use \Drupal\path_alias\AliasWhitelist. See https://drupal.org/node/3092086
   */
  public function testCoreAliasWhitelist() {
    new CoreAliasWhitelist('path_alias_whitelist', $this->cache, $this->lock, $this->state, $this->aliasRepository);
  }

  /**
   * @covers \Drupal\path_alias\AliasWhitelist::__construct
   */
  public function testAliasWhitelist() {
    $object = new AliasWhitelist('path_alias_whitelist', $this->cache, $this->lock, $this->state, $this->aliasRepository);
    $this->assertInstanceOf(CoreAliasWhitelist::class, $object);
  }

  /**
   * @covers \Drupal\system\Form\SiteInformationForm::__construct
   *
   * @expectedDeprecation Calling \Drupal\system\Form\SiteInformationForm::__construct with \Drupal\Core\Path\AliasManagerInterface instead of \Drupal\path_alias\AliasManagerInterface is deprecated in drupal:8.8.0. The new service will be required in drupal:9.0.0. See https://www.drupal.org/node/3092086
   */
  public function testDeprecatedSystemInformationFormConstructorParameters() {
    $this->assertDeprecatedConstructorParameter(SiteInformationForm::class);
  }

  /**
   * @covers \Drupal\system\Plugin\Condition\RequestPath::__construct
   *
   * @expectedDeprecation Calling \Drupal\system\Plugin\Condition\RequestPath::__construct with \Drupal\Core\Path\AliasManagerInterface instead of \Drupal\path_alias\AliasManagerInterface is deprecated in drupal:8.8.0. The new service will be required in drupal:9.0.0. See https://www.drupal.org/node/3092086
   */
  public function testDeprecatedRequestPathConstructorParameters() {
    $this->assertDeprecatedConstructorParameter(RequestPath::class);
  }

  /**
   * @covers \Drupal\views\Plugin\views\argument_default\Raw::__construct
   *
   * @expectedDeprecation Calling \Drupal\views\Plugin\views\argument_default\Raw::__construct with \Drupal\Core\Path\AliasManagerInterface instead of \Drupal\path_alias\AliasManagerInterface is deprecated in drupal:8.8.0. The new service will be required in drupal:9.0.0. See https://www.drupal.org/node/3092086
   */
  public function testDeprecatedRawConstructorParameters() {
    $this->assertDeprecatedConstructorParameter(Raw::class);
  }

  /**
   * Test that deprecation for the \Drupal\Core\Path\AliasManagerInterface.
   *
   * @param string $tested_class_name
   *   The name of the tested class.
   *
   * @dataProvider deprecatedConstructorParametersProvider
   *
   * @expectedDeprecation The \Drupal\Core\Path\AliasManagerInterface interface is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Instead, use \Drupal\path_alias\AliasManagerInterface. See https://drupal.org/node/3092086
   */
  public function assertDeprecatedConstructorParameter($tested_class_name) {
    $tested_class = new \ReflectionClass($tested_class_name);
    $parameters = $tested_class->getConstructor()
      ->getParameters();

    $args = [];
    foreach ($parameters as $parameter) {
      $name = $parameter->getName();
      if ($name === 'alias_manager') {
        $class_name = CoreAliasManagerInterface::class;
      }
      else {
        $type = $parameter->getType();
        $class_name = $type ? (string) $type : NULL;
      }
      $args[$name] = isset($class_name) && $class_name !== 'array' ? $this->prophesize($class_name)->reveal() : [];
    }

    $instance = $tested_class->newInstanceArgs($args);
    $property = $tested_class->getProperty('aliasManager');
    $property->setAccessible(TRUE);
    $this->assertInstanceOf(AliasManagerInterface::class, $property->getValue($instance));
  }

}
