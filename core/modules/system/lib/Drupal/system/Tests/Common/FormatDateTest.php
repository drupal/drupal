<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\FormatDateTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the format_date() function.
 */
class FormatDateTest extends WebTestBase {

  /**
   * Arbitrary langcode for a custom language.
   */
  const LANGCODE = 'xx';

  public static function getInfo() {
    return array(
      'name' => 'Format date',
      'description' => 'Test the format_date() function.',
      'group' => 'Common',
    );
  }

  function setUp() {
    parent::setUp('locale');
    variable_set('configurable_timezones', 1);
    variable_set('date_format_long', 'l, j. F Y - G:i');
    variable_set('date_format_medium', 'j. F Y - G:i');
    variable_set('date_format_short', 'Y M j - g:ia');
    variable_set('locale_custom_strings_' . self::LANGCODE, array(
      '' => array('Sunday' => 'domingo'),
      'Long month name' => array('March' => 'marzo'),
    ));
    $this->refreshVariables();
  }

  /**
   * Test admin-defined formats in format_date().
   */
  function testAdminDefinedFormatDate() {
    // Create an admin user.
    $this->admin_user = $this->drupalCreateUser(array('administer site configuration'));
    $this->drupalLogin($this->admin_user);

    // Add new date format.
    $admin_date_format = 'j M y';
    $edit = array('date_format' => $admin_date_format);
    $this->drupalPost('admin/config/regional/date-time/formats/add', $edit, t('Add format'));

    // Add new date type.
    $edit = array(
      'date_type' => 'Example Style',
      'machine_name' => 'example_style',
      'date_format' => $admin_date_format,
    );
    $this->drupalPost('admin/config/regional/date-time/types/add', $edit, t('Add date type'));

    $timestamp = strtotime('2007-03-10T00:00:00+00:00');
    $this->assertIdentical(format_date($timestamp, 'example_style', '', 'America/Los_Angeles'), '9 Mar 07', t('Test format_date() using an admin-defined date type.'));
    $this->assertIdentical(format_date($timestamp, 'undefined_style'), format_date($timestamp, 'medium'), t('Test format_date() defaulting to medium when $type not found.'));
  }

  /**
   * Tests for the format_date() function.
   */
  function testFormatDate() {
    global $user;

    $language_interface = drupal_container()->get(LANGUAGE_TYPE_INTERFACE);

    $timestamp = strtotime('2007-03-26T00:00:00+00:00');
    $this->assertIdentical(format_date($timestamp, 'custom', 'l, d-M-y H:i:s T', 'America/Los_Angeles', 'en'), 'Sunday, 25-Mar-07 17:00:00 PDT', t('Test all parameters.'));
    $this->assertIdentical(format_date($timestamp, 'custom', 'l, d-M-y H:i:s T', 'America/Los_Angeles', self::LANGCODE), 'domingo, 25-Mar-07 17:00:00 PDT', t('Test translated format.'));
    $this->assertIdentical(format_date($timestamp, 'custom', '\\l, d-M-y H:i:s T', 'America/Los_Angeles', self::LANGCODE), 'l, 25-Mar-07 17:00:00 PDT', t('Test an escaped format string.'));
    $this->assertIdentical(format_date($timestamp, 'custom', '\\\\l, d-M-y H:i:s T', 'America/Los_Angeles', self::LANGCODE), '\\domingo, 25-Mar-07 17:00:00 PDT', t('Test format containing backslash character.'));
    $this->assertIdentical(format_date($timestamp, 'custom', '\\\\\\l, d-M-y H:i:s T', 'America/Los_Angeles', self::LANGCODE), '\\l, 25-Mar-07 17:00:00 PDT', t('Test format containing backslash followed by escaped format string.'));
    $this->assertIdentical(format_date($timestamp, 'custom', 'l, d-M-y H:i:s T', 'Europe/London', 'en'), 'Monday, 26-Mar-07 01:00:00 BST', t('Test a different time zone.'));

    // Create an admin user and add Spanish language.
    $admin_user = $this->drupalCreateUser(array('administer languages'));
    $this->drupalLogin($admin_user);
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => self::LANGCODE,
      'name' => self::LANGCODE,
      'direction' => LANGUAGE_LTR,
    );
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add custom language'));

    // Set language prefix.
    $edit = array('prefix[' . self::LANGCODE . ']' => self::LANGCODE);
    $this->drupalPost('admin/config/regional/language/detection/url', $edit, t('Save configuration'));

    // Create a test user to carry out the tests.
    $test_user = $this->drupalCreateUser();
    $this->drupalLogin($test_user);
    $edit = array('preferred_langcode' => self::LANGCODE, 'mail' => $test_user->mail, 'timezone' => 'America/Los_Angeles');
    $this->drupalPost('user/' . $test_user->uid . '/edit', $edit, t('Save'));

    // Disable session saving as we are about to modify the global $user.
    drupal_save_session(FALSE);
    // Save the original user and language and then replace it with the test user and language.
    $real_user = $user;
    $user = user_load($test_user->uid, TRUE);
    $real_language = $language_interface->langcode;
    $language_interface->langcode = $user->preferred_langcode;
    // Simulate a Drupal bootstrap with the logged-in user.
    date_default_timezone_set(drupal_get_user_timezone());

    $this->assertIdentical(format_date($timestamp, 'custom', 'l, d-M-y H:i:s T', 'America/Los_Angeles', 'en'), 'Sunday, 25-Mar-07 17:00:00 PDT', t('Test a different language.'));
    $this->assertIdentical(format_date($timestamp, 'custom', 'l, d-M-y H:i:s T', 'Europe/London'), 'Monday, 26-Mar-07 01:00:00 BST', t('Test a different time zone.'));
    $this->assertIdentical(format_date($timestamp, 'custom', 'l, d-M-y H:i:s T'), 'domingo, 25-Mar-07 17:00:00 PDT', t('Test custom date format.'));
    $this->assertIdentical(format_date($timestamp, 'long'), 'domingo, 25. marzo 2007 - 17:00', t('Test long date format.'));
    $this->assertIdentical(format_date($timestamp, 'medium'), '25. marzo 2007 - 17:00', t('Test medium date format.'));
    $this->assertIdentical(format_date($timestamp, 'short'), '2007 Mar 25 - 5:00pm', t('Test short date format.'));
    $this->assertIdentical(format_date($timestamp), '25. marzo 2007 - 17:00', t('Test default date format.'));
    // Test HTML time element formats.
    $this->assertIdentical(format_date($timestamp, 'html_datetime'), '2007-03-25T17:00:00-0700', t('Test html_datetime date format.'));
    $this->assertIdentical(format_date($timestamp, 'html_date'), '2007-03-25', t('Test html_date date format.'));
    $this->assertIdentical(format_date($timestamp, 'html_time'), '17:00:00', t('Test html_time date format.'));
    $this->assertIdentical(format_date($timestamp, 'html_yearless_date'), '03-25', t('Test html_yearless_date date format.'));
    $this->assertIdentical(format_date($timestamp, 'html_week'), '2007-W12', t('Test html_week date format.'));
    $this->assertIdentical(format_date($timestamp, 'html_month'), '2007-03', t('Test html_month date format.'));
    $this->assertIdentical(format_date($timestamp, 'html_year'), '2007', t('Test html_year date format.'));

    // Restore the original user and language, and enable session saving.
    $user = $real_user;
    $language_interface->langcode = $real_language;
    // Restore default time zone.
    date_default_timezone_set(drupal_get_user_timezone());
    drupal_save_session(TRUE);
  }
}
