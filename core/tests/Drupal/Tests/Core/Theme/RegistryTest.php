<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Theme\RegistryTest.
 */

namespace Drupal\Tests\Core\Theme;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Theme\Registry;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the theme registry service.
 *
 * @group Drupal
 * @group ${group}
 *
 * @see \Drupal\Core\Theme\Registry
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
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Theme Registry',
      'description' => 'Tests the theme registry.',
      'group' => 'Theme',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->cache = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->lock = $this->getMock('Drupal\Core\Lock\LockBackendInterface');
    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');

    $this->setupTheme();
  }

  /**
   * Tests getting the theme registry defined by a module.
   */
  public function testGetRegistryForModule() {
    $this->setupTheme('test_theme');
    $this->registry->setTheme(new Extension('theme', 'core/modules/system/tests/themes/test_theme/test_theme.info.yml', 'test_theme.theme'));
    $this->registry->setBaseThemes(array());

    // Include the module so that hook_theme can be called.
    include_once DRUPAL_ROOT . '/core/modules/system/tests/modules/theme_test/theme_test.module';
    $this->moduleHandler->expects($this->once())
      ->method('getImplementations')
      ->with('theme')
      ->will($this->returnValue(array('theme_test')));

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

    $info = $registry['theme_test_function_suggestions'];
    $this->assertEquals('module', $info['type']);
    $this->assertEquals('core/modules/system/tests/modules/theme_test', $info['theme path']);
    $this->assertEquals('theme_theme_test_function_suggestions', $info['function']);
    $this->assertEquals(array(), $info['variables']);
  }

  protected function setupTheme($theme_name = NULL) {
    $this->registry = new TestRegistry($this->cache, $this->lock, $this->moduleHandler, $theme_name);
  }

}

class TestRegistry extends Registry {

  public function setTheme(Extension $theme) {
    $this->theme = $theme;
  }

  public function setBaseThemes(array $base_themes) {
    $this->baseThemes = $base_themes;
  }

  protected function init($theme_name = NULL) {
  }

  protected function getPath($module) {
    if ($module == 'theme_test') {
      return 'core/modules/system/tests/modules/theme_test';
    }
  }

  protected function listThemes() {
  }

  protected function initializeTheme() {
  }

}

if (!defined('DRUPAL_ROOT')) {
  define('DRUPAL_ROOT', dirname(dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__)))));
}
