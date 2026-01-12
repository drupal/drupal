<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Unit;

use Drupal\ckeditor5\Plugin\CKEditor5Plugin\Heading;
use Drupal\ckeditor5\Plugin\CKEditor5Plugin\Language;
use Drupal\editor\EditorInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests Drupal\ckeditor5\Plugin\CKEditor5Plugin\Language.
 *
 * @internal
 */
#[CoversClass(Language::class)]
#[Group('ckeditor5')]
class HeadingPluginTest extends UnitTestCase {

  /**
   * Provides a list of configs to test.
   */
  public static function providerGetDynamicPluginConfig(): array {
    // Prepare headings matching ckeditor5.ckeditor5.yml to also protect
    // against unexpected changes to the YAML file given the YAML file is used
    // to generate the dynamic plugin configuration.
    $paragraph = [
      'model' => 'paragraph',
      'title' => 'Paragraph',
      'class' => 'ck-heading_paragraph',
    ];
    $headings = [];
    foreach (range(2, 6) as $number) {
      $headings[$number] = [
        'model' => 'heading' . $number,
        'view' => 'h' . $number,
        'title' => 'Heading ' . $number,
        'class' => 'ck-heading_heading' . $number,
      ];
    }

    return [
      'All headings' => [
        Heading::DEFAULT_CONFIGURATION,
        [
          'heading' => [
            'options' => [
              $paragraph,
              $headings[2],
              $headings[3],
              $headings[4],
              $headings[5],
              $headings[6],
            ],
          ],
        ],
      ],
      'Only required headings' => [
        [
          'enabled_headings' => [],
        ],
        [
          'heading' => [
            'options' => [
              $paragraph,
            ],
          ],
        ],
      ],
      'Heading 2 only' => [
        [
          'enabled_headings' => [
            'heading2',
          ],
        ],
        [
          'heading' => [
            'options' => [
              $paragraph,
              $headings[2],
            ],
          ],
        ],
      ],
      'Heading 2 and 3 only' => [
        [
          'enabled_headings' => [
            'heading2',
            'heading3',
          ],
        ],
        [
          'heading' => [
            'options' => [
              $paragraph,
              $headings[2],
              $headings[3],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Tests get dynamic plugin config.
   */
  #[DataProvider('providerGetDynamicPluginConfig')]
  public function testGetDynamicPluginConfig(array $configuration, array $expected_dynamic_config): void {
    // Read the CKEditor 5 plugin's static configuration from YAML.
    $ckeditor5_plugin_definitions = Yaml::parseFile(__DIR__ . '/../../../ckeditor5.ckeditor5.yml');
    $static_plugin_config = $ckeditor5_plugin_definitions['ckeditor5_heading']['ckeditor5']['config'];

    $plugin = new Heading($configuration, 'ckeditor5_heading', NULL);
    $dynamic_plugin_config = $plugin->getDynamicPluginConfig($static_plugin_config, $this->prophesize(EditorInterface::class)
      ->reveal());

    $this->assertSame($expected_dynamic_config, $dynamic_plugin_config);
  }

}
