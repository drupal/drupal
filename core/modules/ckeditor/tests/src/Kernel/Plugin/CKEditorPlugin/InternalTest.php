<?php

namespace Drupal\Tests\ckeditor\Kernel\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\ckeditor\Plugin\CKEditorPlugin\Internal
 *
 * @group ckeditor
 * @group legacy
 */
class InternalTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'ckeditor',
    'ckeditor_test',
    'filter',
    'editor',
  ];

  /**
   * A testing text format.
   *
   * @var \Drupal\filter\Entity\FilterFormat
   */
  protected $format;

  /**
   * A testing text editor.
   *
   * @var \Drupal\editor\Entity\Editor
   */
  protected $editor;

  /**
   * The CKEditor plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $ckeditorPluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('editor');
    $this->installEntitySchema('filter_format');

    $this->format = FilterFormat::create([
      'format' => 'test_format',
      'name' => $this->randomMachineName(),
    ]);
    $this->format->save();

    $this->editor = Editor::create([
      'editor' => 'ckeditor',
      'format' => 'test_format',
      'settings' => [
        'toolbar' => [
          'rows' => [
            [
              [
                'name' => 'Enabled Buttons',
                'items' => [
                  'Format',
                ],
              ],
            ],
          ],
        ],
      ],
    ]);
    $this->editor->save();

    $this->ckeditorPluginManager = $this->container->get('plugin.manager.ckeditor.plugin');
  }

  /**
   * Tests the format tags settings.
   *
   * @dataProvider formatTagsSettingsTestCases
   */
  public function testFormatTagsSettings($filter_plugins, $expected_format_tags) {
    foreach ($filter_plugins as $filter_plugin_id => $filter_plugin_settings) {
      $this->format->setFilterConfig($filter_plugin_id, $filter_plugin_settings);
    }
    $this->format->save();

    $internal_plugin = $this->ckeditorPluginManager->createInstance('internal', []);
    $plugin_config = $internal_plugin->getConfig($this->editor);
    $this->assertEquals($expected_format_tags, explode(';', $plugin_config['format_tags']));
  }

  /**
   * A data provider for testFormatTagsSettings.
   */
  public function formatTagsSettingsTestCases() {
    $all_tags = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre'];

    return [
      'No filter plugins enabled (all tags allowed)' => [
        [],
        $all_tags,
      ],
      'HTML filter plugin enabled (some tags filtered out)' => [
        [
          'filter_html' => [
            'status' => 1,
            'settings' => [
              'allowed_html' => '<h1> <h2>',
              'filter_html_help' => 1,
              'filter_html_nofollow' => 0,
            ],
          ],
        ],
        ['p', 'h1', 'h2'],
      ],
      'Test attribute filter enabled (all tags allowed)' => [
        [
          'test_attribute_filter' => [
            'status' => 1,
            'settings' => [
              'tags' => ['h1', 'h2'],
            ],
          ],
        ],
        $all_tags,
      ],
    ];
  }

}
