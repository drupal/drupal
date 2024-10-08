<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Symfony\Component\Validator\ConstraintViolationInterface;

// cspell:ignore sourceediting

/**
 * @coversDefaultClass \Drupal\ckeditor5\Plugin\CKEditor5Plugin\SourceEditing
 * @covers \Drupal\ckeditor5\Plugin\CKEditor5PluginManager::getCKEditor5PluginConfig
 * @group ckeditor5
 * @internal
 */
class SourceEditingEmptyElementTest extends SourceEditingTestBase {

  /**
   * Tests creating empty inline elements using Source Editing.
   *
   * @testWith ["<p>Before <i class=\"fab fa-drupal\"></i> and after.</p>", "<p>Before and after.</p>", "<p>Before and after.</p>", null]
   *           ["<p>Before <i class=\"fab fa-drupal\"></i> and after.</p>", "<p>Before &nbsp;and after.</p>", null, "<i>"]
   *           ["<p>Before <i class=\"fab fa-drupal\"></i> and after.</p>", null, null, "<i class>"]
   *           ["<p>Before <span class=\"icon my-icon\"></span> and after.</p>", "<p>Before and after.</p>", "<p>Before and after.</p>", null]
   *           ["<p>Before <span class=\"icon my-icon\"></span> and after.</p>", "<p>Before &nbsp;and after.</p>", null, "<span>"]
   *           ["<p>Before <span class=\"icon my-icon\"></span> and after.</p>", "<p>Before <span class=\"icon\"></span> and after.</p>", null, "<span class=\"icon\">"]
   */
  public function testEmptyInlineElement(string $input, ?string $expected_output_when_restricted, ?string $expected_output_when_unrestricted, ?string $allowed_elements_string): void {
    $this->host->body->value = $input;
    $this->host->save();

    // If no expected output is specified, it should be identical to the input.
    if ($expected_output_when_restricted === NULL) {
      $expected_output_when_restricted = $input;
    }
    if ($expected_output_when_unrestricted === NULL) {
      $expected_output_when_unrestricted = $input;
    }

    $text_editor = Editor::load('test_format');
    $text_format = FilterFormat::load('test_format');
    if ($allowed_elements_string) {
      // Allow creating additional HTML using SourceEditing.
      $settings = $text_editor->getSettings();
      $settings['plugins']['ckeditor5_sourceEditing']['allowed_tags'][] = $allowed_elements_string;
      $text_editor->setSettings($settings);

      // Keep the allowed HTML tags in sync.
      $allowed_elements = HTMLRestrictions::fromTextFormat($text_format);
      $updated_allowed_tags = $allowed_elements->merge(HTMLRestrictions::fromString($allowed_elements_string));
      $filter_html_config = $text_format->filters('filter_html')
        ->getConfiguration();
      $filter_html_config['settings']['allowed_html'] = $updated_allowed_tags->toFilterHtmlAllowedTagsString();
      $text_format->setFilterConfig('filter_html', $filter_html_config);

      // Verify the text format and editor are still a valid pair.
      $this->assertSame([], array_map(
        function (ConstraintViolationInterface $v) {
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
    $this->assertSame($expected_output_when_restricted, $this->getEditorDataAsHtmlString());

    // Make the text format unrestricted: disable filter_html.
    $text_format
      ->setFilterConfig('filter_html', ['status' => FALSE])
      ->save();

    // Verify the text format and editor are still a valid pair.
    $this->assertSame([], array_map(
      function (ConstraintViolationInterface $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        $text_editor,
        $text_format
      ))
    ));

    // Test with a text format allowing arbitrary HTML.
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assertSame($expected_output_when_unrestricted, $this->getEditorDataAsHtmlString());
  }

}
