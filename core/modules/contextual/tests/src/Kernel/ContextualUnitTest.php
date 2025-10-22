<?php

declare(strict_types=1);

namespace Drupal\Tests\contextual\Kernel;

use Drupal\contextual\Element\ContextualLinksPlaceholder;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests edge cases for converting between contextual links and IDs.
 */
#[Group('contextual')]
#[RunTestsInSeparateProcesses]
class ContextualUnitTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'contextual'];

  /**
   * Provides test cases for both test functions.
   *
   * Used in testContextualLinksToId() and testContextualIdToLinks().
   *
   * @return array[]
   *   Test cases.
   */
  public static function contextualLinksDataProvider(): array {
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
      'olivero',
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
      'claro',
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
      'olivero',
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
      'claro',
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
   * @legacy-covers ::_contextual_links_to_id
   */
  #[DataProvider('contextualLinksDataProvider')]
  public function testContextualLinksToId(array $links, string $id): void {
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
   * @legacy-covers ::_contextual_id_to_links
   */
  #[DataProvider('contextualLinksDataProvider')]
  public function testContextualIdToLinks(array $links, string $id): void {
    $this->assertSame($links, _contextual_id_to_links($id));
  }

  /**
   * Tests the placeholder of contextual links in a specific theme.
   *
   * @param array $links
   *   The #contextual_links property value array.
   * @param string $id
   *   The serialized representation of the passed links.
   * @param string $theme
   *   The name of the theme the placeholder should pass to the controller.
   *
   * @legacy-covers \Drupal\contextual\Element\ContextualLinksPlaceholder::preRenderPlaceholder
   */
  #[DataProvider('contextualLinksDataProvider')]
  public function testThemePlaceholder(array $links, string $id, string $theme): void {
    \Drupal::service('theme_installer')->install([$theme]);
    \Drupal::configFactory()->getEditable('system.theme')
      ->set('default', $theme)
      ->save();

    $element = [
      '#type' => 'contextual_links_placeholder',
      '#id' => $id,
      '#pre_render' => [
        ['Drupal\contextual\Element\ContextualLinksPlaceholder', 'preRenderPlaceholder'],
      ],
      '#defaults_loaded' => TRUE,
    ];
    $output = ContextualLinksPlaceholder::preRenderPlaceholder($element);

    $this->assertEquals($theme, $output['#attached']['drupalSettings']['contextual']['theme']);
  }

}
