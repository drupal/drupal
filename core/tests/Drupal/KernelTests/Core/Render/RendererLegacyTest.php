<?php

namespace Drupal\KernelTests\Core\Render;

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

}
