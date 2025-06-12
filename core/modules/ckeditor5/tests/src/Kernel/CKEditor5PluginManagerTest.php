<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Kernel;

use Composer\Autoload\ClassLoader;
use Drupal\ckeditor5\Annotation\CKEditor5AspectsOfCKEditor5Plugin;
use Drupal\ckeditor5\Annotation\DrupalAspectsOfCKEditor5Plugin;
use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\CKEditor5Plugin\Heading;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\editor\Entity\Editor;
use Drupal\KernelTests\KernelTestBase;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\TestTools\Random;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\FrozenParameterBag;
use Symfony\Component\Yaml\Yaml;

// cspell:ignore layercake everytextcontainer justheading

/**
 * Tests different ways of enabling CKEditor 5 plugins.
 *
 * @group ckeditor5
 * @internal
 */
class CKEditor5PluginManagerTest extends KernelTestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'filter',
    'editor',
    'ckeditor5',
    'media',
  ];

  /**
   * The manager for "CKEditor 5 plugin" plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfig;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    FilterFormat::create(
      Yaml::parseFile('core/profiles/standard/config/install/filter.format.basic_html.yml')
    )->save();
    Editor::create([
      'format' => 'basic_html',
      'editor' => 'ckeditor5',
    ])->save();
    FilterFormat::create(
      Yaml::parseFile('core/profiles/standard/config/install/filter.format.full_html.yml')
    )->save();
    Editor::create([
      'format' => 'full_html',
      'editor' => 'ckeditor5',
    ])->save();
    $this->manager = $this->container->get('plugin.manager.ckeditor5.plugin');
    $this->typedConfig = $this->container->get('config.typed');
  }

  /**
   * {@inheritdoc}
   */
  protected function enableModules(array $modules) {
    parent::enableModules($modules);
    // Ensure the CKEditor 5 plugin manager instance on the test reflects the
    // status after the module is installed.
    $this->manager = $this->container->get('plugin.manager.ckeditor5.plugin');
  }

  /**
   * Mocks a module providing a CKEditor 5 plugin in VFS.
   *
   * @param string $module_name
   *   The name of the module.
   * @param string $yaml
   *   The YAML to be stored in the *.ckeditor5.yml file.
   * @param array $additional_files
   *   The additional files to create.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   *   The container that has the VFS-mocked CKEditor 5 plugin-providing module
   *   installed in it; this container must be used to simulate this module
   *   being installed.
   */
  private function mockModuleInVfs(string $module_name, string $yaml, array $additional_files = []): ContainerInterface {
    $site_directory = ltrim(parse_url($this->siteDirectory)['path'], '/');
    vfsStream::create([
      'modules' => [
        $module_name => [
          "$module_name.info.yml" => <<<YAML
name: CKEditor 5 Test $module_name
type: module
core_version_requirement: ^9
YAML,
          "$module_name.ckeditor5.yml" => $yaml,
        ] + $additional_files,
      ],
    ], $this->vfsRoot->getChild($site_directory));

    if (!empty($additional_files)) {
      $additional_class_loader = new ClassLoader();
      $additional_class_loader->addPsr4("Drupal\\$module_name\\Plugin\\CKEditor5Plugin\\", vfsStream::url("root/$site_directory/modules/$module_name/src/Plugin/CKEditor5Plugin"));
      $additional_class_loader->register(TRUE);
    }

    $config_sync = \Drupal::service('config.storage');
    $config_data = $this->config('core.extension')->get();
    $config_data['module'][$module_name] = 1;
    $config_sync->write('core.extension', $config_data);

    // Construct a new container for testing a plugin definition in isolation,
    // without needing a separate module directory structure for it, and instead
    // allowing it to be provided entirely by a PHPUnit data provider. Inherit
    // all definitions from the successfully installed Drupal site for this
    // kernel test, but do not use $this->container. This is a hybrid of kernel
    // and unit test, to get the best of both worlds: test a unit, but ensure
    // the service definitions are in sync.
    $root = vfsStream::url("root/$site_directory");
    $container = new ContainerBuilder(new FrozenParameterBag([
      'app.root' => $root,
      'container.modules' => [
        $module_name => [
          'type' => 'module',
          'pathname' => "modules/$module_name/$module_name.info.yml",
          'filename' => NULL,
        ] + $this->container->getParameter('container.modules'),
      ],
      'container.namespaces' => [
        "Drupal\\$module_name" => vfsStream::url("root/$site_directory/modules/$module_name/src"),
      ] + $this->container->getParameter('container.namespaces'),
    ] + $this->container->getParameterBag()->all()));
    $container->setDefinitions($this->container->getDefinitions());

    // The exception to the above elegance: re-resolve the '%app_root%' param.
    // @see \Symfony\Component\DependencyInjection\Compiler\ResolveParameterPlaceHoldersPass
    // @see \Drupal\Core\DrupalKernel::guessApplicationRoot()
    $container->getDefinition('module_handler')->setArgument(0, '%app.root%');

    // To discover per-test case config schema YAML files, work around the
    // static file cache in \Drupal\Core\Extension\ExtensionDiscovery. There is
    // no work-around that allows using both the files on disk and some in vfs.
    // To make matters worse, decorating a service within the test only is not
    // an option either, because \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition
    // is a pure value object, so it uses the global container. Therefore the
    // only work-around possible is to manipulate the config schema definition
    // cache.
    // @todo Remove this in https://www.drupal.org/project/drupal/issues/2961541.
    if (isset($additional_files['config']['schema']["$module_name.schema.yml"])) {
      $cache = \Drupal::service('cache.discovery')
        ->get('typed_config_definitions');
      $typed_config_definitions = $cache->data;
      $typed_config_definitions += Yaml::parse($additional_files['config']['schema']["$module_name.schema.yml"]);
      \Drupal::service('config.typed')->clearCachedDefinitions();
      \Drupal::service('cache.discovery')->set('typed_config_definitions', $typed_config_definitions, $cache->expire, $cache->tags);
    }

    return $container;
  }

  /**
   * @covers \Drupal\ckeditor5\Plugin\CKEditor5PluginManager::processDefinition
   * @dataProvider providerTestInvalidPluginDefinitions
   */
  public function testInvalidPluginDefinitions(string $yaml, ?string $expected_exception = NULL, ?string $expected_message = NULL, ?array $additional_files = []): void {
    if ($expected_exception) {
      $this->expectException($expected_exception);
    }
    if ($expected_message) {
      $this->expectExceptionMessage($expected_message);
    }
    $container = $this->mockModuleInVfs('ckeditor5_invalid_plugin', $yaml, $additional_files);
    $pluginManager = $container->get('plugin.manager.ckeditor5.plugin');
    $this->assertNotNull($pluginManager);
    $this->assertIsArray($pluginManager->getDefinitions());
  }

  /**
   * Data provider.
   *
   * @return \Generator
   *   Test scenarios.
   */
  public static function providerTestInvalidPluginDefinitions(): \Generator {
    yield 'invalid plugin ID with everything else okay' => [
      <<<YAML
foo_bar:
  ckeditor5:
    plugins: []
  drupal:
    label: TEST
    elements: false
YAML,
      InvalidPluginDefinitionException::class,
      'The "foo_bar" CKEditor 5 plugin definition must have a plugin ID that starts with "ckeditor5_invalid_plugin_".',
    ];

    // Now let's show the progressive exceptions that should steer the plugin
    // developer in the right direction.

    yield 'only plugin ID, nothing else' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar: {}
YAML,
      InvalidPluginDefinitionException::class,
      'The "ckeditor5_invalid_plugin_foo_bar" CKEditor 5 plugin definition must contain a "drupal" key.',
    ];

    yield 'added drupal' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  drupal: {}
YAML,
      InvalidPluginDefinitionException::class,
      'The "ckeditor5_invalid_plugin_foo_bar" CKEditor 5 plugin definition must contain a "ckeditor5" key.',
    ];

    yield 'added ckeditor5' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5: {}
  drupal: {}
YAML,
      InvalidPluginDefinitionException::class,
      'The "ckeditor5_invalid_plugin_foo_bar" CKEditor 5 plugin definition must contain a "ckeditor5.plugins" key.',
    ];

    yield 'added ckeditor5.plugins' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal: {}
YAML,
      InvalidPluginDefinitionException::class,
      'The "ckeditor5_invalid_plugin_foo_bar" CKEditor 5 plugin definition must contain a "drupal.label" key.',
    ];

    yield 'added drupal.label' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    label: {}
YAML,
      InvalidPluginDefinitionException::class,
      'The "ckeditor5_invalid_plugin_foo_bar" CKEditor 5 plugin definition has a "drupal.label" value that is not a string nor a TranslatableMarkup instance.',
    ];

    yield 'fixed drupal.label' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    label: "Foo bar"
YAML,
      InvalidPluginDefinitionException::class,
      'The "ckeditor5_invalid_plugin_foo_bar" CKEditor 5 plugin definition must contain a "drupal.elements" key.',
    ];

    yield 'added drupal.elements' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    label: "Foo bar"
    elements: {}
YAML,
      InvalidPluginDefinitionException::class,
      'The "ckeditor5_invalid_plugin_foo_bar" CKEditor 5 plugin definition has a "drupal.elements" value that is neither a list of HTML tags/attributes nor false.',
    ];

    yield 'wrongly fixed drupal.elements: no valid tags' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    label: "Foo bar"
    elements:
      - foo
      - bar
YAML,
      InvalidPluginDefinitionException::class,
      'The "ckeditor5_invalid_plugin_foo_bar" CKEditor 5 plugin definition has a value at "drupal.elements.0" that is not an HTML tag with optional attributes: "foo". Expected structure: "<tag allowedAttribute="allowedValue1 allowedValue2">".',
    ];

    yield 'wrongly fixed drupal.elements: multiple tags per entry' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    label: "Foo bar"
    elements:
      - <foo> <bar>
YAML,
      InvalidPluginDefinitionException::class,
      'The "ckeditor5_invalid_plugin_foo_bar" CKEditor 5 plugin definition has a value at "drupal.elements.0": multiple tags listed, should be one: "<foo> <bar>".',
    ];

    yield 'fixed drupal.elements' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    label: "Foo bar"
    elements:
      - <foo>
      - <bar>
YAML,
    ];

    yield 'change plugin ID to something invalid' => [
      <<<YAML
foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    label: "Foo bar"
    elements:
      - <foo>
      - <bar>
YAML,
      InvalidPluginDefinitionException::class,
      'The "foo_bar" CKEditor 5 plugin definition must have a plugin ID that starts with "ckeditor5_invalid_plugin_".',
    ];

    yield 'alternative fix for drupal.elements' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    label: "Foo bar"
    elements: false
YAML,
    ];

    yield 'added invalid optional metadata: drupal.admin_library' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    label: "Foo bar"
    elements:
      - <foo>
      - <bar>
    admin_library: ckeditor5/foo_bar
YAML,
      InvalidPluginDefinitionException::class,
      'The "ckeditor5_invalid_plugin_foo_bar" CKEditor 5 plugin definition has a "drupal.admin_library" key whose asset library "ckeditor5/foo_bar" does not exist.',
    ];

    yield 'fixed drupal.admin_library' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    label: "Foo bar"
    elements:
      - <foo>
      - <bar>
    admin_library: ckeditor5/internal.admin.basic
YAML,
    ];

    // Add conditions.
    yield 'unsupported condition type' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    label: "Foo bar"
    elements:
      - <foo>
      - <bar>
    admin_library: ckeditor5/internal.admin.basic
    conditions:
      foo: bar
YAML,
      InvalidPluginDefinitionException::class,
      'The "ckeditor5_invalid_plugin_foo_bar" CKEditor 5 plugin definition has a "drupal.conditions" value that contains some unsupported condition types: "foo". Only the following conditions types are supported: "toolbarItem", "imageUploadStatus", "filter", "requiresConfiguration", "plugins".',
    ];
    yield 'invalid condition: toolbarItem' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    label: "Foo bar"
    elements:
      - <foo>
      - <bar>
    admin_library: ckeditor5/internal.admin.basic
    conditions:
      toolbarItem: [bold, italic]
YAML,
      InvalidPluginDefinitionException::class,
      'The "ckeditor5_invalid_plugin_foo_bar" CKEditor 5 plugin definition has an invalid "drupal.conditions" item. "toolbarItem" is set to an invalid value. A string corresponding to a CKEditor 5 toolbar item must be specified.',
    ];
    yield 'valid condition: toolbarItem' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    label: "Foo bar"
    elements:
      - <foo>
      - <bar>
    admin_library: ckeditor5/internal.admin.basic
    conditions:
      toolbarItem: bold
YAML,
    ];
    yield 'invalid condition: filter' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    label: "Foo bar"
    elements:
      - <foo>
      - <bar>
    admin_library: ckeditor5/internal.admin.basic
    conditions:
      toolbarItem: bold
      filter: true
YAML,
      InvalidPluginDefinitionException::class,
      'The "ckeditor5_invalid_plugin_foo_bar" CKEditor 5 plugin definition has an invalid "drupal.conditions" item. "filter" is set to an invalid value. A string corresponding to a filter plugin ID must be specified.',
    ];
    yield 'valid condition: filter' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    label: "Foo bar"
    elements:
      - <foo>
      - <bar>
    admin_library: ckeditor5/internal.admin.basic
    conditions:
      toolbarItem: bold
      filter: filter_caption
YAML,
    ];
    yield 'invalid condition: imageUploadStatus' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    label: "Foo bar"
    elements:
      - <foo>
      - <bar>
    admin_library: ckeditor5/internal.admin.basic
    conditions:
      toolbarItem: bold
      filter: filter_caption
      imageUploadStatus: 'true'
YAML,
      InvalidPluginDefinitionException::class,
      'The "ckeditor5_invalid_plugin_foo_bar" CKEditor 5 plugin definition has an invalid "drupal.conditions" item. "imageUploadStatus" is set to an invalid value. A boolean indicating whether image uploads must be enabled (true) or not (false) must be specified.',
    ];
    yield 'valid condition: imageUploadStatus' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    label: "Foo bar"
    elements:
      - <foo>
      - <bar>
    admin_library: ckeditor5/internal.admin.basic
    conditions:
      toolbarItem: bold
      filter: filter_caption
      imageUploadStatus: true
YAML,
    ];
    yield 'invalid condition: plugins' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    label: "Foo bar"
    elements:
      - <foo>
      - <bar>
    admin_library: ckeditor5/internal.admin.basic
    conditions:
      toolbarItem: bold
      filter: filter_caption
      imageUploadStatus: true
      plugins: ckeditor5_imageCaption
YAML,
      InvalidPluginDefinitionException::class,
      'The "ckeditor5_invalid_plugin_foo_bar" CKEditor 5 plugin definition has an invalid "drupal.conditions" item. "plugins" is set to an invalid value. A list of strings, each corresponding to a CKEditor 5 plugin ID must be specified.',
    ];
    yield 'valid condition: plugins' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    label: "Foo bar"
    elements:
      - <foo>
      - <bar>
    admin_library: ckeditor5/internal.admin.basic
    conditions:
      toolbarItem: bold
      filter: filter_caption
      imageUploadStatus: true
      plugins: [ckeditor5_imageCaption]
YAML,
    ];
    yield 'unconditional: for plugins that should always loaded' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    label: "Foo bar"
    elements:
      - <foo>
      - <bar>
    admin_library: ckeditor5/internal.admin.basic
    conditions: []
YAML,
    ];
    yield 'explicitly unconditional' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    label: "Foo bar"
    elements:
      - <foo>
      - <bar>
    admin_library: ckeditor5/internal.admin.basic
    conditions: false
YAML,
    ];

    // Add a plugin class; observe what additional requirements need to be met.
    yield 'added plugin class' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    class: Drupal\ckeditor5_invalid_plugin\Plugin\CKEditor5Plugin\FooBar
    label: "Foo bar"
    elements:
      - <foo>
      - <bar>
    admin_library: ckeditor5/internal.admin.basic
YAML,
      InvalidPluginDefinitionException::class,
      'The CKEditor 5 "ckeditor5_invalid_plugin_foo_bar" provides a plugin class: "Drupal\ckeditor5_invalid_plugin\Plugin\CKEditor5Plugin\FooBar", but it does not exist.',
    ];

    yield 'defined minimal (but not yet valid) plugin class' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    class: Drupal\ckeditor5_invalid_plugin\Plugin\CKEditor5Plugin\FooBar
    label: "Foo bar"
    elements:
      - <foo>
      - <bar>
    admin_library: ckeditor5/internal.admin.basic
YAML,
      InvalidPluginDefinitionException::class,
      'CKEditor 5 plugins must implement \Drupal\ckeditor5\Plugin\CKEditor5PluginInterface. "ckeditor5_invalid_plugin_foo_bar" does not.',
      [
        'src' => [
          'Plugin' => [
            'CKEditor5Plugin' => [
              'FooBar.php' => <<<'PHP'
<?php
namespace Drupal\ckeditor5_invalid_plugin\Plugin\CKEditor5Plugin;
class FooBar {}
PHP,
            ],
          ],
        ],
      ],
    ];

    yield 'defined minimal and valid plugin class' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    class: Drupal\ckeditor5_invalid_plugin\Plugin\CKEditor5Plugin\FooBar
    label: "Foo bar"
    elements:
      - <foo>
      - <bar>
    admin_library: ckeditor5/internal.admin.basic
YAML,
      NULL,
      NULL,
      [
        'src' => [
          'Plugin' => [
            'CKEditor5Plugin' => [
              'FooBar.php' => <<<'PHP'
<?php
namespace Drupal\ckeditor5_invalid_plugin\Plugin\CKEditor5Plugin;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
class FooBar extends CKEditor5PluginDefault {}
PHP,
            ],
          ],
        ],
      ],
    ];

    // Make the plugin configurable; observe what additional requirements need
    // to be met.
    yield 'defined minimal and valid plugin class made configurable but not really anything configurable' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    class: Drupal\ckeditor5_invalid_plugin\Plugin\CKEditor5Plugin\FooBar
    label: "Foo bar"
    elements:
      - <foo>
      - <bar>
    admin_library: ckeditor5/internal.admin.basic
YAML,
      NULL,
      NULL,
      [
        'src' => [
          'Plugin' => [
            'CKEditor5Plugin' => [
              'FooBar.php' => <<<'PHP'
<?php
namespace Drupal\ckeditor5_invalid_plugin\Plugin\CKEditor5Plugin;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\Core\Form\FormStateInterface;
class FooBar extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface {
  use CKEditor5PluginConfigurableTrait;
  public function defaultConfiguration() { return []; }
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) { return []; }
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}
}
PHP,
            ],
          ],
        ],
      ],
    ];

    yield 'defined minimal and valid plugin class made configurable: invalid if config schema is missing' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    class: Drupal\ckeditor5_invalid_plugin\Plugin\CKEditor5Plugin\FooBar
    label: "Foo bar"
    elements:
      - <foo>
      - <bar>
    admin_library: ckeditor5/internal.admin.basic
YAML,
      InvalidPluginDefinitionException::class,
      'The "ckeditor5_invalid_plugin_foo_bar" CKEditor 5 plugin definition is configurable, has non-empty default configuration but has no config schema. Config schema is required for validation.',
      [
        'src' => [
          'Plugin' => [
            'CKEditor5Plugin' => [
              'FooBar.php' => <<<'PHP'
<?php
namespace Drupal\ckeditor5_invalid_plugin\Plugin\CKEditor5Plugin;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\Core\Form\FormStateInterface;
class FooBar extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface {
  use CKEditor5PluginConfigurableTrait;
  public function defaultConfiguration() { return ['foo' => FALSE]; }
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) { return []; }
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}
}
PHP,
            ],
          ],
        ],
      ],
    ];

    yield 'defined minimal and valid plugin class made configurable: valid if config schema is present' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    class: Drupal\ckeditor5_invalid_plugin\Plugin\CKEditor5Plugin\FooBar
    label: "Foo bar"
    elements:
      - <foo>
      - <bar>
    admin_library: ckeditor5/internal.admin.basic
YAML,
      NULL,
      NULL,
      [
        'config' => [
          'schema' => [
            'ckeditor5_invalid_plugin.schema.yml' => <<<YAML
ckeditor5.plugin.ckeditor5_invalid_plugin_foo_bar:
  type: mapping
  label: 'Foo Bar'
  mapping:
    foo:
      type: boolean
      label: 'Foo'
YAML,
          ],
        ],
        'src' => [
          'Plugin' => [
            'CKEditor5Plugin' => [
              'FooBar.php' => <<<'PHP'
<?php
namespace Drupal\ckeditor5_invalid_plugin\Plugin\CKEditor5Plugin;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\Core\Form\FormStateInterface;
class FooBar extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface {
  use CKEditor5PluginConfigurableTrait;
  public function defaultConfiguration() { return ['foo' => FALSE]; }
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) { return []; }
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}
}
PHP,
            ],
          ],
        ],
      ],
    ];

    yield 'defined minimal and valid plugin class made configurable: invalid if config schema is present but incomplete' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    class: Drupal\ckeditor5_invalid_plugin\Plugin\CKEditor5Plugin\FooBar
    label: "Foo bar"
    elements:
      - <foo>
      - <bar>
    admin_library: ckeditor5/internal.admin.basic
YAML,
      InvalidPluginDefinitionException::class,
      'The "ckeditor5_invalid_plugin_foo_bar" CKEditor 5 plugin definition is configurable, but its default configuration does not match its config schema. The following errors were found: [foo] The configuration property foo.bar doesn\'t exist, [baz] missing schema.',
      [
        'config' => [
          'schema' => [
            'ckeditor5_invalid_plugin.schema.yml' => <<<YAML
ckeditor5.plugin.ckeditor5_invalid_plugin_foo_bar:
  type: mapping
  label: 'Foo Bar'
  mapping:
    foo:
      type: boolean
      label: 'Foo'
YAML,
          ],
        ],
        'src' => [
          'Plugin' => [
            'CKEditor5Plugin' => [
              'FooBar.php' => <<<'PHP'
<?php
namespace Drupal\ckeditor5_invalid_plugin\Plugin\CKEditor5Plugin;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\Core\Form\FormStateInterface;
class FooBar extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface {
  use CKEditor5PluginConfigurableTrait;
  public function defaultConfiguration() { return ['foo' => ['bar' => TRUE], 'baz' => FALSE]; }
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) { return []; }
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}
}
PHP,
            ],
          ],
        ],
      ],
    ];

    yield 'defined minimal and valid plugin class made configurable: valid if config schema is present and complete' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    class: Drupal\ckeditor5_invalid_plugin\Plugin\CKEditor5Plugin\FooBar
    label: "Foo bar"
    elements:
      - <foo>
      - <bar>
    admin_library: ckeditor5/internal.admin.basic
YAML,
      NULL,
      NULL,
      [
        'config' => [
          'schema' => [
            'ckeditor5_invalid_plugin.schema.yml' => <<<YAML
ckeditor5.plugin.ckeditor5_invalid_plugin_foo_bar:
  type: mapping
  label: 'Foo Bar'
  mapping:
    foo:
      type: boolean
      label: 'Foo'
    bar:
      type: boolean
      label: 'Bar'
YAML,
          ],
        ],
        'src' => [
          'Plugin' => [
            'CKEditor5Plugin' => [
              'FooBar.php' => <<<'PHP'
<?php
namespace Drupal\ckeditor5_invalid_plugin\Plugin\CKEditor5Plugin;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\Core\Form\FormStateInterface;
class FooBar extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface {
  use CKEditor5PluginConfigurableTrait;
  public function defaultConfiguration() { return ['foo' => FALSE, 'bar' => FALSE]; }
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) { return []; }
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}
}
PHP,
            ],
          ],
        ],
      ],
    ];

    yield 'invalid condition: requiresConfiguration not specifying a configuration array' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    label: "Foo bar"
    elements: false
    conditions:
      requiresConfiguration: true
YAML,
      InvalidPluginDefinitionException::class,
      'The "ckeditor5_invalid_plugin_foo_bar" CKEditor 5 plugin definition has an invalid "drupal.conditions" item. "requiresConfiguration" is set to an invalid value. An array structure matching the required configuration for this plugin must be specified.',
    ];

    yield 'invalid condition: requiresConfiguration without configurable plugin' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    label: "Foo bar"
    elements: false
    conditions:
      requiresConfiguration:
        allow_resize: true
YAML,
      InvalidPluginDefinitionException::class,
      'The "ckeditor5_invalid_plugin_foo_bar" CKEditor 5 plugin definition has an invalid "drupal.conditions" item. "requiresConfiguration" is set to an invalid value. This condition type is only available for CKEditor 5 plugins implementing CKEditor5PluginConfigurableInterface.',
    ];

    yield 'invalid condition: requiresConfiguration with configurable plugin but required configuration does not match config schema' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    class: Drupal\ckeditor5_invalid_plugin\Plugin\CKEditor5Plugin\FooBar
    label: "Foo bar"
    elements: false
    conditions:
      requiresConfiguration:
        allow_resize: true
YAML,
      InvalidPluginDefinitionException::class,
      'The "ckeditor5_invalid_plugin_foo_bar" CKEditor 5 plugin definition has an invalid "drupal.conditions" item. "requiresConfiguration" is set to an invalid value. The required configuration does not match its config schema. The following errors were found: [allow_resize] The configuration property allow_resize doesn\'t exist.',
      [
        'config' => [
          'schema' => [
            'ckeditor5_invalid_plugin.schema.yml' => <<<YAML
ckeditor5.plugin.ckeditor5_invalid_plugin_foo_bar:
  type: mapping
  label: 'Foo Bar'
  mapping:
    foo:
      type: boolean
      label: 'Foo'
YAML,
          ],
        ],
        'src' => [
          'Plugin' => [
            'CKEditor5Plugin' => [
              'FooBar.php' => <<<'PHP'
<?php
namespace Drupal\ckeditor5_invalid_plugin\Plugin\CKEditor5Plugin;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\Core\Form\FormStateInterface;
class FooBar extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface {
  use CKEditor5PluginConfigurableTrait;
  public function defaultConfiguration() { return ['foo' => FALSE]; }
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) { return []; }
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}
}
PHP,
            ],
          ],
        ],
      ],
    ];

    yield 'valid condition: requiresConfiguration' => [
      <<<YAML
ckeditor5_invalid_plugin_foo_bar:
  ckeditor5:
    plugins: {}
  drupal:
    class: Drupal\ckeditor5_invalid_plugin\Plugin\CKEditor5Plugin\FooBar
    label: "Foo bar"
    elements: false
    conditions:
      requiresConfiguration:
        foo: true
YAML,
      NULL,
      NULL,
      [
        'config' => [
          'schema' => [
            'ckeditor5_invalid_plugin.schema.yml' => <<<YAML
ckeditor5.plugin.ckeditor5_invalid_plugin_foo_bar:
  type: mapping
  label: 'Foo Bar'
  mapping:
    foo:
      type: boolean
      label: 'Foo'
YAML,
          ],
        ],
        'src' => [
          'Plugin' => [
            'CKEditor5Plugin' => [
              'FooBar.php' => <<<'PHP'
<?php
namespace Drupal\ckeditor5_invalid_plugin\Plugin\CKEditor5Plugin;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\Core\Form\FormStateInterface;
class FooBar extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface {
  use CKEditor5PluginConfigurableTrait;
  public function defaultConfiguration() { return ['foo' => FALSE]; }
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) { return []; }
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}
}
PHP,
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Tests detection of invalid CKEditor5PluginElementsSubsetInterface classes.
   *
   * @dataProvider providerProvidedElementsInvalidElementSubset
   */
  public function testProvidedElementsInvalidElementSubset(array $configured_subset, string $expected_exception_message): void {
    $this->enableModules(['ckeditor5_plugin_elements_subset']);

    // Configure the sneaky superset plugin.
    $sneaky_plugin_id = 'ckeditor5_plugin_elements_subset_sneakySuperset';
    $text_editor = Editor::create([
      'format' => 'dummy',
      'editor' => 'ckeditor5',
      'settings' => [
        'plugins' => [
          $sneaky_plugin_id => ['configured_subset' => $configured_subset],
        ],
      ],
    ]);

    // Invalid subsets are allowed on unsaved Text Editor config entities,
    // because they may have invalid configuration.
    $text_editor->enforceIsNew(FALSE);

    // No exception when getting all provided elements.
    $this->assertGreaterThan(0, count($this->manager->getProvidedElements()));

    // No exception when getting the sneaky plugin's provided elements.
    $this->assertGreaterThan(0, count($this->manager->getProvidedElements([$sneaky_plugin_id])));

    // Exception when getting the sneaky plugin's provided elements *and* a text
    // editor config entity is passed: only then can a subset be generated based
    // on configuration.
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage($expected_exception_message);
    $this->manager->getProvidedElements([$sneaky_plugin_id], $text_editor);
  }

  /**
   * Data provider.
   *
   * @return array
   *   Test scenarios.
   */
  public static function providerProvidedElementsInvalidElementSubset(): array {
    $random_tag_name = strtolower(Random::machineName());
    $random_tag = "<$random_tag_name>";
    return [
      'superset: random tag not listed in the plugin definition' => [
        [$random_tag],
        "The \"ckeditor5_plugin_elements_subset_sneakySuperset\" CKEditor 5 plugin implements ::getElementsSubset() and did not return a subset, the following tags are absent from the plugin definition: \"$random_tag\".",
      ],
      'subset that omits the essential creatable tag' => [
        ['<bar baz>'],
        'The "ckeditor5_plugin_elements_subset_sneakySuperset" CKEditor 5 plugin implements ::getElementsSubset() and did return a subset ("<bar baz>") but the following tags can no longer be created: "<bar>".',
      ],
      'subset that tries to leverage the `<$any-html5-element>` wildcard tag but picks a concrete tag that the wildcard tag does not resolve into' => [
        ['<drupal-media class="sensational">'],
        'The "ckeditor5_plugin_elements_subset_sneakySuperset" CKEditor 5 plugin implements ::getElementsSubset() and did not return a subset, the following tags are absent from the plugin definition: "<drupal-media class="sensational">".',
      ],
    ];
  }

  /**
   * Tests the enabling of plugins.
   */
  public function testEnabledPlugins(): void {
    $editor = Editor::load('basic_html');

    // Case 1: no extra CKEditor 5 plugins.
    $definitions = array_keys($this->manager->getEnabledDefinitions($editor));
    $default_plugins = [
      'ckeditor5_autoformat',
      'ckeditor5_bold',
      'ckeditor5_emphasis',
      'ckeditor5_essentials',
      'ckeditor5_globalAttributeDir',
      'ckeditor5_globalAttributeLang',
      'ckeditor5_heading',
      'ckeditor5_htmlComments',
      'ckeditor5_paragraph',
      'ckeditor5_pasteFromOffice',
    ];
    $this->assertSame($default_plugins, $definitions, 'No CKEditor 5 plugins found besides the built-in ones.');
    $default_libraries = [
      'ckeditor5/internal.drupal.ckeditor5',
      'ckeditor5/internal.drupal.ckeditor5.emphasis',
      'ckeditor5/internal.drupal.ckeditor5.htmlEngine',
      'core/ckeditor5.autoformat',
      'core/ckeditor5.basic',
      'core/ckeditor5.essentials',
      'core/ckeditor5.htmlSupport',
      'core/ckeditor5.pasteFromOffice',
    ];
    $this->assertSame($default_libraries, $this->manager->getEnabledLibraries($editor));

    // Enable the CKEditor 5 Test module, which has the layercake plugin and
    // clear the editor manager's static cache so that it is picked up.
    $this->enableModules(['ckeditor5_test']);
    $this->manager = $this->container->get('plugin.manager.ckeditor5.plugin');
    $this->manager->clearCachedDefinitions();

    // Case 2: The CKEditor 5 layercake plugin is available and library should
    // NOT be loaded if its toolbar items are not enabled.
    $this->assertSame($default_plugins, array_keys($this->manager->getEnabledDefinitions($editor)));
    $this->assertSame($default_libraries, $this->manager->getEnabledLibraries($editor));

    // Case 3: The CKEditor 5 layercake plugin is available and library should
    // be loaded without having to enable plugins.
    $settings = $editor->getSettings();
    $settings['toolbar']['items'][] = 'simpleBox';
    $editor->setSettings($settings);
    $plugin_ids = array_keys($this->manager->getEnabledDefinitions($editor));
    $default_plugins_with_layercake = array_merge($default_plugins, ['ckeditor5_test_layercake']);

    // Sort on plugin id.
    asort($default_plugins_with_layercake);
    $this->assertSame(array_values($default_plugins_with_layercake), $plugin_ids);
    $default_libraries_with_layercake = array_merge($default_libraries, ['ckeditor5_test/layercake']);
    sort($default_libraries_with_layercake);
    $this->assertSame($default_libraries_with_layercake, $this->manager->getEnabledLibraries($editor));

    // Enable media embed filter which the CKEditor 5 media plugin requires.
    $editor->getFilterFormat()->setFilterConfig('media_embed', ['status' => TRUE])->save();

    // Case 4: The CKEditor 5 media plugin should be enabled and the library
    // should be available now that the media_embed is enabled.
    $plugin_ids = array_keys($this->manager->getEnabledDefinitions($editor));
    $expected_plugins = array_merge($default_plugins, [
      'ckeditor5_drupalMediaCaption',
      'ckeditor5_test_layercake',
      'media_media',
      'media_mediaAlign',
    ]);
    sort($expected_plugins);
    $this->assertSame($expected_plugins, $plugin_ids);
    $expected_libraries = array_merge($default_libraries, [
      'ckeditor5/internal.drupal.ckeditor5.media',
      'ckeditor5/internal.drupal.ckeditor5.mediaAlign',
      'ckeditor5_test/layercake',
    ]);
    sort($expected_libraries);
    $this->assertSame($expected_libraries, $this->manager->getEnabledLibraries($editor));

    // Enable the CKEditor 5 Plugin Conditions Test module, which has the
    // ckeditor5_plugin_conditions_test_plugins_condition plugin which is
    // conditionally enabled. Clear the editor manager's static cache so that it
    // is picked up.
    $this->enableModules(['ckeditor5_plugin_conditions_test']);
    $this->manager = $this->container->get('plugin.manager.ckeditor5.plugin');
    $this->manager->clearCachedDefinitions();

    // Case 5: just installing the ckeditor5_plugin_conditions_test module does
    // not enable its conditionally enabled plugin.
    $this->assertSame($expected_plugins, $plugin_ids);
    $this->assertSame($expected_libraries, $this->manager->getEnabledLibraries($editor));

    // Case 6: placing the table plugin's button enables the table plugin, but
    // also implicitly enables the conditionally enabled plugin.
    $settings['toolbar']['items'][] = 'insertTable';
    $editor->setSettings($settings);
    $plugin_ids = array_keys($this->manager->getEnabledDefinitions($editor));
    $expected_plugins = array_merge($expected_plugins, ['ckeditor5_table', 'ckeditor5_plugin_conditions_test_plugins_condition']);
    sort($expected_plugins);
    $this->assertSame(array_values($expected_plugins), $plugin_ids);
    $expected_libraries = array_merge($default_libraries, [
      'ckeditor5/internal.drupal.ckeditor5.media',
      'ckeditor5/internal.drupal.ckeditor5.mediaAlign',
      'ckeditor5_test/layercake',
      'core/ckeditor5.table',
    ]);
    sort($expected_libraries);
    $this->assertSame($expected_libraries, $this->manager->getEnabledLibraries($editor));

    // Case 7: GHS is enabled for other text editors if they are using a
    // CKEditor 5 plugin that uses wildcard tags.
    $settings['toolbar']['items'][] = 'alignment';
    $editor->setSettings($settings);
    $plugin_ids = array_keys($this->manager->getEnabledDefinitions($editor));
    $expected_plugins = array_merge($expected_plugins, [
      'ckeditor5_alignment',
      'ckeditor5_wildcardHtmlSupport',
    ]);
    sort($expected_plugins);
    $this->assertSame(array_values($expected_plugins), $plugin_ids);
    $expected_libraries = array_merge($expected_libraries, [
      'core/ckeditor5.alignment',
    ]);
    sort($expected_libraries);
    $this->assertSame($expected_libraries, $this->manager->getEnabledLibraries($editor));

    // Case 8: GHS is enabled for Full HTML (or any other text format that has
    // no TYPE_HTML_RESTRICTOR filters).
    $editor = Editor::load('full_html');
    $definitions = array_keys($this->manager->getEnabledDefinitions($editor));
    $default_plugins = [
      'ckeditor5_arbitraryHtmlSupport',
      'ckeditor5_autoformat',
      'ckeditor5_bold',
      'ckeditor5_emphasis',
      'ckeditor5_essentials',
      'ckeditor5_heading',
      'ckeditor5_htmlComments',
      'ckeditor5_paragraph',
      'ckeditor5_pasteFromOffice',
    ];
    $this->assertSame($default_plugins, $definitions, 'No CKEditor 5 plugins found besides the built-in ones.');
    $default_libraries = [
      'ckeditor5/internal.drupal.ckeditor5',
      'ckeditor5/internal.drupal.ckeditor5.emphasis',
      'ckeditor5/internal.drupal.ckeditor5.htmlEngine',
      'core/ckeditor5.autoformat',
      'core/ckeditor5.basic',
      'core/ckeditor5.essentials',
      'core/ckeditor5.htmlSupport',
      'core/ckeditor5.pasteFromOffice',
    ];
    $this->assertSame($default_libraries, $this->manager->getEnabledLibraries($editor));
  }

  /**
   * Tests the parsing of CKEditor 5 plugin element config.
   *
   * @param string[] $plugins
   *   The plugins to parse the elements list from.
   * @param array $text_editor_settings
   *   The text editor settings.
   * @param array $expected_elements
   *   An array of expected allowed elements an attributes in the structure
   *   used by filter_html.
   * @param string $expected_readable_string
   *   The expected allowed tags and attributes as a string, typically used
   *   in the filter_html "Allowed tags" field.
   *
   * @covers \Drupal\ckeditor5\Plugin\CKEditor5PluginManager::getProvidedElements
   * @dataProvider providerTestProvidedElements
   */
  public function testProvidedElements(array $plugins, array $text_editor_settings, array $expected_elements, string $expected_readable_string): void {
    $this->enableModules(['ckeditor5_plugin_elements_test']);

    $text_editor = Editor::create([
      'format' => 'dummy',
      'editor' => 'ckeditor5',
      'settings' => $text_editor_settings,
      'image_upload' => [
        'status' => FALSE,
      ],
    ]);
    FilterFormat::create([
      'format' => 'dummy',
      'name' => 'dummy',
    ])->save();
    $this->assertConfigSchema(
      $this->typedConfig,
      $text_editor->getConfigDependencyName(),
      $text_editor->toArray()
    );
    // @todo Remove in https://www.drupal.org/project/drupal/issues/3361534, which moves this into ::assertConfigSchema()
    $this->assertSame([], array_map(
      fn ($v) => sprintf("[%s] %s", $v->getPropertyPath(), (string) $v->getMessage()),
      iterator_to_array($this->typedConfig->createFromNameAndData($text_editor->getConfigDependencyName(), $text_editor->toArray())->validate())
    ));

    $provided_elements = $this->manager->getProvidedElements($plugins, $text_editor);
    $this->assertSame($expected_elements, $provided_elements);
    $this->assertSame($expected_readable_string, (new HTMLRestrictions($provided_elements))->toFilterHtmlAllowedTagsString());
  }

  /**
   * Provides uses cases enabling different elements and the expected results.
   */
  public static function providerTestProvidedElements(): array {
    $text_align_classes = [
      'text-align-left' => TRUE,
      'text-align-center' => TRUE,
      'text-align-right' => TRUE,
      'text-align-justify' => TRUE,
    ];

    return [
      'sourceEditing' => [
        'plugins' => ['ckeditor5_sourceEditing'],
        'text_editor_settings' => [],
        'expected_elements' => [],
        'expected_readable_string' => '',
      ],
      'imageResize' => [
        'plugins' => ['ckeditor5_imageResize'],
        'text_editor_settings' => [],
        'expected_elements' => [],
        'expected_readable_string' => '',
      ],
      'language' => [
        'plugins' => ['ckeditor5_language'],
        'text_editor_settings' => [],
        'expected_elements' => [
          'span' => [
            'lang' => TRUE,
            'dir' => TRUE,
          ],
        ],
        'expected_readable_string' => '<span lang dir>',
      ],
      'alignment and heading' => [
        'plugins' => [
          'ckeditor5_alignment',
          'ckeditor5_heading',
          'ckeditor5_paragraph',
        ],
        'text_editor_settings' => [
          'plugins' => [
            'ckeditor5_heading' => Heading::DEFAULT_CONFIGURATION,
          ],
        ],
        'expected_elements' => [
          'p' => [
            'class' => $text_align_classes,
          ],
          'h2' => [
            'class' => $text_align_classes,
          ],
          'h3' => [
            'class' => $text_align_classes,
          ],
          'h4' => [
            'class' => $text_align_classes,
          ],
          'h5' => [
            'class' => $text_align_classes,
          ],
          'h6' => [
            'class' => $text_align_classes,
          ],
        ],
        'expected_readable_string' => '<p class="text-align-left text-align-center text-align-right text-align-justify"> <h2 class="text-align-left text-align-center text-align-right text-align-justify"> <h3 class="text-align-left text-align-center text-align-right text-align-justify"> <h4 class="text-align-left text-align-center text-align-right text-align-justify"> <h5 class="text-align-left text-align-center text-align-right text-align-justify"> <h6 class="text-align-left text-align-center text-align-right text-align-justify">',
      ],
      'alignment and heading, but all class values allowed for headings' => [
        'plugins' => [
          'ckeditor5_alignment',
          'ckeditor5_heading',
          'ckeditor5_paragraph',
          'ckeditor5_plugin_elements_test_headingsUseClassAnyValue',
        ],
        'text_editor_settings' => [
          'plugins' => [
            'ckeditor5_heading' => Heading::DEFAULT_CONFIGURATION,
          ],
        ],
        'expected_elements' => [
          'p' => [
            'class' => $text_align_classes,
          ],
          'h2' => [
            'class' => TRUE,
          ],
          'h3' => [
            'class' => TRUE,
          ],
          'h4' => [
            'class' => TRUE,
          ],
          'h5' => [
            'class' => TRUE,
          ],
          'h6' => [
            'class' => TRUE,
          ],
          'h1' => [
            'class' => TRUE,
          ],
        ],
        'expected_readable_string' => '<p class="text-align-left text-align-center text-align-right text-align-justify"> <h2 class> <h3 class> <h4 class> <h5 class> <h6 class> <h1 class>',
      ],
      'heading text container combo' => [
        'plugins' => [
          'ckeditor5_plugin_elements_test_headingCombo',
          'ckeditor5_paragraph',
        ],
        'text_editor_settings' => [
          'plugins' => [],
          // Deviate from the default toolbar items because that would cause
          // the `ckeditor5_heading` plugin to be enabled.
          // @see \Drupal\ckeditor5\Plugin\Editor\CKEditor5::getDefaultSettings()
          'toolbar' => ['items' => ['bold', 'italic']],
        ],
        'expected_elements' => [
          'p' => [
            'data-everytextcontainer' => TRUE,
          ],
          'h1' => [
            'data-justheading' => TRUE,
            'data-everytextcontainer' => TRUE,
          ],
        ],
        'expected_readable_string' => '<p data-everytextcontainer> <h1 data-justheading data-everytextcontainer>',
      ],
      'headings plus headings with attributes' => [
        'plugins' => [
          'ckeditor5_plugin_elements_test_headingsWithOtherAttributes',
          'ckeditor5_heading',
        ],
        'text_editor_settings' => [
          'plugins' => [
            'ckeditor5_heading' => Heading::DEFAULT_CONFIGURATION,
          ],
        ],
        'expected_elements' => [
          'h2' => [
            'class' => [
              'additional-allowed-class' => TRUE,
            ],
          ],
          'h3' => [
            'data-just-h3' => TRUE,
            'data-just-h3-limited' => [
              'i-am-the-only-allowed-value' => TRUE,
            ],
          ],
          'h4' => FALSE,
          'h5' => [
            'data-just-h5-limited' => [
              'first-allowed-value' => TRUE,
              'second-allowed-value' => TRUE,
            ],
          ],
          'h6' => FALSE,
          'h1' => [
            'data-just-h1' => TRUE,
          ],
        ],
        'expected_readable_string' => '<h2 class="additional-allowed-class"> <h3 data-just-h3 data-just-h3-limited="i-am-the-only-allowed-value"> <h4> <h5 data-just-h5-limited="first-allowed-value second-allowed-value"> <h6> <h1 data-just-h1>',
      ],
      'headings plus headings with attributes and alignment' => [
        'plugins' => [
          'ckeditor5_plugin_elements_test_headingsWithOtherAttributes',
          'ckeditor5_heading',
          'ckeditor5_alignment',
        ],
        'text_editor_settings' => [
          'plugins' => [
            'ckeditor5_heading' => Heading::DEFAULT_CONFIGURATION,
          ],
        ],
        'expected_elements' => [
          'h2' => [
            'class' => ['additional-allowed-class' => TRUE] + $text_align_classes,
          ],
          'h3' => [
            'data-just-h3' => TRUE,
            'data-just-h3-limited' => [
              'i-am-the-only-allowed-value' => TRUE,
            ],
            'class' => $text_align_classes,
          ],
          'h4' => [
            'class' => $text_align_classes,
          ],
          'h5' => [
            'data-just-h5-limited' => [
              'first-allowed-value' => TRUE,
              'second-allowed-value' => TRUE,
            ],
            'class' => $text_align_classes,
          ],
          'h6' => [
            'class' => $text_align_classes,
          ],
          'h1' => [
            'data-just-h1' => TRUE,
            'class' => $text_align_classes,
          ],
        ],
        'expected_readable_string' => '<h2 class="additional-allowed-class text-align-left text-align-center text-align-right text-align-justify"> <h3 data-just-h3 data-just-h3-limited="i-am-the-only-allowed-value" class="text-align-left text-align-center text-align-right text-align-justify"> <h4 class="text-align-left text-align-center text-align-right text-align-justify"> <h5 data-just-h5-limited="first-allowed-value second-allowed-value" class="text-align-left text-align-center text-align-right text-align-justify"> <h6 class="text-align-left text-align-center text-align-right text-align-justify"> <h1 data-just-h1 class="text-align-left text-align-center text-align-right text-align-justify">',
      ],
    ];
  }

  /**
   * Tests the logic of findPluginSupportingElement.
   *
   * When multiple plugins support a given tag, this method decides which plugin
   * to return based on which provides the broadest attribute support.
   *
   * @covers \Drupal\ckeditor5\Plugin\CKEditor5PluginManager::findPluginSupportingElement
   *
   * @dataProvider providerTestPluginSupportingElement
   */
  public function testPluginSupportingElement(string $tag, ?string $expected_plugin_id): void {
    $this->enableModules(['ckeditor5_definition_supporting_element']);
    $plugin_id = $this->manager->findPluginSupportingElement($tag);
    $this->assertSame($expected_plugin_id, $plugin_id);
  }

  /**
   * Provides use cases for findPluginSupportingElement().
   */
  public static function providerTestPluginSupportingElement() {
    return [
      'tag that belongs to a superset' => [
        'tag' => 'h2',
        'expected_plugin_id' => 'ckeditor5_heading',
      ],
      'tag only available as tag' => [
        'tag' => 'nav',
        'expected_plugin_id' => 'ckeditor5_definition_supporting_element_just_nav',
      ],
      'between just tag, full use of class, and constrained use of class, return full use of class' => [
        'tag' => 'article',
        'expected_plugin_id' => 'ckeditor5_definition_supporting_element_article_class',
      ],
      'between just tag and full use of class, return full use of class' => [
        'tag' => 'footer',
        'expected_plugin_id' => 'ckeditor5_definition_supporting_element_footer_class',
      ],
      'between just tag and constrained use of class, return constrained use of class' => [
        'tag' => 'aside',
        'expected_plugin_id' => 'ckeditor5_definition_supporting_element_aside_class_with_values',
      ],
      'between full use of class and constrained use of class, return full use of class' => [
        'tag' => 'main',
        'expected_plugin_id' => 'ckeditor5_definition_supporting_element_main_class',
      ],
      'between one plugin allows one attribute, second allows two, return the one that allows two' => [
        'tag' => 'figure',
        'expected_plugin_id' => 'ckeditor5_definition_supporting_element_figure_two_attrib',
      ],
      'between one plugin allows one attribute, second allows two (but appearing in opposite order), still return the one that allows two' => [
        'tag' => 'dialog',
        'expected_plugin_id' => 'ckeditor5_definition_supporting_element_dialog_two_attrib',
      ],
      'tag that belongs to a plugin with conditions' => [
        'tag' => 'drupal-media',
        'expected_plugin_id' => NULL,
      ],
    ];
  }

  /**
   * @covers \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition::validateCKEditor5Aspects
   */
  public function testAutomaticLinkDecoratorsDisallowed(): void {
    $this->expectException(InvalidPluginDefinitionException::class);
    $this->expectExceptionMessage('The "ckeditor5_automatic_link_decorator_test_llamaClass" CKEditor 5 plugin definition specifies an automatic decorator, this is not supported. Use the Drupal filter system instead.');

    $this->enableModules(['ckeditor5_automatic_link_decorator_test']);

    $this->manager->getDefinitions();
  }

  /**
   * @covers \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition::validateCKEditor5Aspects
   */
  public function testExternalLinkAutomaticLinkDecoratorDisallowed(): void {
    $this->expectException(InvalidPluginDefinitionException::class);
    $this->expectExceptionMessage('The "ckeditor5_automatic_link_decorator_test_2_addTargetToExternalLinks" CKEditor 5 plugin definition specifies an automatic decorator, this is not supported. Use the Drupal filter system instead.');

    $this->enableModules(['ckeditor5_automatic_link_decorator_test_2']);

    $this->manager->getDefinitions();
  }

  /**
   * @covers \Drupal\ckeditor5\Plugin\CKEditor5PluginManager::getDiscovery
   * @dataProvider providerTestDerivedPluginDefinitions
   */
  public function testDerivedPluginDefinitions(string $yaml, ?string $expected_exception = NULL, ?string $expected_message = NULL, array $additional_files = [], ?array $expected_derived_plugin_definitions = NULL): void {
    if ($expected_exception) {
      $this->expectException($expected_exception);
    }
    if ($expected_message) {
      $this->expectExceptionMessage($expected_message);
    }
    $container = $this->mockModuleInVfs('ckeditor5_derived_plugin', $yaml, $additional_files);

    $actual_definitions = $container->get('plugin.manager.ckeditor5.plugin')->getDefinitions();
    $this->assertEquals($expected_derived_plugin_definitions, $actual_definitions);
  }

  /**
   * Data provider.
   *
   * @return \Generator
   *   Test scenarios.
   */
  public static function providerTestDerivedPluginDefinitions(): \Generator {
    // Defaults inherited from CKEditor5AspectsOfCKEditor5Plugin.
    $ckeditor5_aspects_defaults = get_class_vars(CKEditor5AspectsOfCKEditor5Plugin::class);
    // Defaults inherited from DrupalAspectsOfCKEditor5Plugin.
    $drupal_aspects_defaults = get_class_vars(DrupalAspectsOfCKEditor5Plugin::class);

    $simple_deriver_additional_files = [
      'src' => [
        'Plugin' => [
          'CKEditor5Plugin' => [
            'SimpleDeriver.php' => <<<'PHP'
<?php
namespace Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\Component\Plugin\Derivative\DeriverBase;
class SimpleDeriver extends DeriverBase {
  public function getDerivativeDefinitions($base_plugin_definition) {
    assert($base_plugin_definition instanceof CKEditor5PluginDefinition);
    foreach (['bar', 'baz'] as $id) {
      $definition = $base_plugin_definition->toArray();
      $definition['id'] = $id;
      $definition['drupal']['label'] = sprintf("Foo %s", $id);
      $this->derivatives[$id] = new CKEditor5PluginDefinition($definition);
    }
    return $this->derivatives;
  }
}
PHP,
          ],
        ],
      ],
    ];

    yield 'INVALID: simple deriver but without `drupal.elements` in the base definition and it not getting set by the deriver' => [
      <<<YAML
ckeditor5_derived_plugin_foo:
  ckeditor5:
    plugins: {}
  drupal:
    deriver: Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin\SimpleDeriver
YAML,
      InvalidPluginDefinitionException::class,
      'The "ckeditor5_derived_plugin_foo:bar" CKEditor 5 derived plugin definition must contain a "drupal.elements" key.',
      $simple_deriver_additional_files,
    ];

    yield 'INVALID: simple deriver but without `ckeditor5.plugins` in the base definition and it not getting set by the deriver' => [
      <<<YAML
ckeditor5_derived_plugin_foo:
  ckeditor5: {}
  drupal:
    elements: false
    deriver: Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin\SimpleDeriver
YAML,
      \ArgumentCountError::class,
      NULL,
      $simple_deriver_additional_files,
    ];

    yield 'INVALID: simple deriver but without `ckeditor5` in the base definition and it not getting set by the deriver' => [
      <<<YAML
ckeditor5_derived_plugin_foo:
  drupal:
    elements: false
    deriver: Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin\SimpleDeriver
YAML,
      InvalidPluginDefinitionException::class,
      'The "ckeditor5_derived_plugin_foo:bar" CKEditor 5 derived plugin definition must contain a "ckeditor5" key.',
      $simple_deriver_additional_files,
    ];

    yield 'INVALID: simple deriver which returns arrays instead of CKEditor5PluginDefinition instances' => [
      <<<YAML
ckeditor5_derived_plugin_foo:
  ckeditor5:
    plugins: {}
  drupal:
    elements: false
    deriver: Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin\SimpleDeriver
YAML,
      InvalidPluginDefinitionException::class,
      'The "ckeditor5_derived_plugin_foo:bar" CKEditor 5 plugin definition must extend Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition',
      [
        'src' => [
          'Plugin' => [
            'CKEditor5Plugin' => [
              'SimpleDeriver.php' => <<<'PHP'
<?php
namespace Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\Component\Plugin\Derivative\DeriverBase;
class SimpleDeriver extends DeriverBase {
  public function getDerivativeDefinitions($base_plugin_definition) {
    assert($base_plugin_definition instanceof CKEditor5PluginDefinition);
    foreach (['bar', 'baz'] as $id) {
      $definition = $base_plugin_definition->toArray();
      $definition['id'] = $id;
      $definition['drupal']['label'] = sprintf("Foo %s", $id);
      $this->derivatives[$id] = $definition;
    }
    return $this->derivatives;
  }
}
PHP,
            ],
          ],
        ],
      ],
    ];

    yield 'VALID: simple deriver, base definition in YAML' => [
      <<<YAML
ckeditor5_derived_plugin_foo:
  ckeditor5:
    plugins: {}
  drupal:
    elements: false
    deriver: Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin\SimpleDeriver
YAML,
      NULL,
      NULL,
      $simple_deriver_additional_files,
      [
        'ckeditor5_derived_plugin_foo:bar' => new CKEditor5PluginDefinition([
          'provider' => 'ckeditor5_derived_plugin',
          'id' => 'ckeditor5_derived_plugin_foo:bar',
          'ckeditor5' => ['plugins' => []] + $ckeditor5_aspects_defaults,
          'drupal' => [
            'label' => 'Foo bar',
            'elements' => FALSE,
            'deriver' => 'Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin\SimpleDeriver',
          ] + $drupal_aspects_defaults,
        ]),
        'ckeditor5_derived_plugin_foo:baz' => new CKEditor5PluginDefinition([
          'provider' => 'ckeditor5_derived_plugin',
          'id' => 'ckeditor5_derived_plugin_foo:baz',
          'ckeditor5' => ['plugins' => []] + $ckeditor5_aspects_defaults,
          'drupal' => [
            'label' => 'Foo baz',
            'elements' => FALSE,
            'deriver' => 'Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin\SimpleDeriver',
          ] + $drupal_aspects_defaults,
        ]),
      ],
    ];

    yield 'VALID: simple deriver, base definition in PHP with Attribute' => [
      '',
      NULL,
      NULL,
      [
        'src' => [
          'Plugin' => [
            'CKEditor5Plugin' => [
              'Foo.php' => <<<'PHP'
<?php
declare(strict_types = 1);
namespace Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin;
use Drupal\ckeditor5\Attribute\CKEditor5AspectsOfCKEditor5Plugin;
use Drupal\ckeditor5\Attribute\CKEditor5Plugin;
use Drupal\ckeditor5\Attribute\DrupalAspectsOfCKEditor5Plugin;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\Core\StringTranslation\TranslatableMarkup;

#[CKEditor5Plugin(
  id: 'ckeditor5_derived_plugin_foo',
  ckeditor5: new CKEditor5AspectsOfCKEditor5Plugin(
    plugins: [],
  ),
  drupal: new DrupalAspectsOfCKEditor5Plugin(
    elements: false,
    deriver: 'Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin\SimpleDeriver',
  ),
)]
class Foo extends CKEditor5PluginDefault {
}
PHP,
              'SimpleDeriver.php' => $simple_deriver_additional_files['src']['Plugin']['CKEditor5Plugin']['SimpleDeriver.php'],
            ],
          ],
        ],
      ],
      [
        'ckeditor5_derived_plugin_foo:bar' => new CKEditor5PluginDefinition([
          'provider' => 'ckeditor5_derived_plugin',
          'id' => 'ckeditor5_derived_plugin_foo:bar',
          'ckeditor5' => ['plugins' => []] + $ckeditor5_aspects_defaults,
          'drupal' => [
            'class' => 'Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin\Foo',
            'label' => 'Foo bar',
            'elements' => FALSE,
            'deriver' => 'Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin\SimpleDeriver',
          ] + $drupal_aspects_defaults,
        ]),
        'ckeditor5_derived_plugin_foo:baz' => new CKEditor5PluginDefinition([
          'provider' => 'ckeditor5_derived_plugin',
          'id' => 'ckeditor5_derived_plugin_foo:baz',
          'ckeditor5' => ['plugins' => []] + $ckeditor5_aspects_defaults,
          'drupal' => [
            'class' => 'Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin\Foo',
            'label' => 'Foo baz',
            'elements' => FALSE,
            'deriver' => 'Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin\SimpleDeriver',
          ] + $drupal_aspects_defaults,
        ]),
      ],
    ];

    yield 'VALID: simple deriver, base definition in PHP with Annotation' => [
      '',
      NULL,
      NULL,
      [
        'src' => [
          'Plugin' => [
            'CKEditor5Plugin' => [
              'Foo.php' => <<<'PHP'
<?php
declare(strict_types = 1);
namespace Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
/**
 * @CKEditor5Plugin(
 *   id = "ckeditor5_derived_plugin_foo",
 *   ckeditor5 = @CKEditor5AspectsOfCKEditor5Plugin(
 *     plugins = {},
 *   ),
 *   drupal = @DrupalAspectsOfCKEditor5Plugin(
 *     elements = false,
 *     deriver = "Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin\SimpleDeriver",
 *   )
 * )
 */
class Foo extends CKEditor5PluginDefault {
}
PHP,
              'SimpleDeriver.php' => $simple_deriver_additional_files['src']['Plugin']['CKEditor5Plugin']['SimpleDeriver.php'],
            ],
          ],
        ],
      ],
      [
        'ckeditor5_derived_plugin_foo:bar' => new CKEditor5PluginDefinition([
          'provider' => 'ckeditor5_derived_plugin',
          'id' => 'ckeditor5_derived_plugin_foo:bar',
          'ckeditor5' => ['plugins' => []] + $ckeditor5_aspects_defaults,
          'drupal' => [
            'class' => 'Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin\Foo',
            'label' => 'Foo bar',
            'elements' => FALSE,
            'deriver' => 'Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin\SimpleDeriver',
          ] + $drupal_aspects_defaults,
        ]),
        'ckeditor5_derived_plugin_foo:baz' => new CKEditor5PluginDefinition([
          'provider' => 'ckeditor5_derived_plugin',
          'id' => 'ckeditor5_derived_plugin_foo:baz',
          'ckeditor5' => ['plugins' => []] + $ckeditor5_aspects_defaults,
          'drupal' => [
            'class' => 'Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin\Foo',
            'label' => 'Foo baz',
            'elements' => FALSE,
            'deriver' => 'Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin\SimpleDeriver',
          ] + $drupal_aspects_defaults,
        ]),
      ],
    ];

    yield 'VALID: minimal base plugin definition, maximal deriver' => [
      <<<YAML
# Minimal annotation key-value pairs set in the YAML, most set in the deriver.
ckeditor5_derived_plugin_foo:
  drupal:
    deriver: Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin\MaximalDeriver
YAML,
      NULL,
      NULL,
      [
        'src' => [
          'Plugin' => [
            'CKEditor5Plugin' => [
              'MaximalDeriver.php' => <<<'PHP'
<?php
namespace Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\Component\Plugin\Derivative\DeriverBase;
class MaximalDeriver extends DeriverBase {
  public function getDerivativeDefinitions($base_plugin_definition) {
    assert($base_plugin_definition instanceof CKEditor5PluginDefinition);
    foreach (['A', 'B'] as $id) {
      $definition = $base_plugin_definition->toArray();
      $definition['id'] = $id;
      $definition['drupal']['label'] = sprintf("Foo %s", $id);
      $definition['drupal']['elements'] = FALSE;
      $definition['ckeditor5']['plugins'] = [];
      $this->derivatives[$id] = new CKEditor5PluginDefinition($definition);
    }
    return $this->derivatives;
  }
}
PHP,
            ],
          ],
        ],
      ],
      [
        'ckeditor5_derived_plugin_foo:A' => new CKEditor5PluginDefinition([
          'provider' => 'ckeditor5_derived_plugin',
          'id' => 'ckeditor5_derived_plugin_foo:A',
          'ckeditor5' => ['plugins' => []],
          'drupal' => [
            'label' => 'Foo A',
            'elements' => FALSE,
            'deriver' => 'Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin\MaximalDeriver',
          ] + $drupal_aspects_defaults,
        ]),
        'ckeditor5_derived_plugin_foo:B' => new CKEditor5PluginDefinition([
          'provider' => 'ckeditor5_derived_plugin',
          'id' => 'ckeditor5_derived_plugin_foo:B',
          'ckeditor5' => ['plugins' => []],
          'drupal' => [
            'label' => 'Foo B',
            'elements' => FALSE,
            'deriver' => 'Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin\MaximalDeriver',
          ] + $drupal_aspects_defaults,
        ]),
      ],
    ];

    yield 'VALID: container-dependent deriver' => [
      <<<YAML
ckeditor5_derived_plugin_foo:
  ckeditor5:
    plugins: {}
  drupal:
    elements: false
    deriver: Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin\ContainerDependentDeriver
YAML,
      NULL,
      NULL,
      [
        'config' => [
          'schema' => [
            'ckeditor5_derived_plugin.schema.yml' => <<<YAML
ckeditor5.plugin.ckeditor5_derived_plugin:
  type: mapping
  label: 'Foo'
  mapping:
    foo:
      type: boolean
      label: 'Foo'
YAML,
          ],
        ],
        'src' => [
          'Plugin' => [
            'CKEditor5Plugin' => [
              'ContainerDependentDeriver.php' => <<<'PHP'
<?php
namespace Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Authentication\AuthenticationCollectorInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
class ContainerDependentDeriver extends DeriverBase implements ContainerDeriverInterface {
  protected $authenticationCollector;
  public function __construct(AuthenticationCollectorInterface $authentication_collector) {
    $this->authenticationCollector = $authentication_collector;
  }
  public static function create(ContainerInterface $container, $base_plugin_id) {
    assert($base_plugin_id === 'ckeditor5_derived_plugin_foo');
    return new static($container->get('authentication_collector'));
  }
  public function getDerivativeDefinitions($base_plugin_definition) {
    assert($base_plugin_definition instanceof CKEditor5PluginDefinition);
    $authentication_providers = array_keys($this->authenticationCollector->getSortedProviders());
    foreach ($authentication_providers as $id) {
      $definition = $base_plugin_definition->toArray();
      $definition['id'] = $id;
      $definition['drupal']['label'] = sprintf("Foo %s", $id);
      $this->derivatives[$definition['id']] = new CKEditor5PluginDefinition($definition);
    }
    return $this->derivatives;
  }
}
PHP,
            ],
          ],
        ],
      ],
      [
        'ckeditor5_derived_plugin_foo:cookie' => new CKEditor5PluginDefinition([
          'provider' => 'ckeditor5_derived_plugin',
          'id' => 'ckeditor5_derived_plugin_foo:cookie',
          'ckeditor5' => ['plugins' => []] + $ckeditor5_aspects_defaults,
          'drupal' => [
            'label' => 'Foo cookie',
            'elements' => FALSE,
            'deriver' => 'Drupal\ckeditor5_derived_plugin\Plugin\CKEditor5Plugin\ContainerDependentDeriver',
          ] + $drupal_aspects_defaults,
        ]),
      ],
    ];
  }

  /**
   * Tests backwards compatibility of icon names.
   */
  public function testIconsBackwardsCompatibility(): void {
    \Drupal::service('module_installer')->install(['ckeditor5_icon_deprecation_test']);
    $definitions = \Drupal::service('plugin.manager.ckeditor5.plugin')->getDefinitions();
    $config = $definitions['ckeditor5_icon_deprecation_test_plugin']->toArray()['ckeditor5']['config']['drupalElementStyles'];
    $this->assertSame('IconObjectCenter', $config['align'][0]['icon']);
    $this->assertSame('IconObjectLeft', $config['align'][1]['icon']);
    $this->assertSame('IconObjectRight', $config['align'][2]['icon']);
    $this->assertSame('IconObjectInlineLeft', $config['align'][3]['icon']);
    $this->assertSame('IconObjectInlineRight', $config['align'][4]['icon']);
    $this->assertStringContainsString('<svg viewBox="0 0 20 20"', $config['svg'][0]['icon']);
    $this->assertSame('IconThreeVerticalDots', $config['threeVerticalDots'][0]['icon']);
  }

}
