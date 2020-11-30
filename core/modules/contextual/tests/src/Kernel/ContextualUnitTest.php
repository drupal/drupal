<?php

namespace Drupal\Tests\contextual\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests all edge cases of converting from #contextual_links to ids and vice
 * versa.
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
   * Provides testcases for both test functions.
   *
   * Used in testContextualLinksToId() and testContextualIdToLinks().
   */
  public function _contextual_links_id_testcases() {
    // Test branch conditions:
    // - one group.
    // - one dynamic path argument.
    // - no metadata.
    $tests[] = [
      'links' => [
        'node' => [
          'route_parameters' => [
            'node' => '14031991',
          ],
          'metadata' => ['langcode' => 'en'],
        ],
      ],
      'id' => 'node:node=14031991:langcode=en',
    ];

    // Test branch conditions:
    // - one group.
    // - multiple dynamic path arguments.
    // - no metadata.
    $tests[] = [
      'links' => [
        'foo' => [
          'route_parameters' => [
            'bar',
            'key' => 'baz',
            'qux',
          ],
          'metadata' => ['langcode' => 'en'],
        ],
      ],
      'id' => 'foo:0=bar&key=baz&1=qux:langcode=en',
    ];

    // Test branch conditions:
    // - one group.
    // - one dynamic path argument.
    // - metadata.
    $tests[] = [
      'links' => [
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
      'id' => 'views_ui_edit:view=frontpage:location=page&display=page_1&langcode=en',
    ];

    // Test branch conditions:
    // - multiple groups.
    // - multiple dynamic path arguments.
    $tests[] = [
      'links' => [
        'node' => [
          'route_parameters' => [
            'node' => '14031991',
          ],
          'metadata' => ['langcode' => 'en'],
        ],
        'foo' => [
          'route_parameters' => [
            'bar',
            'key' => 'baz',
            'qux',
          ],
          'metadata' => ['langcode' => 'en'],
        ],
        'edge' => [
          'route_parameters' => ['20011988'],
          'metadata' => ['langcode' => 'en'],
        ],
      ],
      'id' => 'node:node=14031991:langcode=en|foo:0=bar&key=baz&1=qux:langcode=en|edge:0=20011988:langcode=en',
    ];

    return $tests;
  }

  /**
   * Tests _contextual_links_to_id().
   */
  public function testContextualLinksToId() {
    $tests = $this->_contextual_links_id_testcases();
    foreach ($tests as $test) {
      $this->assertIdentical(_contextual_links_to_id($test['links']), $test['id']);
    }
  }

  /**
   * Tests _contextual_id_to_links().
   */
  public function testContextualIdToLinks() {
    $tests = $this->_contextual_links_id_testcases();
    foreach ($tests as $test) {
      $this->assertIdentical(_contextual_id_to_links($test['id']), $test['links']);
    }
  }

}
