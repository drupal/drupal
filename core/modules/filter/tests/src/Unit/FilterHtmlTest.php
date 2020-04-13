<?php

namespace Drupal\Tests\filter\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\filter\Plugin\Filter\FilterHtml;

/**
 * @coversDefaultClass \Drupal\filter\Plugin\Filter\FilterHtml
 * @group filter
 */
class FilterHtmlTest extends UnitTestCase {

  /**
   * @var \Drupal\filter\Plugin\Filter\FilterHtml
   */
  protected $filter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $configuration['settings'] = [
      'allowed_html' => '<a href> <p> <em> <strong> <cite> <blockquote> <code class="pretty boring align-*"> <ul alpaca-*="wooly-* strong"> <ol llama-*> <li> <dl> <dt> <dd> <br> <h3 id>',
      'filter_html_help' => 1,
      'filter_html_nofollow' => 0,
    ];
    $this->filter = new FilterHtml($configuration, 'filter_html', ['provider' => 'test']);
    $this->filter->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * @covers ::filterAttributes
   *
   * @dataProvider providerFilterAttributes
   *
   * @param string $html
   *   Input HTML.
   * @param string $expected
   *   The expected output string.
   */
  public function testfilterAttributes($html, $expected) {
    $this->assertSame($expected, $this->filter->filterAttributes($html));
  }

  /**
   * Provides data for testfilterAttributes.
   *
   * @return array
   *   An array of test data.
   */
  public function providerFilterAttributes() {
    return [
      ['<a href="/blog" title="Blog">Blog</a>', '<a href="/blog">Blog</a>'],
      ['<p dir="rtl" />', '<p dir="rtl"></p>'],
      ['<p dir="bogus" />', '<p></p>'],
      ['<p id="first" />', '<p></p>'],
      // The addition of xml:lang isn't especially desired, but is still valid
      // HTML5. See https://www.drupal.org/node/1333730.
      ['<p id="first" lang="en">text</p>', '<p lang="en" xml:lang="en">text</p>'],
      ['<p style="display: none;" />', '<p></p>'],
      ['<code class="pretty invalid">foreach ($a as $b) {}</code>', '<code class="pretty">foreach ($a as $b) {}</code>'],
      ['<code class="boring pretty">foreach ($a as $b) {}</code>', '<code class="boring pretty">foreach ($a as $b) {}</code>'],
      ['<code class="boring    pretty ">foreach ($a as $b) {}</code>', '<code class="boring pretty">foreach ($a as $b) {}</code>'],
      ['<code class="invalid alpaca">foreach ($a as $b) {}</code>', '<code>foreach ($a as $b) {}</code>'],
      ['<h3 class="big">a heading</h3>', '<h3>a heading</h3>'],
      ['<h3 id="first">a heading</h3>', '<h3 id="first">a heading</h3>'],
      // Wildcard value. Case matters, so upper case doesn't match.
      ['<code class="align-left bold">foreach ($a as $b) {}</code>', '<code class="align-left">foreach ($a as $b) {}</code>'],
      ['<code class="align-right ">foreach ($a as $b) {}</code>', '<code class="align-right">foreach ($a as $b) {}</code>'],
      ['<code class="Align-right ">foreach ($a as $b) {}</code>', '<code>foreach ($a as $b) {}</code>'],
      // Wildcard name, case is ignored.
      ['<ol style="display: none;" llama-wim="noble majestic"></ol>', '<ol llama-wim="noble majestic"></ol>'],
      ['<ol style="display: none;" LlamA-Wim="majestic"></ol>', '<ol llama-wim="majestic"></ol>'],
      ['<ol style="display: none;" llama-="noble majestic"></ol>', '<ol llama-="noble majestic"></ol>'],
      // Both wildcard names and values.
      ['<ul style="display: none;" alpaca-wool="wooly-warm strong majestic"></ul>', '<ul alpaca-wool="wooly-warm strong"></ul>'],
    ];
  }

  /**
   * @covers ::setConfiguration
   */
  public function testSetConfiguration() {
    $configuration['settings'] = [
      // New lines and spaces are replaced with a single space.
      'allowed_html' => "<a>  <br>\r\n  <p>",
      'filter_html_help' => 1,
      'filter_html_nofollow' => 0,
    ];
    $filter = new FilterHtml($configuration, 'filter_html', ['provider' => 'test']);
    $this->assertSame('<a> <br> <p>', $filter->getConfiguration()['settings']['allowed_html']);
  }

}
