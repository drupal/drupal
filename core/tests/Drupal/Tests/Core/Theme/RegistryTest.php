<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Theme\Registry;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\theme_test\Hook\ThemeTestHooks;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests Drupal\Core\Theme\Registry.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\Drupal\Core\Theme\Registry::class)]
#[\PHPUnit\Framework\Attributes\Group('Theme')]
class RegistryTest extends UnitTestCase {

  /**
   * The mocked theme registry.
   *
   * @var \Drupal\Core\Theme\Registry|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $registry;

  /**
   * The cache backend.
   */
  protected CacheBackendInterface&Stub $cache;

  /**
   * The lock backend.
   */
  protected LockBackendInterface&Stub $lock;

  /**
   * The module handler.
   */
  protected ModuleHandlerInterface&Stub $moduleHandler;

  /**
   * The theme handler.
   */
  protected ThemeHandlerInterface&Stub $themeHandler;

  /**
   * The cache backend.
   */
  protected CacheBackendInterface&Stub $runtimeCache;

  /**
   * The theme manager.
   */
  protected ThemeManagerInterface&Stub $themeManager;

  /**
   * The module list.
   */
  protected ModuleExtensionList&Stub $moduleList;

  /**
   * The kernel.
   */
  protected HttpKernelInterface&Stub $kernel;

  /**
   * They key value factory.
   */
  protected KeyValueFactoryInterface $keyValueFactory;

  /**
   * The list of functions that get_defined_functions() should provide.
   *
   * @var array
   */
  public static $functions = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->cache = $this->createStub(CacheBackendInterface::class);
    $this->lock = $this->createStub(LockBackendInterface::class);
    $this->moduleHandler = $this->createStub(ModuleHandlerInterface::class);
    $this->themeHandler = $this->createStub(ThemeHandlerInterface::class);
    $this->runtimeCache = $this->createStub(CacheBackendInterface::class);
    $this->themeManager = $this->createStub(ThemeManagerInterface::class);
    $this->moduleList = $this->createStub(ModuleExtensionList::class);
    $this->kernel = $this->createStub(HttpKernelInterface::class);
    $this->keyValueFactory = new KeyValueMemoryFactory();

    $this->registry = new Registry($this->cache, $this->lock, $this->moduleHandler, $this->themeHandler, $this->runtimeCache, $this->moduleList, $this->kernel, $this->keyValueFactory);
    $this->registry->setThemeManager($this->themeManager);
  }

  /**
   * Reinitializes the module handler as a mock object.
   */
  protected function setUpMockModuleHandler(): void {
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $reflection = new \ReflectionProperty($this->registry, 'moduleHandler');
    $reflection->setValue($this->registry, $this->moduleHandler);
  }

  /**
   * Reinitializes the module list as a mock object.
   */
  protected function setUpMockModuleList(): void {
    $this->moduleList = $this->createMock(ModuleExtensionList::class);
    $reflection = new \ReflectionProperty($this->registry, 'moduleList');
    $reflection->setValue($this->registry, $this->moduleList);
  }

  /**
   * Reinitializes the theme manager as a mock object.
   */
  protected function setUpMockThemeManager(): void {
    $this->themeManager = $this->createMock(ThemeManagerInterface::class);
    $reflection = new \ReflectionProperty($this->registry, 'themeManager');
    $reflection->setValue($this->registry, $this->themeManager);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();
    static::$functions = [];
  }

  /**
   * Tests getting the theme registry defined by a module.
   */
  public function testGetRegistryForModule(): void {
    $this->setUpMockModuleList();
    $this->setUpMockModuleHandler();
    $this->setUpMockThemeManager();

    $test_theme = new ActiveTheme([
      'name' => 'test_theme',
      'path' => 'core/modules/system/tests/themes/test_theme/test_theme.info.yml',
      'engine' => 'twig',
      'owner' => 'twig',
      'libraries_override' => [],
      'libraries_extend' => [],
      'libraries' => [],
      'extension' => '.twig',
      'base_theme_extensions' => [],
    ]);

    $test_stable = new ActiveTheme([
      'name' => 'test_stable',
      'path' => 'core/tests/fixtures/test_stable/test_stable.info.yml',
      'engine' => 'twig',
      'owner' => 'twig',
      'libraries_override' => [],
      'libraries_extend' => [],
      'libraries' => [],
      'extension' => '.twig',
      'base_theme_extensions' => [],
    ]);

    $this->themeManager->expects($this->exactly(2))
      ->method('getActiveTheme')
      ->willReturnOnConsecutiveCalls($test_theme, $test_stable);

    // Set up a mock theme hook list for the test_stable theme. This does
    // not actually need to exist for this test.
    $this->keyValueFactory->get('hook_data')->set('theme_hook_list', [
      'test_stable' => [
        'preprocess_theme_test_render_element' => [
          'Drupal\test_stable\Hook\TestStableHooks::preprocessThemeTestRenderElement',
        ],
      ],
    ]);
    $themeTestTheme = new ThemeTestHooks();
    $this->moduleHandler->expects($this->exactly(2))
      ->method('invoke')
      ->with('theme_test', 'theme')
      ->willReturn($themeTestTheme->theme(NULL, NULL, NULL, NULL));
    $this->moduleHandler->expects($this->atMost(50))
      ->method('invokeAllWith')
      // $callback is documented on ModuleHandlerInterface::invokeAllWith().
      // The first argument expects a callable, but it doesn't matter what it
      // is, use pi() as a canary in case code changes, and it begins to use it.
      // The second argument is the module name and for that theme_test is
      // always correct here.
      ->willReturnCallback(fn (string $hook, callable $callback) => $callback('pi', 'theme_test'));
    $this->moduleHandler->expects($this->exactly(2))
      ->method('getModuleList')
      ->willReturn(['theme_test' => NULL]);
    $this->moduleList->expects($this->exactly(2))
      ->method('getPath')
      ->with('theme_test')
      ->willReturn('core/modules/system/tests/modules/theme_test');

    $registry = $this->registry->get();

    // Ensure that the registry entries from the module are found.
    $this->assertArrayHasKey('theme_test', $registry);
    $this->assertArrayHasKey('theme_test_template_test', $registry);
    $this->assertArrayHasKey('theme_test_template_test_2', $registry);
    $this->assertArrayHasKey('theme_test_suggestion_provided', $registry);
    $this->assertArrayHasKey('theme_test_specific_suggestions', $registry);
    $this->assertArrayHasKey('theme_test_suggestions', $registry);
    $this->assertArrayHasKey('theme_test_foo', $registry);
    $this->assertArrayHasKey('theme_test_render_element', $registry);

    $this->assertEquals('Drupal\theme_test\Hook\ThemeTestHooks:preprocessThemeTestRenderElement', $registry['theme_test_render_element']['initial preprocess']);

    // The second call will initialize with the second theme. Ensure that this
    // returns a different object and the discovery for the second theme's
    // preprocess function worked.
    $other_registry = $this->registry->get();
    $this->assertNotSame($registry, $other_registry);
    // The "function" is just a map to the invoke map which contains the theme
    // and hook name.
    $function = 'test_stable_preprocess_theme_test_render_element';
    $this->assertContains($function, $other_registry['theme_test_render_element']['preprocess functions']);
    $this->assertEquals(['theme' => 'test_stable', 'hook' => 'preprocess_theme_test_render_element'], $other_registry['preprocess invokes'][$function]);
  }

  /**
   * Tests post process extension.
   *
   * @param array $defined_functions
   *   An array of functions to be used in place of get_defined_functions().
   * @param array $hooks
   *   An array of theme hooks to process.
   * @param array $expected
   *   The expected results.
   *
   * @legacy-covers ::postProcessExtension
   * @legacy-covers ::completeSuggestion
   * @legacy-covers ::mergePreprocessFunctions
   */
  #[\PHPUnit\Framework\Attributes\DataProvider('providerTestPostProcessExtension')]
  public function testPostProcessExtension($defined_functions, $hooks, $expected): void {
    $this->setUpMockModuleHandler();

    static::$functions['user'] = $defined_functions;

    $theme = $this->prophesize(ActiveTheme::class);
    $theme->getBaseThemeExtensions()->willReturn([]);
    $theme->getName()->willReturn('test');
    $theme->getEngine()->willReturn('twig');

    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('getModuleList')
      ->willReturn([]);

    $class = new \ReflectionClass(Registry::class);
    $reflection_method = $class->getMethod('postProcessExtension');
    $reflection_method->invokeArgs($this->registry, [&$hooks, $theme->reveal()]);

    $this->assertEquals($expected, $hooks);
  }

  /**
   * Provides test data to ::testPostProcessExtension().
   */
  public static function providerTestPostProcessExtension(): array {
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
    $data['base_hook_with_auto-discovered_suggestions'] = [
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

}

namespace Drupal\Core\Theme;

use Drupal\Tests\Core\Theme\RegistryTest;

/**
 * Overrides get_defined_functions() with a configurable mock.
 */
function get_defined_functions() {
  return RegistryTest::$functions ?: \get_defined_functions();
}
