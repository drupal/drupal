<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Theme\RegistryTest.
 */

namespace Drupal\Tests\Core\Theme;

use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Theme\Registry;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Theme\Registry
 * @group Theme
 */
class RegistryTest extends UnitTestCase {

  /**
   * The tested theme registry.
   *
   * @var \Drupal\Tests\Core\Theme\TestRegistry
   */
  protected $registry;

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cache;

  /**
   * The mocked lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $lock;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The mocked theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $themeHandler;

  /**
   * The mocked theme initialization.
   *
   * @var \Drupal\Core\Theme\ThemeInitializationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $themeInitialization;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $themeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->cache = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->lock = $this->getMock('Drupal\Core\Lock\LockBackendInterface');
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->themeHandler = $this->getMock('Drupal\Core\Extension\ThemeHandlerInterface');
    $this->themeInitialization = $this->getMock('Drupal\Core\Theme\ThemeInitializationInterface');
    $this->themeManager = $this->getMock('Drupal\Core\Theme\ThemeManagerInterface');

    $this->setupTheme();
  }

  /**
   * Tests getting the theme registry defined by a module.
   */
  public function testGetRegistryForModule() {
    $test_theme = new ActiveTheme([
      'name' => 'test_theme',
      'path' => 'core/modules/system/tests/themes/test_theme/test_theme.info.yml',
      'engine' => 'twig',
      'owner' => 'twig',
      'stylesheets_remove' => [],
      'libraries_override' => [],
      'libraries_extend' => [],
      'libraries' => [],
      'extension' => '.twig',
      'base_themes' => [],
    ]);

    $test_stable = new ActiveTheme([
      'name' => 'test_stable',
      'path' => 'core/modules/system/tests/themes/test_stable/test_stable.info.yml',
      'engine' => 'twig',
      'owner' => 'twig',
      'stylesheets_remove' => [],
      'libraries_override' => [],
      'libraries_extend' => [],
      'libraries' => [],
      'extension' => '.twig',
      'base_themes' => [],
    ]);

    $this->themeManager->expects($this->exactly(2))
      ->method('getActiveTheme')
      ->willReturnOnConsecutiveCalls($test_theme, $test_stable);

    // Include the module and theme files so that hook_theme can be called.
    include_once $this->root . '/core/modules/system/tests/modules/theme_test/theme_test.module';
    include_once $this->root . '/core/modules/system/tests/themes/test_stable/test_stable.theme';
    $this->moduleHandler->expects($this->exactly(2))
      ->method('getImplementations')
      ->with('theme')
      ->will($this->returnValue(array('theme_test')));
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('getModuleList')
      ->willReturn([]);

    $registry = $this->registry->get();

    // Ensure that the registry entries from the module are found.
    $this->assertArrayHasKey('theme_test', $registry);
    $this->assertArrayHasKey('theme_test_template_test', $registry);
    $this->assertArrayHasKey('theme_test_template_test_2', $registry);
    $this->assertArrayHasKey('theme_test_suggestion_provided', $registry);
    $this->assertArrayHasKey('theme_test_specific_suggestions', $registry);
    $this->assertArrayHasKey('theme_test_suggestions', $registry);
    $this->assertArrayHasKey('theme_test_function_suggestions', $registry);
    $this->assertArrayHasKey('theme_test_foo', $registry);
    $this->assertArrayHasKey('theme_test_render_element', $registry);
    $this->assertArrayHasKey('theme_test_render_element_children', $registry);
    $this->assertArrayHasKey('theme_test_function_template_override', $registry);

    $this->assertArrayNotHasKey('test_theme_not_existing_function', $registry);
    $this->assertFalse(in_array('test_stable_preprocess_theme_test_render_element', $registry['theme_test_render_element']['preprocess functions']));

    $info = $registry['theme_test_function_suggestions'];
    $this->assertEquals('module', $info['type']);
    $this->assertEquals('core/modules/system/tests/modules/theme_test', $info['theme path']);
    $this->assertEquals('theme_theme_test_function_suggestions', $info['function']);
    $this->assertEquals(array(), $info['variables']);

    // The second call will initialize with the second theme. Ensure that this
    // returns a different object and the discovery for the second theme's
    // preprocess function worked.
    $other_registry = $this->registry->get();
    $this->assertNotSame($registry, $other_registry);
    $this->assertTrue(in_array('test_stable_preprocess_theme_test_render_element', $other_registry['theme_test_render_element']['preprocess functions']));
  }

  protected function setupTheme() {
    $this->registry = new TestRegistry($this->root, $this->cache, $this->lock, $this->moduleHandler, $this->themeHandler, $this->themeInitialization);
    $this->registry->setThemeManager($this->themeManager);
  }

}

class TestRegistry extends Registry {

  protected function getPath($module) {
    if ($module == 'theme_test') {
      return 'core/modules/system/tests/modules/theme_test';
    }
  }

}
