<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Datetime\DateTimePlusTest.
 */

namespace Drupal\system\Tests\Datetime;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Tests DrupalDateTime functionality.
 *
 * @group Datetime
 */
class DrupalDateTimeTest extends WebTestBase {

  /**
   * Set up required modules.
   */
  public static $modules = array();

  /**
   * Test setup.
   */
  protected function setUp() {
    parent::setUp();

  }

  /**
   * Test that the AJAX Timezone Callback can deal with various formats.
   */
  public function testSystemTimezone() {
    $options = array(
      'query' => array(
        'date' => 'Tue+Sep+17+2013+21%3A35%3A31+GMT%2B0100+(BST)#',
      )
    );
    // Query the AJAX Timezone Callback with a long-format date.
    $response = $this->drupalGet('system/timezone/BST/3600/1', $options);
    $this->assertEqual($response, '"Europe\/London"', 'Timezone AJAX callback successfully identifies and responds to a long-format date.');
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
    \Drupal::config('system.date')
      ->set('timezone.user.configurable', 0)
      ->set('timezone.default', NULL)
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
    \Drupal::config('system.date')->set('timezone.default', 'Europe/Warsaw')->save();

    // Create a date object with an unspecified timezone, which should
    // end up using the site timezone.
    $date = new DrupalDateTime($date_string);
    $timezone = $date->getTimezone()->getName();
    $this->assertTrue($timezone == 'Europe/Warsaw', 'DrupalDateTime uses the site timezone if provided.');

    // Create user.
    \Drupal::config('system.date')->set('timezone.user.configurable', 1)->save();
    $test_user = $this->drupalCreateUser(array());
    $this->drupalLogin($test_user);

    // Set up the user with a different timezone than the site.
    $edit = array('mail' => $test_user->getEmail(), 'timezone' => 'Asia/Manila');
    $this->drupalPostForm('user/' . $test_user->id() . '/edit', $edit, t('Save'));

    // Disable session saving as we are about to modify the global $user.
    \Drupal::service('session_manager')->disable();
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
    \Drupal::service('session_manager')->enable();


  }
}
