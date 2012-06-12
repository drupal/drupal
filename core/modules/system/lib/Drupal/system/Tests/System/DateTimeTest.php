<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\DateTimeTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Tests generic date and time handling capabilities of Drupal.
 */
class DateTimeTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Date and time',
      'description' => 'Configure date and time settings. Test date formatting and time zone handling, including daylight saving time.',
      'group' => 'System',
    );
  }

  function setUp() {
    parent::setUp(array('language'));

    // Create admin user and log in admin user.
    $this->admin_user = $this->drupalCreateUser(array('administer site configuration'));
    $this->drupalLogin($this->admin_user);
  }


  /**
   * Test time zones and DST handling.
   */
  function testTimeZoneHandling() {
    // Setup date/time settings for Honolulu time.
    variable_set('date_default_timezone', 'Pacific/Honolulu');
    variable_set('configurable_timezones', 0);
    variable_set('date_format_medium', 'Y-m-d H:i:s O');

    // Create some nodes with different authored-on dates.
    $date1 = '2007-01-31 21:00:00 -1000';
    $date2 = '2007-07-31 21:00:00 -1000';
    $node1 = $this->drupalCreateNode(array('created' => strtotime($date1), 'type' => 'article'));
    $node2 = $this->drupalCreateNode(array('created' => strtotime($date2), 'type' => 'article'));

    // Confirm date format and time zone.
    $this->drupalGet("node/$node1->nid");
    $this->assertText('2007-01-31 21:00:00 -1000', t('Date should be identical, with GMT offset of -10 hours.'));
    $this->drupalGet("node/$node2->nid");
    $this->assertText('2007-07-31 21:00:00 -1000', t('Date should be identical, with GMT offset of -10 hours.'));

    // Set time zone to Los Angeles time.
    variable_set('date_default_timezone', 'America/Los_Angeles');

    // Confirm date format and time zone.
    $this->drupalGet("node/$node1->nid");
    $this->assertText('2007-01-31 23:00:00 -0800', t('Date should be two hours ahead, with GMT offset of -8 hours.'));
    $this->drupalGet("node/$node2->nid");
    $this->assertText('2007-08-01 00:00:00 -0700', t('Date should be three hours ahead, with GMT offset of -7 hours.'));
  }

  /**
   * Test date type configuration.
   */
  function testDateTypeConfiguration() {
    // Confirm system date types appear.
    $this->drupalGet('admin/config/regional/date-time');
    $this->assertText(t('Medium'), 'System date types appear in date type list.');
    $this->assertNoRaw('href="/admin/config/regional/date-time/types/medium/delete"', 'No delete link appear for system date types.');

    // Add custom date type.
    $this->clickLink(t('Add date type'));
    $date_type = strtolower($this->randomName(8));
    $machine_name = 'machine_' . $date_type;
    $date_format = 'd.m.Y - H:i';
    $edit = array(
      'date_type' => $date_type,
      'machine_name' => $machine_name,
      'date_format' => $date_format,
    );
    $this->drupalPost('admin/config/regional/date-time/types/add', $edit, t('Add date type'));
    $this->assertEqual($this->getUrl(), url('admin/config/regional/date-time', array('absolute' => TRUE)), t('Correct page redirection.'));
    $this->assertText(t('New date type added successfully.'), 'Date type added confirmation message appears.');
    $this->assertText($date_type, 'Custom date type appears in the date type list.');
    $this->assertText(t('delete'), 'Delete link for custom date type appears.');

    // Delete custom date type.
    $this->clickLink(t('delete'));
    $this->drupalPost('admin/config/regional/date-time/types/' . $machine_name . '/delete', array(), t('Remove'));
    $this->assertEqual($this->getUrl(), url('admin/config/regional/date-time', array('absolute' => TRUE)), t('Correct page redirection.'));
    $this->assertText(t('Removed date type ' . $date_type), 'Custom date type removed.');
  }

  /**
   * Test date format configuration.
   */
  function testDateFormatConfiguration() {
    // Confirm 'no custom date formats available' message appears.
    $this->drupalGet('admin/config/regional/date-time/formats');
    $this->assertText(t('No custom date formats available.'), 'No custom date formats message appears.');

    // Add custom date format.
    $this->clickLink(t('Add format'));
    $edit = array(
      'date_format' => 'Y',
    );
    $this->drupalPost('admin/config/regional/date-time/formats/add', $edit, t('Add format'));
    $this->assertEqual($this->getUrl(), url('admin/config/regional/date-time/formats', array('absolute' => TRUE)), t('Correct page redirection.'));
    $this->assertNoText(t('No custom date formats available.'), 'No custom date formats message does not appear.');
    $this->assertText(t('Custom date format added.'), 'Custom date format added.');

    // Ensure custom date format appears in date type configuration options.
    $this->drupalGet('admin/config/regional/date-time');
    $this->assertRaw('<option value="Y">', 'Custom date format appears in options.');

    // Edit custom date format.
    $this->drupalGet('admin/config/regional/date-time/formats');
    $this->clickLink(t('edit'));
    $edit = array(
      'date_format' => 'Y m',
    );
    $this->drupalPost($this->getUrl(), $edit, t('Save format'));
    $this->assertEqual($this->getUrl(), url('admin/config/regional/date-time/formats', array('absolute' => TRUE)), t('Correct page redirection.'));
    $this->assertText(t('Custom date format updated.'), 'Custom date format successfully updated.');

    // Delete custom date format.
    $this->clickLink(t('delete'));
    $this->drupalPost($this->getUrl(), array(), t('Remove'));
    $this->assertEqual($this->getUrl(), url('admin/config/regional/date-time/formats', array('absolute' => TRUE)), t('Correct page redirection.'));
    $this->assertText(t('Removed date format'), 'Custom date format removed successfully.');
  }

  /**
   * Test if the date formats are stored properly.
   */
  function testDateFormatStorage() {
    $date_format = array(
      'type' => 'short',
      'format' => 'dmYHis',
      'locked' => 0,
      'is_new' => 1,
    );
    system_date_format_save($date_format);

    $format = db_select('date_formats', 'df')
      ->fields('df', array('format'))
      ->condition('type', 'short')
      ->condition('format', 'dmYHis')
      ->execute()
      ->fetchField();
    $this->verbose($format);
    $this->assertEqual('dmYHis', $format, 'Unlocalized date format resides in general table.');

    $format = db_select('date_format_locale', 'dfl')
      ->fields('dfl', array('format'))
      ->condition('type', 'short')
      ->condition('format', 'dmYHis')
      ->execute()
      ->fetchField();
    $this->assertFalse($format, 'Unlocalized date format resides not in localized table.');

    // Enable German language
    $language = (object) array(
      'langcode' => 'de',
      'default' => TRUE,
    );
    language_save($language);

    $date_format = array(
      'type' => 'short',
      'format' => 'YMDHis',
      'locales' => array('de', 'tr'),
      'locked' => 0,
      'is_new' => 1,
    );
    system_date_format_save($date_format);

    $format = db_select('date_format_locale', 'dfl')
      ->fields('dfl', array('format'))
      ->condition('type', 'short')
      ->condition('format', 'YMDHis')
      ->condition('language', 'de')
      ->execute()
      ->fetchField();
    $this->assertEqual('YMDHis', $format, 'Localized date format resides in localized table.');

    $format = db_select('date_formats', 'df')
      ->fields('df', array('format'))
      ->condition('type', 'short')
      ->condition('format', 'YMDHis')
      ->execute()
      ->fetchField();
    $this->assertEqual('YMDHis', $format, 'Localized date format resides in general table too.');

    $format = db_select('date_format_locale', 'dfl')
      ->fields('dfl', array('format'))
      ->condition('type', 'short')
      ->condition('format', 'YMDHis')
      ->condition('language', 'tr')
      ->execute()
      ->fetchColumn();
    $this->assertFalse($format, 'Localized date format for disabled language is ignored.');
  }
}
