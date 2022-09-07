<?php

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Symfony\Component\Validator\ConstraintViolation;

// cspell:ignore gramma sourceediting

/**
 * @coversDefaultClass \Drupal\ckeditor5\Plugin\CKEditor5Plugin\SourceEditing
 * @covers \Drupal\ckeditor5\Plugin\CKEditor5PluginManager::getCKEditor5PluginConfig()
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
            'allowed_html' => '<div class> <p> <br> <a href> <ol> <ul> <li>',
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
            'bulletedList',
            'numberedList',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => ['<div class>'],
          ],
          'ckeditor5_list' => [
            'reversed' => FALSE,
            'startIndex' => FALSE,
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
        'value' => '',
        'format' => 'test_format',
      ],
    ]);
    $this->host->save();

    $this->drupalLogin($this->adminUser);
  }

  /**
   * @covers \Drupal\ckeditor5\Plugin\CKEditor5Plugin\SourceEditing::buildConfigurationForm
   */
  public function testSourceEditingSettingsForm() {
    $this->drupalLogin($this->drupalCreateUser(['administer filters']));

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->createNewTextFormat($page, $assert_session);
    $assert_session->assertWaitOnAjaxRequest();

    // The Source Editing plugin settings form should not be present.
    $assert_session->elementNotExists('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-sourceediting"]');

    $this->assertNotEmpty($assert_session->waitForElement('css', '.ckeditor5-toolbar-item-sourceEditing'));
    $this->triggerKeyUp('.ckeditor5-toolbar-item-sourceEditing', 'ArrowDown');
    $assert_session->assertWaitOnAjaxRequest();

    // The Source Editing plugin settings form should now be present and should
    // have no allowed tags configured.
    $page->clickLink('Source editing');
    $this->assertNotNull($assert_session->waitForElementVisible('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-sourceediting-allowed-tags"]'));

    $javascript = <<<JS
      const allowedTags = document.querySelector('[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-sourceediting-allowed-tags"]');
      allowedTags.value = '<div data-foo>';
      allowedTags.dispatchEvent(new Event('input'));
JS;
    $this->getSession()->executeScript($javascript);

    // Immediately save the configuration. Intentionally do nothing that would
    // trigger an AJAX rebuild.
    $page->pressButton('Save configuration');

    // Verify that the configuration was saved.
    $this->drupalGet('admin/config/content/formats/manage/ckeditor5');
    $page->clickLink('Source editing');
    $this->assertNotNull($ghs_textarea = $assert_session->waitForElementVisible('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-sourceediting-allowed-tags"]'));

    $ghs_string = '<div data-foo>';
    $this->assertSame($ghs_string, $ghs_textarea->getValue());
    $allowed_html_field = $assert_session->fieldExists('filters[filter_html][settings][allowed_html]');
    $this->assertStringContainsString($ghs_string, $allowed_html_field->getValue(), "$ghs_string not found in the allowed tags value of: {$allowed_html_field->getValue()}");
  }

  /**
   * Tests allowing extra attributes on already supported tags using GHS.
   *
   * @dataProvider providerAllowingExtraAttributes
   */
  public function testAllowingExtraAttributes(string $original_markup, string $expected_markup, ?string $allowed_elements_string = NULL) {
    $this->host->body->value = $original_markup;
    $this->host->save();

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
    $general_test_case_markup = '<div class="llama" data-llama="🦙"><p data-llama="🦙">The <a href="https://example.com/pirate" class="button" data-grammar="subject">pirate</a> is <a href="https://example.com/irate" class="use-ajax" data-grammar="adjective">irate</a>.</p></div>';
    return [
      'no extra attributes allowed' => [
        $general_test_case_markup,
        '<div class="llama"><p>The <a href="https://example.com/pirate">pirate</a> is <a href="https://example.com/irate">irate</a>.</p></div>',
      ],

      // Common case: any attribute that is not `style` or `class`.
      '<a data-grammar="subject">' => [
        $general_test_case_markup,
        '<div class="llama"><p>The <a href="https://example.com/pirate" data-grammar="subject">pirate</a> is <a href="https://example.com/irate">irate</a>.</p></div>',
        '<a data-grammar="subject">',
      ],
      '<a data-grammar="adjective">' => [
        $general_test_case_markup,
        '<div class="llama"><p>The <a href="https://example.com/pirate">pirate</a> is <a href="https://example.com/irate" data-grammar="adjective">irate</a>.</p></div>',
        '<a data-grammar="adjective">',
      ],
      '<a data-grammar>' => [
        $general_test_case_markup,
        '<div class="llama"><p>The <a href="https://example.com/pirate" data-grammar="subject">pirate</a> is <a href="https://example.com/irate" data-grammar="adjective">irate</a>.</p></div>',
        '<a data-grammar>',
      ],

      // Edge case: `class`.
      '<a class="button">' => [
        $general_test_case_markup,
        '<div class="llama"><p>The <a class="button" href="https://example.com/pirate">pirate</a> is <a href="https://example.com/irate">irate</a>.</p></div>',
        '<a class="button">',
      ],
      '<a class="use-ajax">' => [
        $general_test_case_markup,
        '<div class="llama"><p>The <a href="https://example.com/pirate">pirate</a> is <a class="use-ajax" href="https://example.com/irate">irate</a>.</p></div>',
        '<a class="use-ajax">',
      ],
      '<a class>' => [
        $general_test_case_markup,
        '<div class="llama"><p>The <a class="button" href="https://example.com/pirate">pirate</a> is <a class="use-ajax" href="https://example.com/irate">irate</a>.</p></div>',
        '<a class>',
      ],

      // Edge case: $text-container wildcard with additional
      // attribute.
      '<$text-container data-llama>' => [
        $general_test_case_markup,
        '<div class="llama" data-llama="🦙"><p data-llama="🦙">The <a href="https://example.com/pirate">pirate</a> is <a href="https://example.com/irate">irate</a>.</p></div>',
        '<$text-container data-llama>',
      ],
      // Edge case: $text-container wildcard with stricter attribute
      // constrain.
      '<$text-container class="not-llama">' => [
        $general_test_case_markup,
        '<div class="llama"><p>The <a href="https://example.com/pirate">pirate</a> is <a href="https://example.com/irate">irate</a>.</p></div>',
        '<$text-container class="not-llama">',
      ],

      // Edge case: wildcard attribute names:
      // - prefix, f.e. `data-*`
      // - infix, f.e. `*gramma*`
      // - suffix, f.e. `*-grammar`
      '<a data-*>' => [
        $general_test_case_markup,
        '<div class="llama"><p>The <a href="https://example.com/pirate" data-grammar="subject">pirate</a> is <a href="https://example.com/irate" data-grammar="adjective">irate</a>.</p></div>',
        '<a data-*>',
      ],
      '<a *gramma*>' => [
        $general_test_case_markup,
        '<div class="llama"><p>The <a href="https://example.com/pirate" data-grammar="subject">pirate</a> is <a href="https://example.com/irate" data-grammar="adjective">irate</a>.</p></div>',
        '<a *gramma*>',
      ],
      '<a *-grammar>' => [
        $general_test_case_markup,
        '<div class="llama"><p>The <a href="https://example.com/pirate" data-grammar="subject">pirate</a> is <a href="https://example.com/irate" data-grammar="adjective">irate</a>.</p></div>',
        '<a *-grammar>',
      ],

      // Edge case: concrete attribute with wildcard class value.
      '<a class="use-*">' => [
        $general_test_case_markup,
        '<div class="llama"><p>The <a href="https://example.com/pirate">pirate</a> is <a class="use-ajax" href="https://example.com/irate">irate</a>.</p></div>',
        '<a class="use-*">',
      ],

      // Edge case: concrete attribute with wildcard attribute value.
      '<a data-grammar="sub*">' => [
        $general_test_case_markup,
        '<div class="llama"><p>The <a href="https://example.com/pirate" data-grammar="subject">pirate</a> is <a href="https://example.com/irate">irate</a>.</p></div>',
        '<a data-grammar="sub*">',
      ],

      // Edge case: `data-*` with wildcard attribute value.
      '<a data-*="sub*">' => [
        $general_test_case_markup,
        '<div class="llama"><p>The <a href="https://example.com/pirate" data-grammar="subject">pirate</a> is <a href="https://example.com/irate">irate</a>.</p></div>',
        '<a data-*="sub*">',
      ],

      // Edge case: `style`.
      // @todo https://www.drupal.org/project/drupal/issues/3304832

      // Edge case: `type` attribute on lists.
      // @todo Remove in https://www.drupal.org/project/drupal/issues/3274635.
      'no numberedList-related additions to the Source Editing configuration' => [
        '<ol type="A"><li>foo</li><li>bar</li></ol>',
        '<ol><li>foo</li><li>bar</li></ol>',
      ],
      '<ol type>' => [
        '<ol type="A"><li>foo</li><li>bar</li></ol>',
        '<ol type="A"><li>foo</li><li>bar</li></ol>',
        '<ol type>',
      ],
      '<ol type="A">' => [
        '<ol type="A"><li>foo</li><li>bar</li></ol>',
        '<ol type="A"><li>foo</li><li>bar</li></ol>',
        '<ol type="A">',
      ],
      'no bulletedList-related additions to the Source Editing configuration' => [
        '<ul type="circle"><li>foo</li><li>bar</li></ul>',
        '<ul><li>foo</li><li>bar</li></ul>',
      ],
      '<ul type>' => [
        '<ul type="circle"><li>foo</li><li>bar</li></ul>',
        '<ul type="circle"><li>foo</li><li>bar</li></ul>',
        '<ul type>',
      ],
      '<ul type="circle">' => [
        '<ul type="circle"><li>foo</li><li>bar</li></ul>',
        '<ul type="circle"><li>foo</li><li>bar</li></ul>',
        '<ul type="circle">',
      ],
    ];
  }

}
