<?php

namespace Drupal\Tests\ckeditor5\Kernel;

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * @covers \Drupal\ckeditor5\Plugin\CKEditor5PluginManager::getCKEditor5PluginConfig
 * @group ckeditor5
 * @internal
 */
class WildcardHtmlSupportTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor5',
    'filter',
    'editor',
  ];

  /**
   * The manager for "CKEditor 5 plugin" plugins.
   *
   * @var \Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface
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
   * @covers \Drupal\ckeditor5\Plugin\CKEditor5Plugin\SourceEditing::getDynamicPluginConfig
   * @dataProvider providerGhsConfiguration
   */
  public function testGhsConfiguration(string $filter_html_allowed, array $source_editing_tags, array $expected_ghs_configuration, ?array $additional_toolbar_items = []): void {
    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'settings' => [
            'allowed_html' => $filter_html_allowed,
          ],
        ],
      ],
    ])->save();
    $editor_config = [
      'editor' => 'ckeditor5',
      'format' => 'test_format',
      'settings' => [
        'toolbar' => [
          'items' => array_merge(['sourceEditing'], $additional_toolbar_items),
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => $source_editing_tags,
          ],
        ],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
    ];
    if (in_array('alignment', $additional_toolbar_items, TRUE)) {
      $editor_config['settings']['plugins']['ckeditor5_alignment'] = [
        'enabled_alignments' => ['left', 'center', 'right', 'justify'],
      ];
    }

    $editor = Editor::create($editor_config);
    $editor->save();
    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('test_format'),
        FilterFormat::load('test_format')
      ))
    ));
    $config = $this->manager->getCKEditor5PluginConfig($editor);
    $ghs_configuration = $config['config']['htmlSupport']['allow'];
    // The first two entries in the GHS configuration are from the
    // `ckeditor5_globalAttributeDir` and `ckeditor5_globalAttributeLang`
    // plugins. They are out of scope for this test, so omit them.
    $ghs_configuration = array_slice($ghs_configuration, 2);
    $this->assertEquals($expected_ghs_configuration, $ghs_configuration);
  }

  public function providerGhsConfiguration(): array {
    return [
      'empty source editing' => [
        '<p> <br>',
        [],
        [],
      ],
      'without wildcard' => [
        '<p> <br> <a href> <blockquote> <div data-llama>',
        ['<div data-llama>'],
        [
          [
            'name' => 'div',
            'attributes' => [
              [
                'key' => 'data-llama',
                'value' => TRUE,
              ],
            ],
          ],
        ],
        ['link', 'blockQuote'],
      ],
      '<$text-container> minimal configuration' => [
        '<p data-llama> <br>',
        ['<$text-container data-llama>'],
        [
          [
            'name' => 'p',
            'attributes' => [
              [
                'key' => 'data-llama',
                'value' => TRUE,
              ],
            ],
          ],
        ],
      ],
      '<$text-container> from multiple plugins' => [
        '<p data-llama class="text-align-left text-align-center text-align-right text-align-justify"> <br>',
        ['<$text-container data-llama>'],
        [
          [
            'name' => 'p',
            'attributes' => [
              [
                'key' => 'data-llama',
                'value' => TRUE,
              ],
            ],
            'classes' => [
              'regexp' => [
                'pattern' => '/^(text-align-left|text-align-center|text-align-right|text-align-justify)$/',
              ],
            ],
          ],
        ],
        ['alignment'],
      ],
      '<$text-container> with attribute from multiple plugins' => [
        '<p data-llama class"> <br>',
        ['<$text-container data-llama>', '<p class>'],
        [
          [
            'name' => 'p',
            'classes' => TRUE,
          ],
          [
            'name' => 'p',
            'attributes' => [
              [
                'key' => 'data-llama',
                'value' => TRUE,
              ],
            ],
            'classes' => [
              'regexp' => [
                'pattern' => '/^(text-align-left|text-align-center|text-align-right|text-align-justify)$/',
              ],
            ],
          ],
        ],
        ['alignment'],
      ],
      '<$text-container> realistic configuration' => [
        '<p data-llama> <br> <a href> <blockquote> <div data-llama> <mark> <abbr title>',
        ['<$text-container data-llama>', '<div>', '<mark>', '<abbr title>'],
        [
          [
            'name' => 'div',
          ],
          [
            'name' => 'mark',
          ],
          [
            'name' => 'abbr',
            'attributes' => [
              [
                'key' => 'title',
                'value' => TRUE,
              ],
            ],
          ],
          [
            'name' => 'p',
            'attributes' => [
              [
                'key' => 'data-llama',
                'value' => TRUE,
              ],
            ],
          ],
          [
            'name' => 'div',
            'attributes' => [
              [
                'key' => 'data-llama',
                'value' => TRUE,
              ],
            ],
          ],
        ],
        ['link', 'blockQuote'],
      ],
    ];
  }

}
