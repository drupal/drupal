<?php

namespace Drupal\Tests\Core\Theme;

use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Theme\Registry;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Theme\Registry
 * @group Theme
 * @group legacy
 *
 * @todo Remove in https://www.drupal.org/project/drupal/issues/3097889
 */
class RegistryLegacyTest extends UnitTestCase {

  /**
   * The mocked theme registry.
   *
   * @var \Drupal\Core\Theme\Registry|PHPUnit\Framework\MockObject\MockObject
   */
  protected $registry;

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cache;

  /**
   * The mocked lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $lock;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The mocked theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $themeHandler;

  /**
   * The mocked theme initialization.
   *
   * @var \Drupal\Core\Theme\ThemeInitializationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $themeInitialization;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $themeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->cache = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->lock = $this->createMock('Drupal\Core\Lock\LockBackendInterface');
    $this->moduleHandler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->themeHandler = $this->createMock('Drupal\Core\Extension\ThemeHandlerInterface');
    $this->themeInitialization = $this->createMock('Drupal\Core\Theme\ThemeInitializationInterface');
    $this->themeManager = $this->createMock('Drupal\Core\Theme\ThemeManagerInterface');

    $this->setupTheme();
  }

  /**
   * Tests getting legacy theme function registry data defined by a module.
   *
   * @expectedDeprecation Unsilenced deprecation: Theme functions are deprecated in drupal:8.0.0 and are removed from drupal:10.0.0. Use Twig templates instead of theme_theme_test(). See https://www.drupal.org/node/1831138
   */
  public function testGetLegacyThemeFunctionRegistryForModule() {
    $test_theme = new ActiveTheme([
      'name' => 'test_legacy_theme',
      'path' => 'core/modules/system/tests/themes/test_legacy_theme/test_legacy_theme.info.yml',
      'engine' => 'twig',
      'owner' => 'twig',
      'stylesheets_remove' => [],
      'libraries_override' => [],
      'libraries_extend' => [],
      'libraries' => [],
      'extension' => '.twig',
      'base_theme_extensions' => [],
    ]);

    $this->themeManager->expects($this->once())
      ->method('getActiveTheme')
      ->willReturn($test_theme);

    // Include the module and theme files so that hook_theme can be called.
    include_once $this->root . '/core/modules/system/tests/modules/theme_legacy_test/theme_legacy_test.module';
    $this->moduleHandler->expects($this->once())
      ->method('getImplementations')
      ->with('theme')
      ->will($this->returnValue(['theme_legacy_test']));
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('getModuleList')
      ->willReturn([]);

    $registry = $this->registry->get();

    // Ensure that the registry entries from the module are found.
    $this->assertArrayHasKey('theme_test', $registry);
    $this->assertArrayHasKey('theme_test_function_suggestions', $registry);
    $this->assertArrayHasKey('theme_test_foo', $registry);
    $this->assertArrayHasKey('theme_test_render_element_children', $registry);
    $this->assertArrayHasKey('theme_test_function_template_override', $registry);

    $this->assertArrayNotHasKey('test_theme_not_existing_function', $registry);

    $info = $registry['theme_test_function_suggestions'];
    $this->assertEquals('module', $info['type']);
    $this->assertEquals('core/modules/system/tests/modules/theme_legacy_test', $info['theme path']);
    $this->assertEquals('theme_theme_test_function_suggestions', $info['function']);
    $this->assertEquals([], $info['variables']);
  }

  protected function setupTheme() {
    $this->registry = $this->getMockBuilder(Registry::class)
      ->setMethods(['getPath'])
      ->setConstructorArgs([$this->root, $this->cache, $this->lock, $this->moduleHandler, $this->themeHandler, $this->themeInitialization])
      ->getMock();
    $this->registry->expects($this->any())
      ->method('getPath')
      ->willReturnCallback(function ($module) {
        if ($module == 'theme_legacy_test') {
          return 'core/modules/system/tests/modules/theme_legacy_test';
        }
      });
    $this->registry->setThemeManager($this->themeManager);
  }

}
