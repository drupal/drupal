<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Unit;

use Drupal\ckeditor5\Plugin\CKEditor5Plugin\Alignment;
use Drupal\editor\EditorInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @coversDefaultClass \Drupal\ckeditor5\Plugin\CKEditor5Plugin\Alignment
 * @group ckeditor5
 * @internal
 */
class AlignmentPluginTest extends UnitTestCase {

  /**
   * Provides a list of configs to test.
   */
  public function providerGetDynamicPluginConfig(): array {
    return [
      'All alignments' => [
        Alignment::DEFAULT_CONFIGURATION,
        [
          'alignment' => [
            'options' => [
              [
                'name' => 'left',
                'className' => 'text-align-left',
              ],
              [
                'name' => 'center',
                'className' => 'text-align-center',
              ],
              [
                'name' => 'right',
                'className' => 'text-align-right',
              ],
              [
                'name' => 'justify',
                'className' => 'text-align-justify',
              ],
            ],
          ],
        ],
      ],
      'No alignments allowed' => [
        [
          'enabled_alignments' => [],
        ],
        [
          'alignment' => [
            'options' => [],
          ],
        ],
      ],
      'Left only' => [
        [
          'enabled_alignments' => [
            'left',
          ],
        ],
        [
          'alignment' => [
            'options' => [
              [
                'name' => 'left',
                'className' => 'text-align-left',
              ],
            ],
          ],
        ],
      ],
      'Left and justify only' => [
        [
          'enabled_alignments' => [
            'left',
            'justify',
          ],
        ],
        [
          'alignment' => [
            'options' => [
              [
                'name' => 'left',
                'className' => 'text-align-left',
              ],
              [
                'name' => 'justify',
                'className' => 'text-align-justify',
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @covers ::getDynamicPluginConfig
   * @dataProvider providerGetDynamicPluginConfig
   */
  public function testGetDynamicPluginConfig(array $configuration, array $expected_dynamic_config): void {
    // Read the CKEditor 5 plugin's static configuration from YAML.
    $ckeditor5_plugin_definitions = Yaml::parseFile(__DIR__ . '/../../../ckeditor5.ckeditor5.yml');
    $static_plugin_config = $ckeditor5_plugin_definitions['ckeditor5_alignment']['ckeditor5']['config'];

    $plugin = new Alignment($configuration, 'ckeditor5_alignment', NULL);
    $dynamic_plugin_config = $plugin->getDynamicPluginConfig($static_plugin_config, $this->prophesize(EditorInterface::class)
      ->reveal());

    $this->assertSame($expected_dynamic_config, $dynamic_plugin_config);
  }

}
