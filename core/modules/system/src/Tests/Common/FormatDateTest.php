<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Common\FormatDateTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the format_date() function.
 *
 * @group Common
 */
class FormatDateTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language');

  /**
   * Arbitrary langcode for a custom language.
   */
  const LANGCODE = 'xx';

  protected function setUp() {
    parent::setUp('language');

    $this->config('system.date')
      ->set('timezone.user.configurable', 1)
      ->save();
    $formats = $this->container->get('entity.manager')
      ->getStorage('date_format')
      ->loadMultiple(array('long', 'medium', 'short'));
    $formats['long']->setPattern('l, j. F Y - G:i')->save();
    $formats['medium']->setPattern('j. F Y - G:i')->save();
    $formats['short']->setPattern('Y M j - g:ia')->save();
    $this->refreshVariables();

    $this->settingsSet('locale_custom_strings_' . self::LANGCODE, array(
      '' => array('Sunday' => 'domingo'),
      'Long month name' => array('March' => 'marzo'),
    ));

    ConfigurableLanguage::createFromLangcode(static::LANGCODE)->save();
    $this->resetAll();
  }

  /**
   * Tests admin-defined formats in format_date().
   */
  function testAdminDefinedFormatDate() {
    // Create and log in an admin user.
    $this->drupalLogin($this->drupalCreateUser(array('administer site configuration')));

    // Add new date format.
    $edit = array(
      'id' => 'example_style',
      'label' => 'Example Style',
      'date_format_pattern' => 'j M y',
    );
    $this->drupalPostForm('admin/config/regional/date-time/formats/add', $edit, t('Add format'));

    // Add a second date format with a different case than the first.
    $edit = array(
      'id' => 'example_style_uppercase',
      'label' => 'Example Style Uppercase',
      'date_format_pattern' => 'j M Y',
    );
    $this->drupalPostForm('admin/config/regional/date-time/formats/add', $edit, t('Add format'));
    $this->assertText(t('Custom date format added.'));

    $timestamp = strtotime('2007-03-10T00:00:00+00:00');
    $this->assertIdentical(format_date($timestamp, 'example_style', '', 'America/Los_Angeles'), '9 Mar 07');
    $this->assertIdentical(format_date($timestamp, 'example_style_uppercase', '', 'America/Los_Angeles'), '9 Mar 2007');
    $this->assertIdentical(format_date($timestamp, 'undefined_style'), format_date($timestamp, 'fallback'), 'Test format_date() defaulting to `fallback` when $type not found.');
  }

  /**
   * Tests the format_date() function.
   */
  function testFormatDate() {
    $timestamp = strtotime('2007-03-26T00:00:00+00:00');
    $this->assertIdentical(format_date($timestamp, 'custom', 'l, d-M-y H:i:s T', 'America/Los_Angeles', 'en'), 'Sunday, 25-Mar-07 17:00:00 PDT', 'Test all parameters.');
    $this->assertIdentical(format_date($timestamp, 'custom', 'l, d-M-y H:i:s T', 'America/Los_Angeles', self::LANGCODE), 'domingo, 25-Mar-07 17:00:00 PDT', 'Test translated format.');
    $this->assertIdentical(format_date($timestamp, 'custom', '\\l, d-M-y H:i:s T', 'America/Los_Angeles', self::LANGCODE), 'l, 25-Mar-07 17:00:00 PDT', 'Test an escaped format string.');
    $this->assertIdentical(format_date($timestamp, 'custom', '\\\\l, d-M-y H:i:s T', 'America/Los_Angeles', self::LANGCODE), '\\domingo, 25-Mar-07 17:00:00 PDT', 'Test format containing backslash character.');
    $this->assertIdentical(format_date($timestamp, 'custom', '\\\\\\l, d-M-y H:i:s T', 'America/Los_Angeles', self::LANGCODE), '\\l, 25-Mar-07 17:00:00 PDT', 'Test format containing backslash followed by escaped format string.');
    $this->assertIdentical(format_date($timestamp, 'custom', 'l, d-M-y H:i:s T', 'Europe/London', 'en'), 'Monday, 26-Mar-07 01:00:00 BST', 'Test a different time zone.');

    // Change the default language and timezone.
    $this->config('system.site')->set('default_langcode', static::LANGCODE)->save();
    date_default_timezone_set('America/Los_Angeles');

    // Reset the language manager so new negotiations attempts will fall back on
    // on the new language.
    $this->container->get('language_manager')->reset();

    $this->assertIdentical(format_date($timestamp, 'custom', 'l, d-M-y H:i:s T', 'America/Los_Angeles', 'en'), 'Sunday, 25-Mar-07 17:00:00 PDT', 'Test a different language.');
    $this->assertIdentical(format_date($timestamp, 'custom', 'l, d-M-y H:i:s T', 'Europe/London'), 'Monday, 26-Mar-07 01:00:00 BST', 'Test a different time zone.');
    $this->assertIdentical(format_date($timestamp, 'custom', 'l, d-M-y H:i:s T'), 'domingo, 25-Mar-07 17:00:00 PDT', 'Test custom date format.');
    $this->assertIdentical(format_date($timestamp, 'long'), 'domingo, 25. marzo 2007 - 17:00', 'Test long date format.');
    $this->assertIdentical(format_date($timestamp, 'medium'), '25. marzo 2007 - 17:00', 'Test medium date format.');
    $this->assertIdentical(format_date($timestamp, 'short'), '2007 Mar 25 - 5:00pm', 'Test short date format.');
    $this->assertIdentical(format_date($timestamp), '25. marzo 2007 - 17:00', 'Test default date format.');
    // Test HTML time element formats.
    $this->assertIdentical(format_date($timestamp, 'html_datetime'), '2007-03-25T17:00:00-0700', 'Test html_datetime date format.');
    $this->assertIdentical(format_date($timestamp, 'html_date'), '2007-03-25', 'Test html_date date format.');
    $this->assertIdentical(format_date($timestamp, 'html_time'), '17:00:00', 'Test html_time date format.');
    $this->assertIdentical(format_date($timestamp, 'html_yearless_date'), '03-25', 'Test html_yearless_date date format.');
    $this->assertIdentical(format_date($timestamp, 'html_week'), '2007-W12', 'Test html_week date format.');
    $this->assertIdentical(format_date($timestamp, 'html_month'), '2007-03', 'Test html_month date format.');
    $this->assertIdentical(format_date($timestamp, 'html_year'), '2007', 'Test html_year date format.');
  }
}
