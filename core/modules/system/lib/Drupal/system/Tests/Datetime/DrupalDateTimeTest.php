<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Datetime\DateTimePlusTest.
 */

namespace Drupal\system\Tests\Datetime;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Datetime\DrupalDateTime;

class DrupalDateTimeTest extends WebTestBase {

  /**
   * Test information.
   */
  public static function getInfo() {
    return array(
      'name' => 'DrupalDateTime',
      'description' => 'Test DrupalDateTime functionality.',
      'group' => 'Datetime',
    );
  }

  /**
   * Set up required modules.
   */
  public static $modules = array();

  /**
   * Test setup.
   */
  public function setUp() {
    parent::setUp();

  }

  /**
   * Test that DrupalDateTime can detect the right timezone to use.
   * Test with a variety of less commonly used timezone names to
   * help ensure that the system timezone will be different than the
   * stated timezones.
   */
  public function testDateTimezone() {
    global $user;

    $date_string = '2007-01-31 21:00:00';

    // Make sure no site timezone has been set.
    config('system.timezone')
      ->set('user.configurable', 0)
      ->set('default', NULL)
      ->save();

    // Detect the system timezone.
    $system_timezone = date_default_timezone_get();

    // Create a date object with an unspecified timezone, which should
    // end up using the system timezone.
    $date = new DrupalDateTime($date_string);
    $timezone = $date->getTimezone()->getName();
    $this->assertTrue($timezone == $system_timezone, 'DrupalDateTime uses the system timezone when there is no site timezone.');

    // Create a date object with a specified timezone.
    $date = new DrupalDateTime($date_string, 'America/Yellowknife');
    $timezone = $date->getTimezone()->getName();
    $this->assertTrue($timezone == 'America/Yellowknife', 'DrupalDateTime uses the specified timezone if provided.');

    // Set a site timezone.
    config('system.timezone')->set('default', 'Europe/Warsaw')->save();

    // Create a date object with an unspecified timezone, which should
    // end up using the site timezone.
    $date = new DrupalDateTime($date_string);
    $timezone = $date->getTimezone()->getName();
    $this->assertTrue($timezone == 'Europe/Warsaw', 'DrupalDateTime uses the site timezone if provided.');

    // Create user.
    config('system.timezone')->set('user.configurable', 1)->save();
    $test_user = $this->drupalCreateUser(array());
    $this->drupalLogin($test_user);

    // Set up the user with a different timezone than the site.
    $edit = array('mail' => $test_user->getEmail(), 'timezone' => 'Asia/Manila');
    $this->drupalPost('user/' . $test_user->id() . '/edit', $edit, t('Save'));

    // Disable session saving as we are about to modify the global $user.
    drupal_save_session(FALSE);
    // Save the original user and then replace it with the test user.
    $real_user = $user;
    $user = user_load($test_user->id(), TRUE);

    // Simulate a Drupal bootstrap with the logged-in user.
    date_default_timezone_set(drupal_get_user_timezone());

    // Create a date object with an unspecified timezone, which should
    // end up using the user timezone.

    $date = new DrupalDateTime($date_string);
    $timezone = $date->getTimezone()->getName();
    $this->assertTrue($timezone == 'Asia/Manila', 'DrupalDateTime uses the user timezone, if configurable timezones are used and it is set.');

    // Restore the original user, and enable session saving.
    $user = $real_user;
    // Restore default time zone.
    date_default_timezone_set(drupal_get_user_timezone());
    drupal_save_session(TRUE);


  }
}
