<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Unit;

use Drupal\ckeditor5\Plugin\CKEditor5Plugin\ListPlugin;
use Drupal\editor\Entity\Editor;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests Drupal\ckeditor5\Plugin\CKEditor5Plugin\ListPlugin.
 *
 * @internal
 */
#[CoversClass(ListPlugin::class)]
#[Group('ckeditor5')]
class ListPluginTest extends UnitTestCase {

  /**
   * Provides a list of configs to test.
   */
  public static function providerGetDynamicPluginConfig(): array {
    return [
      'startIndex is false' => [
        [
          'properties' => [
            'reversed' => TRUE,
            'startIndex' => FALSE,
            'styles' => TRUE,
          ],
          'multiBlock' => TRUE,
        ],
        [
          'list' => [
            'properties' => [
              'reversed' => TRUE,
              'startIndex' => FALSE,
              'styles' => [
                'useAttribute' => TRUE,
              ],
            ],
            'multiBlock' => TRUE,
          ],
        ],
      ],
      'reversed is false' => [
        [
          'properties' => [
            'reversed' => FALSE,
            'startIndex' => TRUE,
            'styles' => TRUE,
          ],
          'multiBlock' => TRUE,
        ],
        [
          'list' => [
            'properties' => [
              'reversed' => FALSE,
              'startIndex' => TRUE,
              'styles' => [
                'useAttribute' => TRUE,
              ],
            ],
            'multiBlock' => TRUE,
          ],
        ],
      ],
      'styles is false' => [
        [
          'properties' => [
            'reversed' => TRUE,
            'startIndex' => TRUE,
            'styles' => FALSE,
          ],
          'multiBlock' => TRUE,
        ],
        [
          'list' => [
            'properties' => [
              'reversed' => TRUE,
              'startIndex' => TRUE,
              'styles' => FALSE,
            ],
            'multiBlock' => TRUE,
          ],
        ],
      ],
      'all disabled' => [
        [
          'properties' => [
            'reversed' => FALSE,
            'startIndex' => FALSE,
            'styles' => FALSE,
          ],
          'multiBlock' => TRUE,
        ],
        [
          'list' => [
            'properties' => [
              'reversed' => FALSE,
              'startIndex' => FALSE,
              'styles' => FALSE,
            ],
            'multiBlock' => TRUE,
          ],
        ],
      ],
      'all enabled' => [
        [
          'properties' => [
            'reversed' => TRUE,
            'startIndex' => TRUE,
            'styles' => TRUE,
          ],
          'multiBlock' => TRUE,
        ],
        [
          'list' => [
            'properties' => [
              'reversed' => TRUE,
              'startIndex' => TRUE,
              'styles' => [
                'useAttribute' => TRUE,
              ],
            ],
            'multiBlock' => TRUE,
          ],
        ],
      ],
    ];
  }

  /**
   * Tests get dynamic plugin config.
   *
   * @legacy-covers ::getDynamicPluginConfig
   */
  #[DataProvider('providerGetDynamicPluginConfig')]
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
