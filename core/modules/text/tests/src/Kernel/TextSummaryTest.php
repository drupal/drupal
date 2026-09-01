<?php

declare(strict_types=1);

namespace Drupal\Tests\text\Kernel;

use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\Render\FilteredMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\text\TextSummary;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests TextSummary::generate() with different strings and lengths.
 */
#[Group('text')]
#[RunTestsInSeparateProcesses]
#[CoversMethod(TextSummary::class, 'generate')]
class TextSummaryTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'filter',
    'text',
    'field',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['text']);
  }

  /**
   * Tests text summaries for a question followed by a sentence.
   */
  public function testFirstSentenceQuestion(): void {
    $text = 'A question? A sentence. Another sentence.';
    $expected = 'A question? A sentence.';
    $this->assertTextSummary($text, $expected, NULL, 30);
  }

  /**
   * Tests summary with long example.
   */
  public function testLongSentence(): void {
    // Setup test strings and expectations.
    // cSpell:disable
    $long_sentence_text =
      'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. ' .
      // 108.
      'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. ' .
      // 103.
      'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. ' .
      // 110.
      'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';
    $long_sentence_expected = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. ' .
                'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. ' .
                'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.';

    $long_paragraph_text =
      'Dr. Cras ultricies ligula sed magna dictum porta.&nbsp;Curabitur non nulla sit amet nisl tempus convallis quis ac lectus.&nbsp;Pellentesque in ipsum ' .
      'id orci porta dapibus.&nbsp;Vestibulum ac diam sit amet quam vehicula elementum sed sit amet dui.&nbsp;Nulla porttitor.&nbsp;Cras ultricies&nbsp;ligula sed magna ' .
      'dictum porta.&nbsp;Nulla quis lorem ut libero malesuada feugiat.&nbsp;Proin eget tortor risus.&nbsp;Curabitur non nulla sit amet nisl tempus convallis quis ac ' .
      'lectus.&nbsp;Proin eget tortor risus.&nbsp;Praesent sapien massa, convallis a pellentesque nec, egestas non nisi.&nbsp;Vivamus suscipit tortor eget felis ' .
      'porttitor volutpat. Quisque velit nisi, pretium ut lacinia in, elementum id enim. Curabitur arcu erat, accumsan id imperdiet et, porttitor at sem. ' .
      'Quisque velit nisi, pretium ut lacinia in, elementum id enim. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; ' .
      'Donec velit neque, auctor sit amet aliquam vel, ullamcorper sit amet ligula. Curabitur aliquet quam id dui posuere blandit. Curabitur aliquet quam id ' .
      'dui posuere blandit. Curabitur non nulla sit amet nisl tempus convallis quis ac lectus. Pellentesque in ipsum id orci porta dapibus';

    $long_paragraph_expected = 'Dr. Cras ultricies ligula sed magna dictum porta.&nbsp;Curabitur non nulla sit amet nisl tempus convallis quis ac lectus.&nbsp;Pellentesque in ipsum ' .
      'id orci porta dapibus.&nbsp;Vestibulum ac diam sit amet quam vehicula elementum sed sit amet dui.&nbsp;Nulla porttitor.';
    // cSpell:enable

    // First three sentences add up to: 336, so add one for space and then 3 to
    // get half-way into next word.
    $this->assertTextSummary($long_sentence_text, $long_sentence_expected, NULL, 340);

    // Confirm long paragraph breaks correctly.
    $this->assertTextSummary($long_paragraph_text, $long_paragraph_expected, NULL, 300);
  }

  /**
   * Tests various summary length edge cases.
   */
  public function testLength(): void {
    FilterFormat::create([
      'format' => 'autop',
      'name' => 'Autop',
      'filters' => [
        'filter_autop' => [
          'status' => 1,
        ],
      ],
    ])->save();
    FilterFormat::create([
      'format' => 'autop_correct',
      'name' => 'Autop correct',
      'filters' => [
        'filter_autop' => [
          'status' => 1,
        ],
        'filter_htmlcorrector' => [
          'status' => 1,
        ],
      ],
    ])->save();

    // This string tests a number of edge cases.
    $text = "<p>\nHi\n</p>\n<p>\nfolks\n<br />\n!\n</p>";

    // The summaries we expect TextSummary::generate() to return when
    // $size is the index of each array item.
    // Using no text format:
    $format = NULL;
    $i = 0;
    $this->assertTextSummary($text, "<p>\nHi\n</p>\n<p>\nfolks\n<br />\n!\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<", $format, $i++);
    $this->assertTextSummary($text, "<p", $format, $i++);
    $this->assertTextSummary($text, "<p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\n", $format, $i++);
    $this->assertTextSummary($text, "<p>\nH", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n<", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>\n<p>\nfolks\n<br />\n!\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>\n<p>\nfolks\n<br />\n!\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>\n<p>\nfolks\n<br />\n!\n</p>", $format, $i++);

    // Using a text format with filter_autop enabled.
    $format = 'autop';
    $i = 0;
    $this->assertTextSummary($text, "<p>\nHi\n</p>\n<p>\nfolks\n<br />\n!\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<", $format, $i++);
    $this->assertTextSummary($text, "<p", $format, $i++);
    $this->assertTextSummary($text, "<p>", $format, $i++);
    $this->assertTextSummary($text, "<p>", $format, $i++);
    $this->assertTextSummary($text, "<p>", $format, $i++);
    $this->assertTextSummary($text, "<p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>\n<p>\nfolks\n<br />\n!\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>\n<p>\nfolks\n<br />\n!\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>\n<p>\nfolks\n<br />\n!\n</p>", $format, $i++);

    // Using a text format with filter_autop and filter_htmlcorrector enabled.
    $format = 'autop_correct';
    $i = 0;
    $this->assertTextSummary($text, "<p>\nHi\n</p>\n<p>\nfolks\n<br />\n!\n</p>", $format, $i++);
    $this->assertTextSummary($text, "", $format, $i++);
    $this->assertTextSummary($text, "<p></p>", $format, $i++);
    $this->assertTextSummary($text, "<p></p>", $format, $i++);
    $this->assertTextSummary($text, "<p></p>", $format, $i++);
    $this->assertTextSummary($text, "<p></p>", $format, $i++);
    $this->assertTextSummary($text, "<p></p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>\n<p>\nfolks\n<br />\n!\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>\n<p>\nfolks\n<br />\n!\n</p>", $format, $i++);
    $this->assertTextSummary($text, "<p>\nHi\n</p>\n<p>\nfolks\n<br />\n!\n</p>", $format, $i++);
  }

  /**
   * Tests text summaries with an invalid filter format.
   */
  public function testInvalidFilterFormat(): void {

    $this->assertTextSummary($this->randomString(100), '', 'non_existent_format');
  }

  /**
   * Calls TextSummary::generate() and asserts that the expected teaser is returned.
   *
   * @internal
   */
  public function assertTextSummary(string $text, string $expected, ?string $format = NULL, int $size = 600): void {
    $summary = \Drupal::service(TextSummary::class)->generate($text, $format, $size);
    $this->assertSame($expected, $summary, '<pre style="white-space: pre-wrap">' . $summary . '</pre> is identical to <pre style="white-space: pre-wrap">' . $expected . '</pre>');
  }

  /**
   * Test text normalization when filter_html or filter_htmlcorrector enabled.
   */
  public function testNormalization(): void {
    FilterFormat::create([
      'format' => 'filter_html_enabled',
      'name' => 'Filter HTML enabled',
      'filters' => [
        'filter_html' => [
          'status' => 1,
          'settings' => [
            'allowed_html' => '<strong>',
          ],
        ],
      ],
    ])->save();
    FilterFormat::create([
      'format' => 'filter_htmlcorrector_enabled',
      'name' => 'Filter HTML corrector enabled',
      'filters' => [
        'filter_htmlcorrector' => [
          'status' => 1,
        ],
      ],
    ])->save();
    FilterFormat::create([
      'format' => 'neither_filter_enabled',
      'name' => 'Neither filter enabled',
      'filters' => [],
    ])->save();

    $filtered_markup = FilteredMarkup::create('<div><strong><span>Hello World</span></strong></div>');
    // With either HTML filter enabled, TextSummary::generate() will
    // normalize the text using HTML::normalize().
    $summary = \Drupal::service(TextSummary::class)->generate($filtered_markup, 'filter_html_enabled', 30);
    $this->assertStringContainsString('<div><strong><span>', $summary);
    $this->assertStringContainsString('</span></strong></div>', $summary);
    $summary = \Drupal::service(TextSummary::class)->generate($filtered_markup, 'filter_htmlcorrector_enabled', 30);
    $this->assertStringContainsString('<div><strong><span>', $summary);
    $this->assertStringContainsString('</span></strong></div>', $summary);
    // If neither filter is enabled, the text will not be normalized.
    $summary = \Drupal::service(TextSummary::class)->generate($filtered_markup, 'neither_filter_enabled', 30);
    $this->assertStringContainsString('<div><strong><span>', $summary);
    $this->assertStringNotContainsString('</span></strong></div>', $summary);
  }

}
