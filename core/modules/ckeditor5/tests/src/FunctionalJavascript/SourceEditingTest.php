<?php

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * @coversDefaultClass \Drupal\ckeditor5\Plugin\CKEditor5Plugin\SourceEditing
 * @group ckeditor5
 * @internal
 */
class SourceEditingTest extends CKEditor5TestBase {

  use CKEditor5TestTrait;

  /**
   * The user to use during testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A host entity with a body field whose text to edit with CKEditor 5.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $host;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor5',
    'node',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'settings' => [
            'allowed_html' => '<p> <br> <a href>',
          ],
        ],
        'filter_align' => ['status' => TRUE],
        'filter_caption' => ['status' => TRUE],
      ],
    ])->save();
    Editor::create([
      'editor' => 'ckeditor5',
      'format' => 'test_format',
      'settings' => [
        'toolbar' => [
          'items' => [
            'sourceEditing',
            'link',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => [],
          ],
        ],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
    ])->save();
    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('test_format'),
        FilterFormat::load('test_format')
      ))
    ));
    $this->adminUser = $this->drupalCreateUser([
      'use text format test_format',
      'bypass node access',
    ]);

    // Create a sample host entity to test CKEditor 5.
    $this->host = $this->createNode([
      'type' => 'page',
      'title' => 'Animals with strange names',
      'body' => [
        'value' => '<p>The <a href="https://example.com/pirate" class="button" data-grammar="subject">pirate</a> is <a href="https://example.com/irate" class="use-ajax" data-grammar="adjective">irate</a>.</p>',
        'format' => 'test_format',
      ],
    ]);
    $this->host->save();

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests allowing extra attributes on already supported tags using GHS.
   *
   * @dataProvider providerAllowingExtraAttributes
   */
  public function testAllowingExtraAttributes(string $expected_markup, ?string $allowed_elements_string = NULL) {
    if ($allowed_elements_string) {
      // Allow creating additional HTML using SourceEditing.
      $text_editor = Editor::load('test_format');
      $settings = $text_editor->getSettings();
      $settings['plugins']['ckeditor5_sourceEditing']['allowed_tags'][] = $allowed_elements_string;
      $text_editor->setSettings($settings);

      // Keep the allowed HTML tags in sync.
      $text_format = FilterFormat::load('test_format');
      $allowed_elements = HTMLRestrictions::fromTextFormat($text_format);
      $updated_allowed_tags = $allowed_elements->merge(HTMLRestrictions::fromString($allowed_elements_string));
      $filter_html_config = $text_format->filters('filter_html')
        ->getConfiguration();
      $filter_html_config['settings']['allowed_html'] = $updated_allowed_tags->toFilterHtmlAllowedTagsString();
      $text_format->setFilterConfig('filter_html', $filter_html_config);

      // Verify the text format and editor are still a valid pair.
      $this->assertSame([], array_map(
        function (ConstraintViolation $v) {
          return (string) $v->getMessage();
        },
        iterator_to_array(CKEditor5::validatePair(
          $text_editor,
          $text_format
        ))
      ));

      // If valid, save both.
      $text_format->save();
      $text_editor->save();
    }

    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assertSame($expected_markup, $this->getEditorDataAsHtmlString());
  }

  /**
   * Data provider for ::testAllowingExtraAttributes().
   *
   * @return array
   *   The test cases.
   */
  public function providerAllowingExtraAttributes(): array {
    return [
      'no extra attributes allowed' => [
        '<p>The <a href="https://example.com/pirate">pirate</a> is <a href="https://example.com/irate">irate</a>.</p>',
      ],

      // Common case: any attribute that is not `style` or `class`.
      '<a data-grammar="subject">' => [
        '<p>The <a href="https://example.com/pirate" data-grammar="subject">pirate</a> is <a href="https://example.com/irate">irate</a>.</p>',
        '<a data-grammar="subject">',
      ],
      '<a data-grammar="adjective">' => [
        '<p>The <a href="https://example.com/pirate">pirate</a> is <a href="https://example.com/irate" data-grammar="adjective">irate</a>.</p>',
        '<a data-grammar="adjective">',
      ],
      '<a data-grammar>' => [
        '<p>The <a href="https://example.com/pirate" data-grammar="subject">pirate</a> is <a href="https://example.com/irate" data-grammar="adjective">irate</a>.</p>',
        '<a data-grammar>',
      ],

      // Edge case: `class`.
      '<a class="button">' => [
        '<p>The <a class="button" href="https://example.com/pirate">pirate</a> is <a href="https://example.com/irate">irate</a>.</p>',
        '<a class="button">',
      ],
      '<a class="use-ajax">' => [
        '<p>The <a href="https://example.com/pirate">pirate</a> is <a class="use-ajax" href="https://example.com/irate">irate</a>.</p>',
        '<a class="use-ajax">',
      ],
      '<a class>' => [
        '<p>The <a class="button" href="https://example.com/pirate">pirate</a> is <a class="use-ajax" href="https://example.com/irate">irate</a>.</p>',
        '<a class>',
      ],

      // Edge case: `style`.
      // @todo https://www.drupal.org/project/drupal/issues/3260857
    ];
  }

}
