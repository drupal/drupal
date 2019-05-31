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
   * The list of functions that get_defined_functions() should provide.
   *
   * @var array
   */
  public static $functions = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
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
   * {@inheritdoc}
   */
  protected function tearDown() {
    parent::tearDown();
    static::$functions = [];
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
      'base_theme_extensions' => [],
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
      'base_theme_extensions' => [],
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
      ->will($this->returnValue(['theme_test']));
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
    $this->assertEquals([], $info['variables']);

    // The second call will initialize with the second theme. Ensure that this
    // returns a different object and the discovery for the second theme's
    // preprocess function worked.
    $other_registry = $this->registry->get();
    $this->assertNotSame($registry, $other_registry);
    $this->assertTrue(in_array('test_stable_preprocess_theme_test_render_element', $other_registry['theme_test_render_element']['preprocess functions']));
  }

  /**
   * @covers ::postProcessExtension
   * @covers ::completeSuggestion
   * @covers ::mergePreprocessFunctions
   *
   * @dataProvider providerTestPostProcessExtension
   *
   * @param array $defined_functions
   *   An array of functions to be used in place of get_defined_functions().
   * @param array $hooks
   *   An array of theme hooks to process.
   * @param array $expected
   *   The expected results.
   */
  public function testPostProcessExtension($defined_functions, $hooks, $expected) {
    static::$functions['user'] = $defined_functions;

    $theme = $this->prophesize(ActiveTheme::class);
    $theme->getBaseThemeExtensions()->willReturn([]);
    $theme->getName()->willReturn('test');
    $theme->getEngine()->willReturn('twig');

    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('getModuleList')
      ->willReturn([]);

    $class = new \ReflectionClass(TestRegistry::class);
    $reflection_method = $class->getMethod('postProcessExtension');
    $reflection_method->setAccessible(TRUE);
    $reflection_method->invokeArgs($this->registry, [&$hooks, $theme->reveal()]);

    $this->assertArrayEquals($expected, $hooks);
  }

  /**
   * Provides test data to ::testPostProcessExtension().
   */
  public function providerTestPostProcessExtension() {
    // This is test data for unit testing
    // \Drupal\Core\Theme\Registry::postProcessExtension(), not what happens
    // before it. Therefore, for all test data:
    // - Explicitly defined hooks also come with explicitly defined preprocess
    //   functions, because those are collected in
    //   \Drupal\Core\Theme\Registry::processExtension().
    // - Explicitly defined hooks that set a 'base hook' also have
    //   'incomplete preprocess functions' set to TRUE, since that is done in
    //   \Drupal\Core\Theme\Registry::processExtension().
    $data = [];

    // Test the discovery of suggestions via the presence of preprocess
    // functions that follow the "__" naming pattern.
    $data['base_hook_with_autodiscovered_suggestions'] = [
      'defined_functions' => [
        'test_preprocess_test_hook__suggestion',
        'test_preprocess_test_hook__suggestion__another',
      ],
      'hooks' => [
        'test_hook' => [
          'preprocess functions' => ['explicit_preprocess_test_hook'],
        ],
      ],
      'expected' => [
        'test_hook' => [
          'preprocess functions' => [
            'explicit_preprocess_test_hook',
          ],
        ],
        'test_hook__suggestion' => [
          'preprocess functions' => [
            'explicit_preprocess_test_hook',
            'test_preprocess_test_hook__suggestion',
          ],
          'base hook' => 'test_hook',
        ],
        'test_hook__suggestion__another' => [
          'preprocess functions' => [
            'explicit_preprocess_test_hook',
            'test_preprocess_test_hook__suggestion',
            'test_preprocess_test_hook__suggestion__another',
          ],
          'base hook' => 'test_hook',
        ],
      ],
    ];

    // Test that suggestions following the "__" naming pattern can also be
    // explicitly defined in hook_theme(), such as 'field__node__title' defined
    // in node_theme().
    $data['base_hook_with_explicit_suggestions'] = [
      'defined_functions' => [],
      'hooks' => [
        'test_hook' => [
          'preprocess functions' => ['explicit_preprocess_test_hook'],
        ],
        'test_hook__suggestion__another' => [
          'base hook' => 'test_hook',
          'preprocess functions' => ['explicit_preprocess_test_hook__suggestion__another'],
          'incomplete preprocess functions' => TRUE,
        ],
      ],
      'expected' => [
        'test_hook' => [
          'preprocess functions' => [
            'explicit_preprocess_test_hook',
          ],
        ],
        'test_hook__suggestion__another' => [
          'preprocess functions' => [
            'explicit_preprocess_test_hook',
            'explicit_preprocess_test_hook__suggestion__another',
          ],
          'base hook' => 'test_hook',
        ],
      ],
    ];

    // Same as above, but also test that a preprocess function for an
    // intermediary suggestion level gets discovered.
    $data['base_hook_with_explicit_suggestions_and_intermediary_preprocess_function'] = [
      'defined_functions' => [
        'test_preprocess_test_hook__suggestion',
      ],
      'hooks' => [
        'test_hook' => [
          'preprocess functions' => ['explicit_preprocess_test_hook'],
        ],
        'test_hook__suggestion__another' => [
          'base hook' => 'test_hook',
          'preprocess functions' => ['explicit_preprocess_test_hook__suggestion__another'],
          'incomplete preprocess functions' => TRUE,
        ],
      ],
      'expected' => [
        'test_hook' => [
          'preprocess functions' => [
            'explicit_preprocess_test_hook',
          ],
        ],
        'test_hook__suggestion' => [
          'preprocess functions' => [
            'explicit_preprocess_test_hook',
            'test_preprocess_test_hook__suggestion',
          ],
          'base hook' => 'test_hook',
        ],
        'test_hook__suggestion__another' => [
          'preprocess functions' => [
            'explicit_preprocess_test_hook',
            'test_preprocess_test_hook__suggestion',
            'explicit_preprocess_test_hook__suggestion__another',
          ],
          'base hook' => 'test_hook',
        ],
      ],
    ];

    // Test that hooks not following the "__" naming pattern can explicitly
    // specify a base hook, such as is done in
    // \Drupal\Core\Layout\LayoutPluginManager::getThemeImplementations().
    $data['child_hook_without_magic_naming'] = [
      'defined_functions' => [],
      'hooks' => [
        'test_hook' => [
          'preprocess functions' => ['explicit_preprocess_test_hook'],
        ],
        'child_hook' => [
          'base hook' => 'test_hook',
          'preprocess functions' => ['explicit_preprocess_child_hook'],
          'incomplete preprocess functions' => TRUE,
        ],
      ],
      'expected' => [
        'test_hook' => [
          'preprocess functions' => [
            'explicit_preprocess_test_hook',
          ],
        ],
        'child_hook' => [
          'preprocess functions' => [
            'explicit_preprocess_test_hook',
            'explicit_preprocess_child_hook',
          ],
          'base hook' => 'test_hook',
        ],
      ],
    ];

    // Same as above, but also test that such child hooks can also be extended
    // with magically named suggestions.
    $data['child_hook_with_suggestions'] = [
      'defined_functions' => [
        'test_preprocess_child_hook__suggestion',
        'test_preprocess_child_hook__suggestion__another',
      ],
      'hooks' => [
        'test_hook' => [
          'preprocess functions' => ['explicit_preprocess_test_hook'],
        ],
        'child_hook' => [
          'base hook' => 'test_hook',
          'preprocess functions' => ['explicit_preprocess_child_hook'],
          'incomplete preprocess functions' => TRUE,
        ],
      ],
      'expected' => [
        'test_hook' => [
          'preprocess functions' => [
            'explicit_preprocess_test_hook',
          ],
        ],
        'child_hook' => [
          'preprocess functions' => [
            'explicit_preprocess_test_hook',
            'explicit_preprocess_child_hook',
          ],
          'base hook' => 'test_hook',
        ],
        'child_hook__suggestion' => [
          'preprocess functions' => [
            'explicit_preprocess_test_hook',
            'explicit_preprocess_child_hook',
            'test_preprocess_child_hook__suggestion',
          ],
          'base hook' => 'test_hook',
        ],
        'child_hook__suggestion__another' => [
          'preprocess functions' => [
            'explicit_preprocess_test_hook',
            'explicit_preprocess_child_hook',
            'test_preprocess_child_hook__suggestion',
            'test_preprocess_child_hook__suggestion__another',
          ],
          'base hook' => 'test_hook',
        ],
      ],
    ];

    // Test that a suggestion following the "__" naming pattern can specify a
    // different base hook than what is implied by that pattern. Ensure that
    // preprocess functions from both the naming pattern and from 'base hook'
    // are collected.
    $data['suggestion_with_alternate_base_hook'] = [
      'defined_functions' => [
        'test_preprocess_test_hook__suggestion',
      ],
      'hooks' => [
        'test_hook' => [
          'preprocess functions' => ['explicit_preprocess_test_hook'],
        ],
        'alternate_base_hook' => [
          'preprocess functions' => ['explicit_preprocess_alternate_base_hook'],
        ],
        'test_hook__suggestion__another' => [
          'base hook' => 'alternate_base_hook',
          'preprocess functions' => ['explicit_preprocess_test_hook__suggestion__another'],
          'incomplete preprocess functions' => TRUE,
        ],
      ],
      'expected' => [
        'test_hook' => [
          'preprocess functions' => [
            'explicit_preprocess_test_hook',
          ],
        ],
        'alternate_base_hook' => [
          'preprocess functions' => [
            'explicit_preprocess_alternate_base_hook',
          ],
        ],
        'test_hook__suggestion' => [
          'preprocess functions' => [
            'explicit_preprocess_test_hook',
            'test_preprocess_test_hook__suggestion',
          ],
          'base hook' => 'test_hook',
        ],
        'test_hook__suggestion__another' => [
          'preprocess functions' => [
            'explicit_preprocess_alternate_base_hook',
            'explicit_preprocess_test_hook',
            'test_preprocess_test_hook__suggestion',
            'explicit_preprocess_test_hook__suggestion__another',
          ],
          'base hook' => 'alternate_base_hook',
        ],
      ],
    ];

    // Test when a base hook is missing.
    $data['missing_base_hook'] = [
      'defined_functions' => [],
      'hooks' => [
        'child_hook' => [
          'base hook' => 'test_hook',
          'preprocess functions' => ['explicit_preprocess_child_hook'],
          'incomplete preprocess functions' => TRUE,
        ],
      ],
      'expected' => [
        'child_hook' => [
          'preprocess functions' => [
            'explicit_preprocess_child_hook',
          ],
          'base hook' => 'test_hook',
        ],
      ],
    ];

    return $data;
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

namespace Drupal\Core\Theme;

use Drupal\Tests\Core\Theme\RegistryTest;

/**
 * Overrides get_defined_functions() with a configurable mock.
 */
function get_defined_functions() {
  return RegistryTest::$functions ?: \get_defined_functions();
}
