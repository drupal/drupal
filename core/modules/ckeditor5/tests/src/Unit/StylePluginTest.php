<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Unit;

use Drupal\ckeditor5\Plugin\CKEditor5Plugin\Style;
use Drupal\editor\EditorInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\ckeditor5\Plugin\CKEditor5Plugin\Style
 * @group ckeditor5
 * @internal
 */
class StylePluginTest extends UnitTestCase {

  /**
   * Provides a list of configs to test.
   */
  public function providerGetDynamicPluginConfig(): array {
    return [
      'default configuration (empty)' => [
        [
          'styles' => [],
        ],
        [
          'style' => [
            'definitions' => [],
          ],
        ],
      ],
      'Simple' => [
        [
          'styles' => [
            ['label' => 'fancy blockquote', 'element' => '<blockquote class="fancy">'],
          ],
        ],
        [
          'style' => [
            'definitions' => [
              [
                'name' => 'fancy blockquote',
                'element' => 'blockquote',
                'classes' => ['fancy'],
              ],
            ],
          ],
        ],
      ],
      'Complex' => [
        [
          'styles' => [
            ['label' => 'fancy highlighted blockquote', 'element' => '<blockquote class="fancy highlighted">'],
            ['label' => 'important foobar', 'element' => '<foobar class="important">'],
          ],
        ],
        [
          'style' => [
            'definitions' => [
              [
                'name' => 'fancy highlighted blockquote',
                'element' => 'blockquote',
                'classes' => ['fancy', 'highlighted'],
              ],
              [
                'name' => 'important foobar',
                'element' => 'foobar',
                'classes' => ['important'],
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
    $plugin = new Style($configuration, 'ckeditor5_style', NULL);
    $dynamic_plugin_config = $plugin->getDynamicPluginConfig([], $this->prophesize(EditorInterface::class)->reveal());
    $this->assertSame($expected_dynamic_config, $dynamic_plugin_config);
  }

}
