<?php

namespace Drupal\Tests\ckeditor5\Functional\Update;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\editor\EditorInterface;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\FilterFormatInterface;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Tests the update path for the CKEditor 5 image toolbar item.
 *
 * @group Update
 * @group #slow
 */
class CKEditor5UpdateImageToolbarItemTest extends UpdatePathTestBase {

  use CKEditor5TestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/ckeditor5-3222756.php',
    ];
  }

  /**
   * Tests that `uploadImage` toolbar item is updated to `drupalInsertImage`.
   *
   * @dataProvider provider
   */
  public function test(bool $filter_html_is_enabled, bool $image_uploads_are_enabled, bool $source_editing_is_already_enabled, array $expected_source_editing_additions): void {
    // Apply tweaks for the currently provided test case.
    $format = FilterFormat::load('test_format_image');
    if (!$filter_html_is_enabled) {
      $format->setFilterConfig('filter_html', ['status' => FALSE]);
    }
    $editor = Editor::load('test_format_image');
    if (!$image_uploads_are_enabled) {
      $editor->setImageUploadSettings(['status' => FALSE]);
    }
    if (!$source_editing_is_already_enabled) {
      $settings = $editor->getSettings();
      // Remove the `sourceEditing` toolbar item.
      unset($settings['toolbar']['items'][3]);
      // Remove the corresponding plugin settings (allowing `<img data-foo>`).
      unset($settings['plugins']['ckeditor5_sourceEditing']);
      $editor->setSettings($settings);
      if ($filter_html_is_enabled) {
        // Stop allowing `<img data-foo>`.
        $filter_html_config = $format->filters('filter_html')
          ->getConfiguration();
        $filter_html_config['settings']['allowed_html'] = str_replace('data-foo', '', $filter_html_config['settings']['allowed_html']);
        $format->setFilterConfig('filter_html', $filter_html_config);
      }
    }
    $format->trustData()->save();
    $editor->trustData()->save();

    // Run update path; snapshot the Text Format and Editor before and after.
    $editor_before = Editor::load('test_format_image');
    $filter_format_before = $editor->getFilterFormat();
    $this->runUpdates();
    $editor_after = Editor::load('test_format_image');
    $filter_format_after = $editor->getFilterFormat();

    // 1. Toolbar item: `uploadImage` -> `drupalInsertImage`, position must be
    // unchanged.
    $this->assertContains('uploadImage', $editor_before->getSettings()['toolbar']['items']);
    $this->assertNotContains('drupalInsertImage', $editor_before->getSettings()['toolbar']['items']);
    $this->assertNotContains('uploadImage', $editor_after->getSettings()['toolbar']['items']);
    $this->assertContains('drupalInsertImage', $editor_after->getSettings()['toolbar']['items']);
    $this->assertSame(
      array_search('uploadImage', $editor_before->getSettings()['toolbar']['items']),
      array_search('drupalInsertImage', $editor_after->getSettings()['toolbar']['items'])
    );

    // 2. Even though `sourceEditing` may not be enabled before this update, it
    // must be after, at least if image uploads are disabled: extra mark-up will
    // be added to its configuration to avoid breaking backwards compatibility.
    if (!$image_uploads_are_enabled) {
      if (!$source_editing_is_already_enabled) {
        $this->assertNotContains('sourceEditing', $editor_before->getSettings()['toolbar']['items']);
      }
      $this->assertContains('sourceEditing', $editor_after->getSettings()['toolbar']['items']);
      $source_editing_before = $source_editing_is_already_enabled
        ? static::getSourceEditingRestrictions($editor_before)
        : HTMLRestrictions::emptySet();
      $source_editing_after = static::getSourceEditingRestrictions($editor_after);
      if ($source_editing_is_already_enabled) {
        // Nothing has been removed from the allowed source editing tags.
        $this->assertFalse($source_editing_before->allowsNothing());
        $this->assertTrue($source_editing_before->diff($source_editing_after)
          ->allowsNothing());
      }
      $this->assertSame($expected_source_editing_additions, $source_editing_after->diff($source_editing_before)
        ->toCKEditor5ElementsArray());
    }
    // Otherwise verify that sourceEditing configuration remains unchanged.
    else {
      if (!$source_editing_is_already_enabled) {
        $this->assertNotContains('sourceEditing', $editor_before->getSettings()['toolbar']['items']);
      }
      else {
        $this->assertContains('sourceEditing', $editor_before->getSettings()['toolbar']['items']);
        $this->assertSame(
          static::getSourceEditingRestrictions($editor_before)->toCKEditor5ElementsArray(),
          static::getSourceEditingRestrictions($editor_after)->toCKEditor5ElementsArray()
        );
      }
    }

    // 3. `filter_html` restrictions MUST remain unchanged.
    if ($filter_html_is_enabled) {
      $filter_html_before = static::getFilterHtmlRestrictions($filter_format_before);
      $filter_html_after = static::getFilterHtmlRestrictions($filter_format_after);
      $this->assertTrue($filter_html_before->diff($filter_html_after)->allowsNothing());
      $this->assertTrue($filter_html_after->diff($filter_html_before)->allowsNothing());
    }

    // 4. After: text format and editor still form a valid pair.
    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair($editor_after, $filter_format_after))
    ));
  }

  /**
   * Data provider for ::test().
   *
   * @return array
   *   The test cases.
   */
  public function provider(): array {
    // There are 3 aspects that need to be verified, each can be true or false,
    // making for 8 test cases in total.
    $test_cases = [];
    foreach ([TRUE, FALSE] as $filter_html_is_enabled) {
      $test_case_label_part_one = sprintf("filter_html %s", $filter_html_is_enabled ? 'enabled' : 'disabled');
      foreach ([TRUE, FALSE] as $image_uploads_enabled) {
        $test_case_label_part_two = sprintf("image uploads %s", $image_uploads_enabled ? 'enabled' : 'disabled');
        foreach ([TRUE, FALSE] as $source_editing_already_enabled) {
          $test_case_label_part_three = sprintf("sourceEditing initially %s", $source_editing_already_enabled ? 'enabled' : 'disabled');
          // Generate the test case.
          $label = implode(', ', [$test_case_label_part_one, $test_case_label_part_two, $test_case_label_part_three]);
          $test_cases[$label] = [
            'filter_html' => $filter_html_is_enabled,
            'image uploads' => $image_uploads_enabled,
            'sourceEditing already enabled' => $source_editing_already_enabled,
            'expected sourceEditing additions' => $image_uploads_enabled ? [] : ['<img data-entity-uuid data-entity-type>'],
          ];
        }
      }
    }
    return $test_cases;
  }

  /**
   * Gets the configured HTML restrictions for the Source Editing plugin.
   *
   * @param \Drupal\editor\EditorInterface $editor
   *   Text editor configured to use CKEditor 5, with Source Editing enabled.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *   The configured HTML restrictions.
   */
  private static function getSourceEditingRestrictions(EditorInterface $editor): HTMLRestrictions {
    $settings = $editor->getSettings();
    $source_editing_allowed_tags = $settings['plugins']['ckeditor5_sourceEditing']['allowed_tags'];
    return HTMLRestrictions::fromString(implode(' ', $source_editing_allowed_tags));
  }

  /**
   * Gets the configured restrictions for the `filter_html` filter plugin.
   *
   * @param \Drupal\filter\FilterFormatInterface $format
   *   Text format configured to use `filter_html`.
   *
   * @return \Drupal\ckeditor5\HTMLRestrictions
   *   The configured HTML restrictions.
   */
  private static function getFilterHtmlRestrictions(FilterFormatInterface $format): HTMLRestrictions {
    $allowed_html = $format
      ->filters('filter_html')
      ->getConfiguration()['settings']['allowed_html'];
    return HTMLRestrictions::fromString($allowed_html);
  }

}
