<?php

namespace Drupal\Tests\Component\Render;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Render\MarkupTrait;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Component\Render\FormattableMarkup;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Render\PlainTextOutput
 * @group Utility
 */
class PlainTextOutputTest extends TestCase {

  /**
   * Tests ::renderFromHtml().
   *
   * @param $expected
   *   The expected formatted value.
   * @param $string
   *   A string to be formatted.
   * @param array $args
   *   (optional) An associative array of replacements to make. Defaults to
   *   none.
   *
   * @covers ::renderFromHtml
   * @dataProvider providerRenderFromHtml
   */
  public function testRenderFromHtml($expected, $string, $args = []) {
    $markup = new FormattableMarkup($string, $args);
    $output = PlainTextOutput::renderFromHtml($markup);
    $this->assertSame($expected, $output);
  }

  /**
   * Data provider for ::testRenderFromHtml()
   */
  public function providerRenderFromHtml() {
    $data = [];

    $data['simple-text'] = ['Giraffes and wombats', 'Giraffes and wombats'];
    $data['simple-html'] = ['Giraffes and wombats', '<a href="/muh">Giraffes</a> and <strong>wombats</strong>'];
    $data['html-with-quote'] = ['Giraffes and quote"s', '<a href="/muh">Giraffes</a> and <strong>quote"s</strong>'];

    $expected = 'The <em> tag makes your text look like "this".';
    $string = 'The &lt;em&gt; tag makes your text look like <em>"this"</em>.';
    $data['escaped-html-with-quotes'] = [$expected, $string];

    $markup = new class() implements MarkupInterface {
      use MarkupTrait;
    };
    $safe_string = $markup::create('<em>"this"</em>');
    $data['escaped-html-with-quotes-and-placeholders'] = [$expected, 'The @tag tag makes your text look like @result.', ['@tag' => '<em>', '@result' => $safe_string]];

    $safe_string = $markup::create($string);
    $data['safe-string'] = [$expected, $safe_string];

    return $data;
  }

}
