<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Layout;

use Composer\Autoload\ClassLoader;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Layout\LayoutDefinition;
use Drupal\Core\Layout\LayoutInterface;
use Drupal\Core\Layout\LayoutPluginManager;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use Prophecy\Argument;

// cspell:ignore lorem, ipsum, consectetur, adipiscing

/**
 * @coversDefaultClass \Drupal\Core\Layout\LayoutPluginManager
 * @group Layout
 * @group legacy
 */
class LayoutPluginManagerTest extends UnitTestCase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Cache backend instance.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpFilesystem();

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $this->themeManager = $this->prophesize(ThemeManagerInterface::class);
    $container->set('theme.manager', $this->themeManager->reveal());
    \Drupal::setContainer($container);

    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);

    $this->moduleHandler->moduleExists('module_a')->willReturn(TRUE);
    $this->moduleHandler->moduleExists('theme_a')->willReturn(FALSE);
    $this->moduleHandler->moduleExists('core')->willReturn(FALSE);
    $this->moduleHandler->moduleExists('invalid_provider')->willReturn(FALSE);

    $module_a = new Extension('vfs://root', 'module', 'modules/module_a/module_a.layouts.yml');
    $this->moduleHandler->getModule('module_a')->willReturn($module_a);
    $this->moduleHandler->getModuleDirectories()->willReturn(['module_a' => vfsStream::url('root/modules/module_a')]);
    $this->moduleHandler->alter('layout', Argument::type('array'))->shouldBeCalled();

    $this->themeHandler = $this->prophesize(ThemeHandlerInterface::class);

    $this->themeHandler->themeExists('theme_a')->willReturn(TRUE);
    $this->themeHandler->themeExists('core')->willReturn(FALSE);
    $this->themeHandler->themeExists('invalid_provider')->willReturn(FALSE);

    $theme_a = new Extension('vfs://root', 'theme', 'themes/theme_a/theme_a.layouts.yml');
    $this->themeHandler->getTheme('theme_a')->willReturn($theme_a);
    $this->themeHandler->getThemeDirectories()->willReturn(['theme_a' => vfsStream::url('root/themes/theme_a')]);

    $this->cacheBackend = $this->prophesize(CacheBackendInterface::class);

    $namespaces = new \ArrayObject(['Drupal\Core' => vfsStream::url('root/core/lib/Drupal/Core')]);
    $class_loader = new ClassLoader();
    $class_loader->addPsr4("Drupal\\Core\\", vfsStream::url("root/core/lib/Drupal/Core"));
    $class_loader->register(TRUE);
    $this->layoutPluginManager = new LayoutPluginManager($namespaces, $this->cacheBackend->reveal(), $this->moduleHandler->reveal(), $this->themeHandler->reveal());

    $this->expectDeprecation('Using @Layout annotation for plugin with ID plugin_provided_by_annotation_layout is deprecated and is removed from drupal:13.0.0. Use a Drupal\Core\Layout\Attribute\Layout attribute instead. See https://www.drupal.org/node/3395575');
  }

  /**
   * @covers ::getDefinitions
   * @covers ::providerExists
   */
  public function testGetDefinitions(): void {
    $expected = [
      'module_a_provided_layout',
      'theme_a_provided_layout',
      'plugin_provided_layout',
      'plugin_provided_by_annotation_layout',
    ];

    $layout_definitions = $this->layoutPluginManager->getDefinitions();
    $this->assertEquals($expected, array_keys($layout_definitions));
    $this->assertContainsOnlyInstancesOf(LayoutDefinition::class, $layout_definitions);
  }

  /**
   * @covers ::getDefinition
   * @covers ::processDefinition
   */
  public function testGetDefinition(): void {
    $layout_definition = $this->layoutPluginManager->getDefinition('theme_a_provided_layout');
    $this->assertSame('theme_a_provided_layout', $layout_definition->id());
    $this->assertSame('2 column layout', (string) $layout_definition->getLabel());
    $this->assertSame('Columns: 2', (string) $layout_definition->getCategory());
    $this->assertSame('A theme provided layout', (string) $layout_definition->getDescription());
    $this->assertInstanceOf(TranslatableMarkup::class, $layout_definition->getLabel());
    $this->assertInstanceOf(TranslatableMarkup::class, $layout_definition->getCategory());
    $this->assertInstanceOf(TranslatableMarkup::class, $layout_definition->getDescription());
    $this->assertSame('twocol', $layout_definition->getTemplate());
    $this->assertSame('themes/theme_a/templates', $layout_definition->getPath());
    $this->assertSame('theme_a/twocol', $layout_definition->getLibrary());
    $this->assertSame('twocol', $layout_definition->getThemeHook());
    $this->assertSame('themes/theme_a/templates', $layout_definition->getTemplatePath());
    $this->assertSame('theme_a', $layout_definition->getProvider());
    $this->assertSame('right', $layout_definition->getDefaultRegion());
    $this->assertSame(LayoutDefault::class, $layout_definition->getClass());
    $expected_regions = [
      'left' => [
        'label' => new TranslatableMarkup('Left region', [], ['context' => 'layout_region']),
      ],
      'right' => [
        'label' => new TranslatableMarkup('Right region', [], ['context' => 'layout_region']),
      ],
    ];
    $regions = $layout_definition->getRegions();
    $this->assertEquals($expected_regions, $regions);
    $this->assertInstanceOf(TranslatableMarkup::class, $regions['left']['label']);
    $this->assertInstanceOf(TranslatableMarkup::class, $regions['right']['label']);

    $layout_definition = $this->layoutPluginManager->getDefinition('module_a_provided_layout');
    $this->assertSame('module_a_provided_layout', $layout_definition->id());
    $this->assertSame('1 column layout', (string) $layout_definition->getLabel());
    $this->assertSame('Columns: 1', (string) $layout_definition->getCategory());
    $this->assertSame('A module provided layout', (string) $layout_definition->getDescription());
    $this->assertInstanceOf(TranslatableMarkup::class, $layout_definition->getLabel());
    $this->assertInstanceOf(TranslatableMarkup::class, $layout_definition->getCategory());
    $this->assertInstanceOf(TranslatableMarkup::class, $layout_definition->getDescription());
    $this->assertNull($layout_definition->getTemplate());
    $this->assertSame('modules/module_a/layouts', $layout_definition->getPath());
    $this->assertSame('module_a/onecol', $layout_definition->getLibrary());
    $this->assertSame('onecol', $layout_definition->getThemeHook());
    $this->assertNull($layout_definition->getTemplatePath());
    $this->assertSame('module_a', $layout_definition->getProvider());
    $this->assertSame('top', $layout_definition->getDefaultRegion());
    $this->assertSame(LayoutDefault::class, $layout_definition->getClass());
    $expected_regions = [
      'top' => [
        'label' => new TranslatableMarkup('Top region', [], ['context' => 'layout_region']),
      ],
      'bottom' => [
        'label' => new TranslatableMarkup('Bottom region', [], ['context' => 'layout_region']),
      ],
    ];
    $regions = $layout_definition->getRegions();
    $this->assertEquals($expected_regions, $regions);
    $this->assertInstanceOf(TranslatableMarkup::class, $regions['top']['label']);
    $this->assertInstanceOf(TranslatableMarkup::class, $regions['bottom']['label']);
    // Check that arbitrary property value gets set correctly.
    $this->assertSame('ipsum', $layout_definition->get('lorem'));

    $core_path = '/core/lib/Drupal/Core';
    $layout_definition = $this->layoutPluginManager->getDefinition('plugin_provided_layout');
    $this->assertSame('plugin_provided_layout', $layout_definition->id());
    $this->assertEquals('Layout plugin', $layout_definition->getLabel());
    $this->assertEquals('Columns: 1', $layout_definition->getCategory());
    $this->assertEquals('Test layout', $layout_definition->getDescription());
    $this->assertInstanceOf(TranslatableMarkup::class, $layout_definition->getLabel());
    $this->assertInstanceOf(TranslatableMarkup::class, $layout_definition->getCategory());
    $this->assertInstanceOf(TranslatableMarkup::class, $layout_definition->getDescription());
    $this->assertSame('plugin-provided-layout', $layout_definition->getTemplate());
    $this->assertSame($core_path, $layout_definition->getPath());
    $this->assertNull($layout_definition->getLibrary());
    $this->assertSame('plugin_provided_layout', $layout_definition->getThemeHook());
    $this->assertSame("$core_path/templates", $layout_definition->getTemplatePath());
    $this->assertSame('core', $layout_definition->getProvider());
    $this->assertSame('main', $layout_definition->getDefaultRegion());
    $this->assertSame('Drupal\Core\Plugin\Layout\TestLayout', $layout_definition->getClass());
    $expected_regions = [
      'main' => [
        'label' => new TranslatableMarkup('Main Region', [], ['context' => 'layout_region']),
      ],
    ];
    $regions = $layout_definition->getRegions();
    $this->assertEquals($expected_regions, $regions);
    $this->assertInstanceOf(TranslatableMarkup::class, $regions['main']['label']);
    // Check that arbitrary property value gets set correctly.
    $this->assertSame('adipiscing', $layout_definition->get('consectetur'));

    $layout_definition = $this->layoutPluginManager->getDefinition('plugin_provided_by_annotation_layout');
    $this->assertSame('plugin_provided_by_annotation_layout', $layout_definition->id());
    $this->assertEquals('Layout by annotation plugin', $layout_definition->getLabel());
    $this->assertEquals('Columns: 2', $layout_definition->getCategory());
    $this->assertEquals('Test layout provided by annotated plugin', $layout_definition->getDescription());
    $this->assertInstanceOf(TranslatableMarkup::class, $layout_definition->getLabel());
    $this->assertInstanceOf(TranslatableMarkup::class, $layout_definition->getCategory());
    $this->assertInstanceOf(TranslatableMarkup::class, $layout_definition->getDescription());
    $this->assertSame('plugin-provided-annotation-layout', $layout_definition->getTemplate());
    $this->assertSame($core_path, $layout_definition->getPath());
    $this->assertNull($layout_definition->getLibrary());
    $this->assertSame('plugin_provided_annotation_layout', $layout_definition->getThemeHook());
    $this->assertSame("$core_path/templates", $layout_definition->getTemplatePath());
    $this->assertSame('core', $layout_definition->getProvider());
    $this->assertSame('left', $layout_definition->getDefaultRegion());
    $this->assertSame('Drupal\Core\Plugin\Layout\TestAnnotationLayout', $layout_definition->getClass());
    $expected_regions = [
      'left' => [
        'label' => new TranslatableMarkup('Left Region', [], ['context' => 'layout_region']),
      ],
      'right' => [
        'label' => new TranslatableMarkup('Right Region', [], ['context' => 'layout_region']),
      ],
    ];
    $regions = $layout_definition->getRegions();
    $this->assertEquals($expected_regions, $regions);
    $this->assertInstanceOf(TranslatableMarkup::class, $regions['left']['label']);
    $this->assertInstanceOf(TranslatableMarkup::class, $regions['right']['label']);
  }

  /**
   * @covers ::processDefinition
   */
  public function testProcessDefinition(): void {
    $this->moduleHandler->alter('layout', Argument::type('array'))->shouldNotBeCalled();
    $this->expectException(InvalidPluginDefinitionException::class);
    $this->expectExceptionMessage('The "module_a_derived_layout:array_based" layout definition must extend ' . LayoutDefinition::class);
    $module_a_provided_layout = <<<'EOS'
module_a_derived_layout:
  deriver: \Drupal\Tests\Core\Layout\LayoutDeriver
  array_based: true
EOS;
    vfsStream::create([
      'modules' => [
        'module_a' => [
          'module_a.layouts.yml' => $module_a_provided_layout,
        ],
      ],
    ]);
    $this->layoutPluginManager->getDefinitions();
  }

  /**
   * @covers ::getThemeImplementations
   */
  public function testGetThemeImplementations(): void {
    $core_path = '/core/lib/Drupal/Core';
    $expected = [
      'layout' => [
        'render element' => 'content',
      ],
      'twocol' => [
        'render element' => 'content',
        'base hook' => 'layout',
        'template' => 'twocol',
        'path' => 'themes/theme_a/templates',
      ],
      'plugin_provided_layout' => [
        'render element' => 'content',
        'base hook' => 'layout',
        'template' => 'plugin-provided-layout',
        'path' => "$core_path/templates",
      ],
      'plugin_provided_annotation_layout' => [
        'render element' => 'content',
        'base hook' => 'layout',
        'template' => 'plugin-provided-annotation-layout',
        'path' => "$core_path/templates",
      ],
    ];
    $theme_implementations = $this->layoutPluginManager->getThemeImplementations();
    $this->assertEquals($expected, $theme_implementations);
  }

  /**
   * @covers ::getCategories
   */
  public function testGetCategories(): void {
    $expected = [
      'Columns: 1',
      'Columns: 2',
    ];
    $categories = $this->layoutPluginManager->getCategories();
    $this->assertEquals($expected, $categories);
  }

  /**
   * @covers ::getSortedDefinitions
   */
  public function testGetSortedDefinitions(): void {
    // Sorted by category first, then label.
    $expected = [
      'module_a_provided_layout',
      'plugin_provided_layout',
      'theme_a_provided_layout',
      'plugin_provided_by_annotation_layout',
    ];

    $layout_definitions = $this->layoutPluginManager->getSortedDefinitions();
    $this->assertEquals($expected, array_keys($layout_definitions));
    $this->assertContainsOnlyInstancesOf(LayoutDefinition::class, $layout_definitions);
  }

  /**
   * @covers ::getGroupedDefinitions
   */
  public function testGetGroupedDefinitions(): void {
    $category_expected = [
      'Columns: 1' => [
        'module_a_provided_layout',
        'plugin_provided_layout',
      ],
      'Columns: 2' => [
        'theme_a_provided_layout',
        'plugin_provided_by_annotation_layout',
      ],
    ];

    $definitions = $this->layoutPluginManager->getGroupedDefinitions();
    $this->assertEquals(array_keys($category_expected), array_keys($definitions));
    foreach ($category_expected as $category => $expected) {
      $this->assertArrayHasKey($category, $definitions);
      $this->assertEquals($expected, array_keys($definitions[$category]));
      $this->assertContainsOnlyInstancesOf(LayoutDefinition::class, $definitions[$category]);
    }
  }

  /**
   * Test that modules and themes can alter the list of layouts.
   *
   * @covers ::getLayoutOptions
   */
  public function testGetLayoutOptions(): void {
    $this->moduleHandler->alter(
      ['plugin_filter_layout', 'plugin_filter_layout__layout'],
      Argument::type('array'),
      [],
      'layout',
    )->shouldBeCalled();
    $this->themeManager->alter(
      ['plugin_filter_layout', 'plugin_filter_layout__layout'],
      Argument::type('array'),
      [],
      'layout',
    )->shouldBeCalled();

    $this->layoutPluginManager->getLayoutOptions();
  }

  /**
   * Sets up the filesystem with YAML files and annotated plugins.
   */
  protected function setUpFilesystem(): void {
    $module_a_provided_layout = <<<'EOS'
module_a_provided_layout:
  label: 1 column layout
  category: 'Columns: 1'
  description: 'A module provided layout'
  theme_hook: onecol
  path: layouts
  library: module_a/onecol
  regions:
    top:
      label: Top region
    bottom:
      label: Bottom region
  lorem: ipsum
module_a_derived_layout:
  label: 'Invalid provider derived layout'
  deriver: \Drupal\Tests\Core\Layout\LayoutDeriver
  invalid_provider: true
EOS;
    $theme_a_provided_layout = <<<'EOS'
theme_a_provided_layout:
  class: '\Drupal\Core\Layout\LayoutDefault'
  label: 2 column layout
  category: 'Columns: 2'
  description: 'A theme provided layout'
  template: twocol
  path: templates
  library: theme_a/twocol
  default_region: right
  regions:
    left:
      label: Left region
    right:
      label: Right region
EOS;
    $plugin_provided_layout = <<<'EOS'
<?php
namespace Drupal\Core\Plugin\Layout;
use Drupal\Core\Layout\Attribute\Layout;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\StringTranslation\TranslatableMarkup;
/**
 * The TestLayout Class.
 */
#[Layout(
  id: 'plugin_provided_layout',
  label: new TranslatableMarkup('Layout plugin'),
  category: new TranslatableMarkup('Columns: 1'),
  description: new TranslatableMarkup('Test layout'),
  path: "core/lib/Drupal/Core",
  template: "templates/plugin-provided-layout",
  regions: [
    "main" => [
      "label" => new TranslatableMarkup("Main Region", [], ["context" => "layout_region"]),
    ],
  ],
  consectetur: 'adipiscing',
)]
class TestLayout extends LayoutDefault {}
EOS;
    $plugin_provided_by_annotation_layout = <<<'EOS'
<?php
namespace Drupal\Core\Plugin\Layout;
use Drupal\Core\Layout\LayoutDefault;
/**
 * @Layout(
 *   id = "plugin_provided_by_annotation_layout",
 *   label = @Translation("Layout by annotation plugin"),
 *   category = @Translation("Columns: 2"),
 *   description = @Translation("Test layout provided by annotated plugin"),
 *   path = "core/lib/Drupal/Core",
 *   template = "templates/plugin-provided-annotation-layout",
 *   default_region = "left",
 *   regions = {
 *     "left" = {
 *       "label" = @Translation("Left Region", context = "layout_region")
 *     },
 *     "right" = {
 *        "label" = @Translation("Right Region", context = "layout_region")
 *     }
 *   }
 * )
 */
class TestAnnotationLayout extends LayoutDefault {}
EOS;
    vfsStream::setup('root');
    vfsStream::create([
      'modules' => [
        'module_a' => [
          'module_a.layouts.yml' => $module_a_provided_layout,
        ],
      ],
    ]);
    vfsStream::create([
      'themes' => [
        'theme_a' => [
          'theme_a.layouts.yml' => $theme_a_provided_layout,
        ],
      ],
    ]);
    vfsStream::create([
      'core' => [
        'lib' => [
          'Drupal' => [
            'Core' => [
              'Plugin' => [
                'Layout' => [
                  'TestLayout.php' => $plugin_provided_layout,
                  'TestAnnotationLayout.php' => $plugin_provided_by_annotation_layout,
                ],
              ],
            ],
          ],
        ],
      ],
    ]);
  }

}

/**
 * Provides a dynamic layout deriver for the test.
 */
class LayoutDeriver extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if ($base_plugin_definition->get('array_based')) {
      $this->derivatives['array_based'] = [];
    }
    if ($base_plugin_definition->get('invalid_provider')) {
      $this->derivatives['invalid_provider'] = new LayoutDefinition([
        'id' => 'invalid_provider',
        'provider' => 'invalid_provider',
      ]);
      $this->derivatives['invalid_provider']->setClass(LayoutInterface::class);
    }
    return $this->derivatives;
  }

}
