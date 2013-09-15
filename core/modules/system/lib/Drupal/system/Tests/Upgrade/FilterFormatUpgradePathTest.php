<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Upgrade\FilterFormatUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

/**
 * Tests upgrading a bare database with user filter format data.
 *
 * Loads a bare installation of Drupal 7 with filter format data and runs the
 * upgrade process on it. Tests for the conversion filter formats into
 * configurables.
 */
class FilterFormatUpgradePathTest extends UpgradePathTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Filter Formats upgrade test',
      'description' => 'Upgrade tests with filter formats data.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    $path = drupal_get_path('module', 'system');
    $this->databaseDumpFiles = array(
      $path . '/tests/upgrade/drupal-7.bare.standard_all.database.php.gz',
      $path . '/tests/upgrade/drupal-7.roles.database.php',
      $path . '/tests/upgrade/drupal-7.filter_formats.database.php',
    );
    parent::setUp();
  }

  /**
   * Tests expected filter formats entities after a successful upgrade.
   */
  public function testFilterFormatUpgrade() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // Checks that all the formats were upgraded
    $one = entity_load('filter_format', 'format_one');
    $this->assertTrue(!empty($one), 'Filter Format one was successfully upgraded');
    $two = entity_load('filter_format', 'format_two');
    $this->assertTrue(!empty($two), 'Filter Format two was successfully upgraded');

    // Filter format 'Three' is disabled.
    $three_entity = entity_load('filter_format', 'format_three');
    $this->assertFalse($three_entity->status(), 'Filter Format three was successfully upgraded and it is disabled');

    // Check the access to the text formats.

    // Check that the anonymous user role ID has been converted from "1" to
    // "anonymous" and text formats permissions were updated.
    $this->drupalGet('admin/people/permissions');
    $this->assertFieldChecked('edit-anonymous-use-text-format-format-one', 'Use text format format_one permission for "anonymous" is set correctly.');
    $this->assertNoFieldChecked('edit-anonymous-use-text-format-format-two', 'Use text format format_two permission for "anonymous" is set correctly.');

    // Check that the anonymous user role ID has been converted from "2" to
    // "authenticated" and text formats permissions were updated.
    $this->assertNoFieldChecked('edit-authenticated-use-text-format-format-one', 'Use text format format_one permission for "authenticated" is set correctly.');
    $this->assertFieldChecked('edit-authenticated-use-text-format-format-two', 'Use text format format_two permission for "authenticated" is set correctly.');

    // Check that the permission for "gärtner" still exists and text formats
    // permissions were updated.
    $this->assertFieldChecked('edit-4-use-text-format-format-one', 'Use text format format_one permission for role is set correctly.');
    $this->assertNoFieldChecked('edit-4-use-text-format-format-two', 'Use text format format_two permission for role is set correctly.');

    // Check that role 5 cannot access to the defined text formats
    $this->assertNoFieldChecked('edit-5-use-text-format-format-one', 'Use text format format_one permission for role is set correctly.');
    $this->assertNoFieldChecked('edit-5-use-text-format-format-two', 'Use text format format_two permission for role is set correctly.');

    // Check that the format has the missing filter.
    $two = entity_load('filter_format', 'format_two');
    $this->assertTrue($two->filters()->has('missing_filter'));

    // Try to use a filter format with a missing filter, this should not throw
    // an exception.
    $empty_markup = check_markup($this->randomName(), 'format_two');
    $this->assertIdentical($empty_markup, '', 'The filtered text is empty while a filter is missing.');

    // Editing and saving the format should drop the missing filter.
    $this->drupalGet('admin/config/content/formats/manage/format_two');
    $this->assertRaw(t('The %filter filter is missing, and will be removed once this format is saved.', array('%filter' => 'missing_filter')));
    $this->drupalPostForm(NULL, array(), t('Save configuration'));
    filter_formats_reset();
    $two = entity_load('filter_format', 'format_two');
    $this->assertFalse($two->filters()->has('missing_filter'));
  }

}
