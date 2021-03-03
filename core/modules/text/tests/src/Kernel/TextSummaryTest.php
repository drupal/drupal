<?php

namespace Drupal\Tests\text\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests text_summary() with different strings and lengths.
 *
 * @group text
 */
class TextSummaryTest extends KernelTestBase {

  use UserCreationTrait;

  protected static $modules = [
    'system',
    'user',
    'filter',
    'text',
    'field',
    'entity_test',
  ];

  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['text']);
  }

  /**
   * Tests an edge case where the first sentence is a question and
   * subsequent sentences are not. This edge case is documented at
   * https://www.drupal.org/node/180425.
   */
  public function testFirstSentenceQuestion() {
    $text = 'A question? A sentence. Another sentence.';
    $expected = 'A question? A sentence.';
    $this->assertTextSummary($text, $expected, NULL, 30);
  }

  /**
   * Test summary with long example.
   */
  public function testLongSentence() {
    // 125.
    // cSpell:disable
    $text =
      'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. ' .
      // 108.
      'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. ' .
      // 103.
      'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. ' .
      // 110.
      'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';
    $expected = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. ' .
                'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. ' .
                'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.';
    // cSpell:enable
    // First three sentences add up to: 336, so add one for space and then 3 to get half-way into next word.
    $this->assertTextSummary($text, $expected, NULL, 340);
  }

  /**
   * Test various summary length edge cases.
   */
  public function testLength() {
    FilterFormat::create([
      'format' => 'autop',
      'filters' => [
        'filter_autop' => [
          'status' => 1,
        ],
      ],
    ])->save();
    FilterFormat::create([
      'format' => 'autop_correct',
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

    // The summaries we expect text_summary() to return when $size is the index
    // of each array item.
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
   * Test text_summary() returns an empty string without any error when called
   * with an invalid format.
   */
  public function testInvalidFilterFormat() {

    $this->assertTextSummary($this->randomString(100), '', 'non_existent_format');
  }

  /**
   * Calls text_summary() and asserts that the expected teaser is returned.
   */
  public function assertTextSummary($text, $expected, $format = NULL, $size = NULL) {
    $summary = text_summary($text, $format, $size);
    $this->assertSame($expected, $summary, new FormattableMarkup('<pre style="white-space: pre-wrap">@actual</pre> is identical to <pre style="white-space: pre-wrap">@expected</pre>', ['@actual' => $summary, '@expected' => $expected]));
  }

  /**
   * Test required summary.
   */
  public function testRequiredSummary() {
    $this->installEntitySchema('entity_test');
    $this->setUpCurrentUser();
    $field_definition = FieldStorageConfig::create([
      'field_name' => 'test_textwithsummary',
      'type' => 'text_with_summary',
      'entity_type' => 'entity_test',
      'cardinality' => 1,
      'settings' => [
        'max_length' => 200,
      ],
    ]);
    $field_definition->save();

    $instance = FieldConfig::create([
      'field_name' => 'test_textwithsummary',
      'label' => 'A text field',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'settings' => [
        'text_processing' => TRUE,
        'display_summary' => TRUE,
        'required_summary' => TRUE,
      ],
    ]);
    $instance->save();

    EntityFormDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('test_textwithsummary', [
      'type' => 'text_textarea_with_summary',
      'settings' => [
        'summary_rows' => 2,
        'show_summary' => TRUE,
      ],
    ])
      ->save();

    // Check the required summary.
    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
      'type' => 'entity_test',
      'test_textwithsummary' => ['value' => $this->randomMachineName()],
    ]);
    $form = \Drupal::service('entity.form_builder')->getForm($entity);
    $this->assertTrue(!empty($form['test_textwithsummary']['widget'][0]['summary']), 'Summary field is shown');
    $this->assertTrue(!empty($form['test_textwithsummary']['widget'][0]['summary']['#required']), 'Summary field is required');

    // Test validation.
    /** @var \Symfony\Component\Validator\ConstraintViolation[] $violations */
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('test_textwithsummary.0.summary', $violations[0]->getPropertyPath());
    $this->assertEquals('The summary field is required for A text field', $violations[0]->getMessage());
  }

}
