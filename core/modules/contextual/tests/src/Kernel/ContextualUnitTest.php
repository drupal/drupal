<?php

namespace Drupal\Tests\contextual\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests edge cases for converting between contextual links and IDs.
 *
 * @group contextual
 */
class ContextualUnitTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['contextual'];

  /**
   * Provides test cases for both test functions.
   *
   * Used in testContextualLinksToId() and testContextualIdToLinks().
   *
   * @return array[]
   *   Test cases.
   */
  public function contextualLinksDataProvider(): array {
    $tests['one group, one dynamic path argument, no metadata'] = [
      [
        'node' => [
          'route_parameters' => [
            'node' => '14031991',
          ],
          'metadata' => ['langcode' => 'en'],
        ],
      ],
      'node:node=14031991:langcode=en',
    ];

    $tests['one group, multiple dynamic path arguments, no metadata'] = [
      [
        'foo' => [
          'route_parameters' => [
            0 => 'bar',
            'key' => 'baz',
            1 => 'qux',
          ],
          'metadata' => ['langcode' => 'en'],
        ],
      ],
      'foo:0=bar&key=baz&1=qux:langcode=en',
    ];

    $tests['one group, one dynamic path argument, metadata'] = [
      [
        'views_ui_edit' => [
          'route_parameters' => [
            'view' => 'frontpage',
          ],
          'metadata' => [
            'location' => 'page',
            'display' => 'page_1',
            'langcode' => 'en',
          ],
        ],
      ],
      'views_ui_edit:view=frontpage:location=page&display=page_1&langcode=en',
    ];

    $tests['multiple groups, multiple dynamic path arguments'] = [
      [
        'node' => [
          'route_parameters' => [
            'node' => '14031991',
          ],
          'metadata' => ['langcode' => 'en'],
        ],
        'foo' => [
          'route_parameters' => [
            0 => 'bar',
            'key' => 'baz',
            1 => 'qux',
          ],
          'metadata' => ['langcode' => 'en'],
        ],
        'edge' => [
          'route_parameters' => ['20011988'],
          'metadata' => ['langcode' => 'en'],
        ],
      ],
      'node:node=14031991:langcode=en|foo:0=bar&key=baz&1=qux:langcode=en|edge:0=20011988:langcode=en',
    ];

    return $tests;
  }

  /**
   * Tests the conversion from contextual links to IDs.
   *
   * @param array $links
   *   The #contextual_links property value array.
   * @param string $id
   *   The serialized representation of the passed links.
   *
   * @covers ::_contextual_links_to_id
   *
   * @dataProvider contextualLinksDataProvider
   */
  public function testContextualLinksToId(array $links, string $id) {
    $this->assertSame($id, _contextual_links_to_id($links));
  }

  /**
   * Tests the conversion from contextual ID to links.
   *
   * @param array $links
   *   The #contextual_links property value array.
   * @param string $id
   *   The serialized representation of the passed links.
   *
   * @covers ::_contextual_id_to_links
   *
   * @dataProvider contextualLinksDataProvider
   */
  public function testContextualIdToLinks(array $links, string $id) {
    $this->assertSame($links, _contextual_id_to_links($id));
  }

}
