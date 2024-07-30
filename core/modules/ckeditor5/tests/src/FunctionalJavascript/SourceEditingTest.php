<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Symfony\Component\Validator\ConstraintViolation;

// cspell:ignore gramma sourceediting

/**
 * @coversDefaultClass \Drupal\ckeditor5\Plugin\CKEditor5Plugin\SourceEditing
 * @covers \Drupal\ckeditor5\Plugin\CKEditor5PluginManager::getCKEditor5PluginConfig
 * @group ckeditor5
 * @group #slow
 * @internal
 */
class SourceEditingTest extends SourceEditingTestBase {

  /**
   * @covers \Drupal\ckeditor5\Plugin\CKEditor5Plugin\SourceEditing::buildConfigurationForm
   */
  public function testSourceEditingSettingsForm(): void {
    $this->drupalLogin($this->drupalCreateUser(['administer filters']));

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->createNewTextFormat($page, $assert_session);

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
   */
  public function testAllowingExtraAttributes(): void {
    $original_text_editor = Editor::load('test_format');
    $original_text_format = FilterFormat::load('test_format');
    $allowed_elements = HTMLRestrictions::fromTextFormat($original_text_format);
    $filter_html_config = $original_text_format->filters('filter_html')
      ->getConfiguration();
    foreach ($this->providerAllowingExtraAttributes() as $data) {
      $text_editor = clone $original_text_editor;
      $text_format = clone $original_text_format;
      [$original_markup, $expected_markup, $allowed_elements_string] = $data;
      // Allow creating additional HTML using SourceEditing.
      $settings = $text_editor->getSettings();
      if ($allowed_elements_string) {
        $settings['plugins']['ckeditor5_sourceEditing']['allowed_tags'][] = $allowed_elements_string;
      }
      $text_editor->setSettings($settings);

      $new_config = $filter_html_config;
      if ($allowed_elements_string) {
        // Keep the allowed HTML tags in sync.
        $updated_allowed_tags = $allowed_elements->merge(HTMLRestrictions::fromString($allowed_elements_string));
        $new_config['settings']['allowed_html'] = $updated_allowed_tags->toFilterHtmlAllowedTagsString();
      }
      $text_format->setFilterConfig('filter_html', $new_config);

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
      $this->doTestAllowingExtraAttributes($original_markup, $expected_markup, $allowed_elements_string);
    }
  }

  /**
   * Tests extra attributes with a specific data set.
   */
  protected function doTestAllowingExtraAttributes(string $original_markup, string $expected_markup, string $allowed_elements_string): void {
    $this->host->body->value = $original_markup;
    $this->host->save();
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
  protected function providerAllowingExtraAttributes(): array {
    $general_test_case_markup = '<div class="llama" data-llama="🦙"><p data-llama="🦙">The <a href="https://example.com/pirate" class="button" data-grammar="subject">pirate</a> is <a href="https://example.com/irate" class="use-ajax" data-grammar="adjective">irate</a>.</p></div>';
    return [
      'no extra attributes allowed' => [
        $general_test_case_markup,
        '<div class="llama"><p>The <a href="https://example.com/pirate">pirate</a> is <a href="https://example.com/irate">irate</a>.</p></div>',
        '',
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
        '',
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
        '',
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
