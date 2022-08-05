<?php

namespace Drupal\Tests\ckeditor5\Kernel;

use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests configurable plugins.
 *
 * @group ckeditor5
 * @internal
 */
class ConfigurablePluginTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor5',
    // These modules must be installed for ckeditor5_config_schema_info_alter()
    // to work, which in turn is necessary for the plugin definition validation
    // logic.
    // @see \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition::validateDrupalAspects()
    'filter',
    'editor',
  ];

  /**
   * The manager for "CKEditor 5 plugin" plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->manager = $this->container->get('plugin.manager.ckeditor5.plugin');
  }

  /**
   * Tests default settings for configurable CKEditor 5 plugins.
   */
  public function testDefaults() {
    $all_definitions = $this->manager->getDefinitions();
    $configurable_definitions = array_filter($all_definitions, function (CKEditor5PluginDefinition $definition): bool {
      return $definition->isConfigurable();
    });
    $default_plugin_settings = [];
    foreach (array_keys($configurable_definitions) as $plugin_name) {
      $default_plugin_settings[$plugin_name] = $this->manager->getPlugin($plugin_name, NULL)->defaultConfiguration();
    }

    $expected_default_plugin_settings = [
      'ckeditor5_heading' => [
        'enabled_headings' => [
          'heading2',
          'heading3',
          'heading4',
          'heading5',
          'heading6',
        ],
      ],
      'ckeditor5_style' => [
        'styles' => [],
      ],
      'ckeditor5_sourceEditing' => [
        'allowed_tags' => [],
      ],
      'ckeditor5_list' => [
        'reversed' => TRUE,
        'startIndex' => TRUE,
      ],
      'ckeditor5_alignment' => [
        'enabled_alignments' => [
          0 => 'left',
          1 => 'center',
          2 => 'right',
          3 => 'justify',
        ],
      ],
      'ckeditor5_imageResize' => [
        'allow_resize' => TRUE,
      ],
      'ckeditor5_language' => [
        'language_list' => 'un',
      ],
      'ckeditor5_imageUpload' => [],
    ];
    $this->assertSame($expected_default_plugin_settings, $default_plugin_settings);
  }

}
