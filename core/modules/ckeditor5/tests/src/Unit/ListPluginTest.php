<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Unit;

use Drupal\ckeditor5\Plugin\CKEditor5Plugin\ListPlugin;
use Drupal\editor\Entity\Editor;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @coversDefaultClass \Drupal\ckeditor5\Plugin\CKEditor5Plugin\ListPlugin
 * @group ckeditor5
 * @internal
 */
class ListPluginTest extends UnitTestCase {

  /**
   * Provides a list of configs to test.
   */
  public function providerGetDynamicPluginConfig(): array {
    return [
      'startIndex is false' => [
        [
          'reversed' => TRUE,
          'startIndex' => FALSE,
        ],
        [
          'list' => [
            'properties' => [
              'reversed' => TRUE,
              'startIndex' => FALSE,
              'styles' => FALSE,
            ],
          ],
        ],
      ],
      'reversed is false' => [
        [
          'reversed' => FALSE,
          'startIndex' => TRUE,
        ],
        [
          'list' => [
            'properties' => [
              'reversed' => FALSE,
              'startIndex' => TRUE,
              'styles' => FALSE,
            ],
          ],
        ],
      ],
      'both disabled' => [
        [
          'reversed' => FALSE,
          'startIndex' => FALSE,
        ],
        [
          'list' => [
            'properties' => [
              'reversed' => FALSE,
              'startIndex' => FALSE,
              'styles' => FALSE,
            ],
          ],
        ],
      ],
      'both enabled' => [
        [
          'reversed' => TRUE,
          'startIndex' => TRUE,
        ],
        [
          'list' => [
            'properties' => [
              'reversed' => TRUE,
              'startIndex' => TRUE,
              'styles' => FALSE,
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @covers ::getDynamicPluginConfig
   *
   * @dataProvider providerGetDynamicPluginConfig
   */
  public function testGetDynamicPluginConfig(array $configuration, array $expected_dynamic_config): void {
    // Read the CKEditor 5 plugin's static configuration from YAML.
    $ckeditor5_plugin_definitions = Yaml::parseFile(__DIR__ . '/../../../ckeditor5.ckeditor5.yml');
    $static_plugin_config = $ckeditor5_plugin_definitions['ckeditor5_list']['ckeditor5']['config'];
    $plugin = new ListPlugin($configuration, 'ckeditor5_list', NULL);
    $dynamic_plugin_config = $plugin->getDynamicPluginConfig($static_plugin_config, $this->prophesize(Editor::class)
      ->reveal());
    $this->assertSame($expected_dynamic_config, $dynamic_plugin_config);
  }

}
