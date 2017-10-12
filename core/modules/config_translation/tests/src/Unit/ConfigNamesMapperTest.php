<?php

/**
 * @file
 * Contains \Drupal\Tests\config_translation\Unit\ConfigNamesMapperTest.
 */

namespace Drupal\Tests\config_translation\Unit;

use Drupal\config_translation\ConfigNamesMapper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * Tests the functionality provided by the configuration names mapper.
 *
 * @group config_translation
 */
class ConfigNamesMapperTest extends UnitTestCase {

  /**
   * The plugin definition of the test mapper.
   *
   * @var array
   */
  protected $pluginDefinition;

  /**
   * The configuration names mapper to test.
   *
   * @see \Drupal\config_translation\ConfigNamesMapper
   *
   * @var \Drupal\Tests\config_translation\Unit\TestConfigNamesMapper
   */
  protected $configNamesMapper;

  /**
   * The locale configuration manager.
   *
   * @var \Drupal\locale\LocaleConfigManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $localeConfigManager;

  /**
   * The locale configuration manager.
   *
   * @var \Drupal\locale\LocaleConfigManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $typedConfigManager;

  /**
   * The configuration mapper manager.
   *
   * @var \Drupal\config_translation\ConfigMapperManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configMapperManager;

  /**
   * The base route used for testing.
   *
   * @var \Symfony\Component\Routing\Route
   */
  protected $baseRoute;

  /**
   * The route provider used for testing.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $routeProvider;

  /**
   * The mocked URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $urlGenerator;

  /**
   * The mocked language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  protected function setUp() {
    $this->routeProvider = $this->getMock('Drupal\Core\Routing\RouteProviderInterface');

    $this->pluginDefinition = [
      'class' => '\Drupal\config_translation\ConfigNamesMapper',
      'base_route_name' => 'system.site_information_settings',
      'title' => 'System information',
      'names' => ['system.site'],
      'weight' => 42,
    ];

    $this->typedConfigManager = $this->getMock('Drupal\Core\Config\TypedConfigManagerInterface');

    $this->localeConfigManager = $this->getMockBuilder('Drupal\locale\LocaleConfigManager')
      ->disableOriginalConstructor()
      ->getMock();

    $this->configMapperManager = $this->getMock('Drupal\config_translation\ConfigMapperManagerInterface');

    $this->urlGenerator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');
    $container = new ContainerBuilder();
    $container->set('url_generator', $this->urlGenerator);
    \Drupal::setContainer($container);

    $this->baseRoute = new Route('/admin/config/system/site-information');

    $this->routeProvider
      ->expects($this->any())
      ->method('getRouteByName')
      ->with('system.site_information_settings')
      ->will($this->returnValue($this->baseRoute));

    $this->languageManager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');

    $this->configNamesMapper = new TestConfigNamesMapper(
      'system.site_information_settings',
      $this->pluginDefinition,
      $this->getConfigFactoryStub(),
      $this->typedConfigManager,
      $this->localeConfigManager,
      $this->configMapperManager,
      $this->routeProvider,
      $this->getStringTranslationStub(),
      $this->languageManager
    );
  }

  /**
   * Tests ConfigNamesMapper::getTitle().
   */
  public function testGetTitle() {
    $result = $this->configNamesMapper->getTitle();
    $this->assertSame($this->pluginDefinition['title'], (string) $result);
  }

  /**
   * Tests ConfigNamesMapper::getBaseRouteName().
   */
  public function testGetBaseRouteName() {
    $result = $this->configNamesMapper->getBaseRouteName();
    $this->assertSame($this->pluginDefinition['base_route_name'], $result);
  }

  /**
   * Tests ConfigNamesMapper::getBaseRouteParameters().
   */
  public function testGetBaseRouteParameters() {
    $result = $this->configNamesMapper->getBaseRouteParameters();
    $this->assertSame([], $result);
  }

  /**
   * Tests ConfigNamesMapper::getBaseRoute().
   */
  public function testGetBaseRoute() {
    $result = $this->configNamesMapper->getBaseRoute();
    $this->assertSame($this->baseRoute, $result);
  }

  /**
   * Tests ConfigNamesMapper::getBasePath().
   */
  public function testGetBasePath() {
    $this->urlGenerator->expects($this->once())
      ->method('getPathFromRoute')
      ->with('system.site_information_settings', [])
      ->willReturn('/admin/config/system/site-information');
    $result = $this->configNamesMapper->getBasePath();
    $this->assertSame('/admin/config/system/site-information', $result);
  }

  /**
   * Tests ConfigNamesMapper::getOverviewRouteName().
   */
  public function testGetOverviewRouteName() {
    $result = $this->configNamesMapper->getOverviewRouteName();
    $expected = 'config_translation.item.overview.' . $this->pluginDefinition['base_route_name'];
    $this->assertSame($expected, $result);
  }

  /**
   * Tests ConfigNamesMapper::getOverviewRouteParameters().
   */
  public function testGetOverviewRouteParameters() {
    $result = $this->configNamesMapper->getOverviewRouteParameters();
    $this->assertSame([], $result);
  }

  /**
   * Tests ConfigNamesMapper::getOverviewRoute().
   */
  public function testGetOverviewRoute() {
    $expected = new Route('/admin/config/system/site-information/translate',
      [
        '_controller' => '\Drupal\config_translation\Controller\ConfigTranslationController::itemPage',
        'plugin_id' => 'system.site_information_settings',
      ],
      [
        '_config_translation_overview_access' => 'TRUE',
      ]
    );
    $result = $this->configNamesMapper->getOverviewRoute();
    $this->assertSame(serialize($expected), serialize($result));
  }

  /**
   * Tests ConfigNamesMapper::getOverviewPath().
   */
  public function testGetOverviewPath() {
    $this->urlGenerator->expects($this->once())
      ->method('getPathFromRoute')
      ->with('config_translation.item.overview.system.site_information_settings', [])
      ->willReturn('/admin/config/system/site-information/translate');

    $result = $this->configNamesMapper->getOverviewPath();
    $this->assertSame('/admin/config/system/site-information/translate', $result);
  }

  /**
   * Tests ConfigNamesMapper::getAddRouteName().
   */
  public function testGetAddRouteName() {
    $result = $this->configNamesMapper->getAddRouteName();
    $expected = 'config_translation.item.add.' . $this->pluginDefinition['base_route_name'];
    $this->assertSame($expected, $result);
  }

  /**
   * Tests ConfigNamesMapper::getAddRouteParameters().
   */
  public function testGetAddRouteParameters() {
    $route_match = new RouteMatch('example', new Route('/test/{langcode}'), ['langcode' => 'xx']);
    $this->configNamesMapper->populateFromRouteMatch($route_match);

    $expected = ['langcode' => 'xx'];
    $result = $this->configNamesMapper->getAddRouteParameters();
    $this->assertSame($expected, $result);
  }

  /**
   * Tests ConfigNamesMapper::getAddRoute().
   */
  public function testGetAddRoute() {
    $expected = new Route('/admin/config/system/site-information/translate/{langcode}/add',
      [
        '_form' => '\Drupal\config_translation\Form\ConfigTranslationAddForm',
        'plugin_id' => 'system.site_information_settings',
      ],
      [
        '_config_translation_form_access' => 'TRUE',
      ]
    );
    $result = $this->configNamesMapper->getAddRoute();
    $this->assertSame(serialize($expected), serialize($result));
  }

  /**
   * Tests ConfigNamesMapper::getEditRouteName().
   */
  public function testGetEditRouteName() {
    $result = $this->configNamesMapper->getEditRouteName();
    $expected = 'config_translation.item.edit.' . $this->pluginDefinition['base_route_name'];
    $this->assertSame($expected, $result);
  }

  /**
   * Tests ConfigNamesMapper::getEditRouteParameters().
   */
  public function testGetEditRouteParameters() {
    $route_match = new RouteMatch('example', new Route('/test/{langcode}'), ['langcode' => 'xx']);
    $this->configNamesMapper->populateFromRouteMatch($route_match);

    $expected = ['langcode' => 'xx'];
    $result = $this->configNamesMapper->getEditRouteParameters();
    $this->assertSame($expected, $result);
  }

  /**
   * Tests ConfigNamesMapper::getEditRoute().
   */
  public function testGetEditRoute() {
    $expected = new Route('/admin/config/system/site-information/translate/{langcode}/edit',
      [
        '_form' => '\Drupal\config_translation\Form\ConfigTranslationEditForm',
        'plugin_id' => 'system.site_information_settings',
      ],
      [
        '_config_translation_form_access' => 'TRUE',
      ]
    );
    $result = $this->configNamesMapper->getEditRoute();
    $this->assertSame(serialize($expected), serialize($result));
  }

  /**
   * Tests ConfigNamesMapper::getDeleteRouteName().
   */
  public function testGetDeleteRouteName() {
    $result = $this->configNamesMapper->getDeleteRouteName();
    $expected = 'config_translation.item.delete.' . $this->pluginDefinition['base_route_name'];
    $this->assertSame($expected, $result);
  }

  /**
   * Tests ConfigNamesMapper::getDeleteRouteParameters().
   */
  public function testGetDeleteRouteParameters() {
    $route_match = new RouteMatch('example', new Route('/test/{langcode}'), ['langcode' => 'xx']);
    $this->configNamesMapper->populateFromRouteMatch($route_match);

    $expected = ['langcode' => 'xx'];    $result = $this->configNamesMapper->getDeleteRouteParameters();
    $this->assertSame($expected, $result);
  }

  /**
   * Tests ConfigNamesMapper::getRoute().
   */
  public function testGetDeleteRoute() {
    $expected = new Route('/admin/config/system/site-information/translate/{langcode}/delete',
      [
        '_form' => '\Drupal\config_translation\Form\ConfigTranslationDeleteForm',
        'plugin_id' => 'system.site_information_settings',
      ],
      [
        '_config_translation_form_access' => 'TRUE',
      ]
    );
    $result = $this->configNamesMapper->getDeleteRoute();
    $this->assertSame(serialize($expected), serialize($result));
  }

  /**
   * Tests ConfigNamesMapper::getConfigNames().
   */
  public function testGetConfigNames() {
    $result = $this->configNamesMapper->getConfigNames();
    $this->assertSame($this->pluginDefinition['names'], $result);
  }

  /**
   * Tests ConfigNamesMapper::addConfigName().
   */
  public function testAddConfigName() {
    $names = $this->configNamesMapper->getConfigNames();
    $this->configNamesMapper->addConfigName('test');
    $names[] = 'test';
    $result = $this->configNamesMapper->getConfigNames();
    $this->assertSame($names, $result);
  }

  /**
   * Tests ConfigNamesMapper::getWeight().
   */
  public function testGetWeight() {
    $result = $this->configNamesMapper->getWeight();
    $this->assertSame($this->pluginDefinition['weight'], $result);
  }

  /**
   * Tests ConfigNamesMapper::populateFromRouteMatch().
   */
  public function testPopulateFromRouteMatch() {
    // Make sure the language code is not set initially.
    $this->assertSame(NULL, $this->configNamesMapper->getInternalLangcode());

    // Test that an empty request does not set the language code.
    $route_match = new RouteMatch('example', new Route('/test/{langcode}'));
    $this->configNamesMapper->populateFromRouteMatch($route_match);
    $this->assertSame(NULL, $this->configNamesMapper->getInternalLangcode());

    // Test that a request with a 'langcode' attribute sets the language code.
    $route_match = new RouteMatch('example', new Route('/test/{langcode}'), ['langcode' => 'xx']);
    $this->configNamesMapper->populateFromRouteMatch($route_match);
    $this->assertSame('xx', $this->configNamesMapper->getInternalLangcode());

    // Test that the language code gets unset with the wrong request.
    $route_match = new RouteMatch('example', new Route('/test/{langcode}'));
    $this->configNamesMapper->populateFromRouteMatch($route_match);
    $this->assertSame(NULL, $this->configNamesMapper->getInternalLangcode());
  }

  /**
   * Tests ConfigNamesMapper::getTypeLabel().
   */
  public function testGetTypeLabel() {
    $result = $this->configNamesMapper->getTypeLabel();
    $this->assertSame($this->pluginDefinition['title'], (string) $result);
  }

  /**
   * Tests ConfigNamesMapper::getLangcode().
   */
  public function testGetLangcode() {
    // Test that the getLangcode() falls back to 'en', if no explicit language
    // code is provided.
    $config_factory = $this->getConfigFactoryStub([
      'system.site' => ['key' => 'value'],
    ]);
    $this->configNamesMapper->setConfigFactory($config_factory);
    $result = $this->configNamesMapper->getLangcode();
    $this->assertSame('en', $result);

    // Test that getLangcode picks up the language code provided by the
    // configuration.
    $config_factory = $this->getConfigFactoryStub([
      'system.site' => ['langcode' => 'xx'],
    ]);
    $this->configNamesMapper->setConfigFactory($config_factory);
    $result = $this->configNamesMapper->getLangcode();
    $this->assertSame('xx', $result);

    // Test that getLangcode() works for multiple configuration names.
    $this->configNamesMapper->addConfigName('system.maintenance');
    $config_factory = $this->getConfigFactoryStub([
      'system.site' => ['langcode' => 'xx'],
      'system.maintenance' => ['langcode' => 'xx'],
    ]);
    $this->configNamesMapper->setConfigFactory($config_factory);
    $result = $this->configNamesMapper->getLangcode();
    $this->assertSame('xx', $result);

    // Test that getLangcode() throws an exception when different language codes
    // are given.
    $config_factory = $this->getConfigFactoryStub([
      'system.site' => ['langcode' => 'xx'],
      'system.maintenance' => ['langcode' => 'yy'],
    ]);
    $this->configNamesMapper->setConfigFactory($config_factory);
    try {
      $this->configNamesMapper->getLangcode();
      $this->fail();
    }
    catch (\RuntimeException $e) {
    }
  }

  /**
   * Tests ConfigNamesMapper::getConfigData().
   */
  public function testGetConfigData() {
    $configs = [
      'system.site' => [
        'name' => 'Drupal',
        'slogan' => 'Come for the software, stay for the community!',
      ],
      'system.maintenance' => [
        'enabled' => FALSE,
        'message' => '@site is currently under maintenance.',
      ],
      'system.rss' => [
        'items' => [
          'limit' => 10,
          'view_mode' => 'rss',
        ],
      ],
    ];

    $this->configNamesMapper->setConfigNames(array_keys($configs));
    $config_factory = $this->getConfigFactoryStub($configs);
    $this->configNamesMapper->setConfigFactory($config_factory);

    $result = $this->configNamesMapper->getConfigData();
    $this->assertSame($configs, $result);
  }

  /**
   * Tests ConfigNamesMapper::hasSchema().
   *
   * @param array $mock_return_values
   *   An array of values that the mocked locale configuration manager should
   *   return for hasConfigSchema().
   * @param bool $expected
   *   The expected return value of ConfigNamesMapper::hasSchema().
   *
   * @dataProvider providerTestHasSchema
   */
  public function testHasSchema(array $mock_return_values, $expected) {
    // As the configuration names are arbitrary, simply use integers.
    $config_names = range(1, count($mock_return_values));
    $this->configNamesMapper->setConfigNames($config_names);

    $map = [];
    foreach ($config_names as $i => $config_name) {
      $map[] = [$config_name, $mock_return_values[$i]];
    }
    $this->typedConfigManager
      ->expects($this->any())
      ->method('hasConfigSchema')
      ->will($this->returnValueMap($map));

    $result = $this->configNamesMapper->hasSchema();
    $this->assertSame($expected, $result);
  }

  /**
   * Provides data for ConfigMapperTest::testHasSchema().
   *
   * @return array
   *   An array of arrays, where each inner array has an array of values that
   *   the mocked locale configuration manager should return for
   *   hasConfigSchema() as the first value and the expected return value of
   *   ConfigNamesMapper::hasSchema() as the second value.
   */
  public function providerTestHasSchema() {
    return [
      [[TRUE], TRUE],
      [[FALSE], FALSE],
      [[TRUE, TRUE, TRUE], TRUE],
      [[TRUE, FALSE, TRUE], FALSE],
    ];
  }

  /**
   * Tests ConfigNamesMapper::hasTranslatable().
   *
   * @param array $mock_return_values
   *   An array of values that the mocked configuration mapper manager should
   *   return for hasTranslatable().
   * @param bool $expected
   *   The expected return value of ConfigNamesMapper::hasTranslatable().
   *
   * @dataProvider providerTestHasTranslatable
   */
  public function testHasTranslatable(array $mock_return_values, $expected) {
    // As the configuration names are arbitrary, simply use integers.
    $config_names = range(1, count($mock_return_values));
    $this->configNamesMapper->setConfigNames($config_names);

    $map = [];
    foreach ($config_names as $i => $config_name) {
      $map[] = isset($mock_return_values[$i]) ? [$config_name, $mock_return_values[$i]] : [];
    }
    $this->configMapperManager
      ->expects($this->any())
      ->method('hasTranslatable')
      ->will($this->returnValueMap($map));

    $result = $this->configNamesMapper->hasTranslatable();
    $this->assertSame($expected, $result);
  }

  /**
   * Provides data for ConfigNamesMapperTest::testHasTranslatable().
   *
   * @return array
   *   An array of arrays, where each inner array has an array of values that
   *   the mocked configuration mapper manager should return for
   *   hasTranslatable() as the first value and the expected return value of
   *   ConfigNamesMapper::hasTranslatable() as the second value.
   */
  public function providerTestHasTranslatable() {
    return [
      [[], FALSE],
      [[TRUE], TRUE],
      [[FALSE], FALSE],
      [[TRUE, TRUE, TRUE], TRUE],
      [[FALSE, FALSE, FALSE], FALSE],
      [[TRUE, FALSE, TRUE], TRUE],
    ];
  }

  /**
   * Tests ConfigNamesMapper::hasTranslation().
   *
   * @param array $mock_return_values
   *   An array of values that the mocked configuration mapper manager should
   *   return for hasTranslation().
   * @param bool $expected
   *   The expected return value of ConfigNamesMapper::hasTranslation().
   *
   * @dataProvider providerTestHasTranslation
   */
  public function testHasTranslation(array $mock_return_values, $expected) {
    $language = new Language();

    // As the configuration names are arbitrary, simply use integers.
    $config_names = range(1, count($mock_return_values));
    $this->configNamesMapper->setConfigNames($config_names);

    $map = [];
    foreach ($config_names as $i => $config_name) {
      $map[] = [$config_name, $language->getId(), $mock_return_values[$i]];
    }
    $this->localeConfigManager
      ->expects($this->any())
      ->method('hasTranslation')
      ->will($this->returnValueMap($map));

    $result = $this->configNamesMapper->hasTranslation($language);
    $this->assertSame($expected, $result);
  }

  /**
   * Provides data for for ConfigNamesMapperTest::testHasTranslation().
   *
   * @return array
   *   An array of arrays, where each inner array has an array of values that
   *   the mocked configuration mapper manager should return for
   *   hasTranslation() as the first value and the expected return value of
   *   ConfigNamesMapper::hasTranslation() as the second value.
   */
  public function providerTestHasTranslation() {
    return [
      [[TRUE], TRUE],
      [[FALSE], FALSE],
      [[TRUE, TRUE, TRUE], TRUE],
      [[FALSE, FALSE, TRUE], TRUE],
      [[FALSE, FALSE, FALSE], FALSE],
    ];
  }

  /**
   * Tests ConfigNamesMapper::getTypeName().
   */
  public function testGetTypeName() {
    $result = $this->configNamesMapper->getTypeName();
    $this->assertSame('Settings', (string) $result);
  }

  /**
   * Tests ConfigNamesMapper::hasTranslation().
   */
  public function testGetOperations() {
    $expected = [
      'translate' => [
        'title' => 'Translate',
        'url' => Url::fromRoute('config_translation.item.overview.system.site_information_settings'),
      ],
    ];
    $result = $this->configNamesMapper->getOperations();
    $this->assertEquals($expected, $result);
  }

}

/**
 * Defines a test mapper class.
 */
class TestConfigNamesMapper extends ConfigNamesMapper {

  /**
   * Gets the internal language code of this mapper, if any.
   *
   * This method is not to be confused with
   * ConfigMapperInterface::getLangcode().
   *
   * @return string|null
   *   The language code of this mapper if it is set; NULL otherwise.
   */
  public function getInternalLangcode() {
    return isset($this->langcode) ? $this->langcode : NULL;
  }

  /**
   * Sets the list of configuration names.
   *
   * @param array $config_names
   */
  public function setConfigNames(array $config_names) {
    $this->pluginDefinition['names'] = $config_names;
  }

  /**
   * Sets the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory to set.
   */
  public function setConfigFactory(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

}
