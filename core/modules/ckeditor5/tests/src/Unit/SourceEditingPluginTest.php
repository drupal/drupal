<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Unit;

use Drupal\ckeditor5\Plugin\CKEditor5Plugin\SourceEditing;
use Drupal\editor\EditorInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\ckeditor5\Plugin\CKEditor5Plugin\SourceEditing
 * @group ckeditor5
 * @internal
 */
class SourceEditingPluginTest extends UnitTestCase {

  /**
   * Provides a list of configs to test.
   */
  public function providerGetDynamicPluginConfig(): array {
    return [
      'Empty array of allowed tags' => [
        [
          'allowed_tags' => [],
        ],
        [
          'htmlSupport' => [
            'allow' => [],
            'allowEmpty' => [],
          ],
        ],
      ],
      'Simple' => [
        [
          'allowed_tags' => [
            '<foo1>',
            '<foo2 bar>',
            '<foo3 bar="baz">',
            '<foo4 bar="baz qux">',
            '<foo5 bar="baz" qux="foo">',
          ],
        ],
        [
          'htmlSupport' => [
            'allow' => [
              [
                'name' => 'foo1',
              ],
              [
                'name' => 'foo2',
                'attributes' => [
                  [
                    'key' => 'bar',
                    'value' => TRUE,
                  ],
                ],
              ],
              [
                'name' => 'foo3',
                'attributes' => [
                  [
                    'key' => 'bar',
                    'value' => [
                      'regexp' => [
                        'pattern' => '/^(baz)$/',
                      ],
                    ],
                  ],
                ],
              ],
              [
                'name' => 'foo4',
                'attributes' => [
                  [
                    'key' => 'bar',
                    'value' => [
                      'regexp' => [
                        'pattern' => '/^(baz|qux)$/',
                      ],
                    ],
                  ],
                ],
              ],
              [
                'name' => 'foo5',
                'attributes' => [
                  [
                    'key' => 'bar',
                    'value' => [
                      'regexp' => [
                        'pattern' => '/^(baz)$/',
                      ],
                    ],
                  ],
                  [
                    'key' => 'qux',
                    'value' => [
                      'regexp' => [
                        'pattern' => '/^(foo)$/',
                      ],
                    ],
                  ],
                ],
              ],
            ],
            'allowEmpty' => [
              'foo1',
              'foo2',
              'foo3',
              'foo4',
              'foo5',
            ],
          ],
        ],
      ],
      'Prefix wildcards' => [
        [
          'allowed_tags' => [
            '<foo1 bar-*>',
            '<foo2 bar-*="baz">',
            '<foo3 bar-*="baz qux-*">',
            '<foo2 bar="baz-*">',
            '<foo3 bar="baz qux-*">',
          ],
        ],
        [
          'htmlSupport' => [
            'allow' => [
              [
                'name' => 'foo1',
                'attributes' => [
                  [
                    'key' => [
                      'regexp' => [
                        'pattern' => '/^bar-.*$/',
                      ],
                    ],
                    'value' => TRUE,
                  ],
                ],
              ],
              [
                'name' => 'foo2',
                'attributes' => [
                  [
                    'key' => [
                      'regexp' => [
                        'pattern' => '/^bar-.*$/',
                      ],
                    ],
                    'value' => [
                      'regexp' => [
                        'pattern' => '/^(baz)$/',
                      ],
                    ],
                  ],
                  [
                    'key' => 'bar',
                    'value' => [
                      'regexp' => [
                        'pattern' => '/^(baz-.*)$/',
                      ],
                    ],
                  ],
                ],
              ],
              [
                'name' => 'foo3',
                'attributes' => [
                  [
                    'key' => [
                      'regexp' => [
                        'pattern' => '/^bar-.*$/',
                      ],
                    ],
                    'value' => [
                      'regexp' => [
                        'pattern' => '/^(baz|qux-.*)$/',
                      ],
                    ],
                  ],
                  [
                    'key' => 'bar',
                    'value' => [
                      'regexp' => [
                        'pattern' => '/^(baz|qux-.*)$/',
                      ],
                    ],
                  ],
                ],
              ],
            ],
            'allowEmpty' => [
              'foo1',
              'foo2',
              'foo3',
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
    $plugin = new SourceEditing($configuration, 'ckeditor5_sourceEditing', NULL);
    $dynamic_plugin_config = $plugin->getDynamicPluginConfig([], $this->prophesize(EditorInterface::class)
      ->reveal());
    $this->assertSame($expected_dynamic_config, $dynamic_plugin_config);
  }

}
