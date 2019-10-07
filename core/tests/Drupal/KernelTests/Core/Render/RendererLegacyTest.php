<?php

namespace Drupal\KernelTests\Core\Render;

use Drupal\Core\Form\FormHelper;
use Drupal\Core\Render\HtmlResponseAttachmentsProcessor;
use Drupal\KernelTests\KernelTestBase;

/**
 * Deprecation tests cases for the render layer.
 *
 * @group legacy
 */
class RendererLegacyTest extends KernelTestBase {

  /**
   * Tests deprecation of the drupal_http_header_attributes() function.
   *
   * @dataProvider providerAttributes
   *
   * @expectedDeprecation drupal_http_header_attributes() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Render\HtmlResponseAttachmentsProcessor::formatHttpHeaderAttributes() instead. See https://www.drupal.org/node/3000051
   */
  public function testHeaderAttributes($expected, $attributes) {
    $this->assertSame($expected, drupal_http_header_attributes($attributes));
    $this->assertSame($expected, HtmlResponseAttachmentsProcessor::formatHttpHeaderAttributes($attributes));
  }

  /**
   * Provides a list of attributes to test.
   */
  public function providerAttributes() {
    return [
      [' foo=""', ['foo' => '']],
      [' foo=""', ['foo' => []]],
      [' foo="bar"', ['foo' => 'bar']],
      [' foo="bar"', ['foo' => ['bar']]],
      [' foo="bar baz"', ['foo' => ['bar', 'baz']]],
    ];
  }

  /**
   * Tests deprecation of the drupal_process_states() function.
   *
   * @dataProvider providerElements
   *
   * @expectedDeprecation drupal_process_states() is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Form\FormHelper::processStates() instead. See https://www.drupal.org/node/3000069
   */
  public function testDrupalProcessStates($elements) {
    // Clone elements because processing changes array.
    $expected = $elements;
    drupal_process_states($expected);
    FormHelper::processStates($elements);
    $this->assertEquals($expected, $elements);
  }

  /**
   * Provides a list of elements to test.
   */
  public function providerElements() {
    return [
      [
        [
          '#type' => 'date',
          '#states' => [
            'visible' => [
              ':input[name="toggle_me"]' => ['checked' => TRUE],
            ],
          ],
        ],
      ],
      [
        [
          '#type' => 'item',
          '#states' => [
            'visible' => [
              ':input[name="foo"]' => ['value' => 'bar'],
            ],
          ],
        ],
      ],
    ];
  }

}
