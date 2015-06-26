<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Datetime\DrupalDateTimeTest.
 */

namespace Drupal\system\Tests\Datetime;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\simpletest\WebTestBase;
use Drupal\user\Entity\User;

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
    $date_string = '2007-01-31 21:00:00';

    // Make sure no site timezone has been set.
    $this->config('system.date')
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
    $this->config('system.date')->set('timezone.default', 'Europe/Warsaw')->save();

    // Create a date object with an unspecified timezone, which should
    // end up using the site timezone.
    $date = new DrupalDateTime($date_string);
    $timezone = $date->getTimezone()->getName();
    $this->assertTrue($timezone == 'Europe/Warsaw', 'DrupalDateTime uses the site timezone if provided.');

    // Create user.
    $this->config('system.date')->set('timezone.user.configurable', 1)->save();
    $test_user = $this->drupalCreateUser(array());
    $this->drupalLogin($test_user);

    // Set up the user with a different timezone than the site.
    $edit = array('mail' => $test_user->getEmail(), 'timezone' => 'Asia/Manila');
    $this->drupalPostForm('user/' . $test_user->id() . '/edit', $edit, t('Save'));

    // Reload the user and reset the timezone in AccountProxy::setAccount().
    \Drupal::entityManager()->getStorage('user')->resetCache();
    $this->container->get('current_user')->setAccount(User::load($test_user->id()));

    // Create a date object with an unspecified timezone, which should
    // end up using the user timezone.
    $date = new DrupalDateTime($date_string);
    $timezone = $date->getTimezone()->getName();
    $this->assertTrue($timezone == 'Asia/Manila', 'DrupalDateTime uses the user timezone, if configurable timezones are used and it is set.');
  }

  /**
   * Tests the ability to override the time zone in the format method.
   */
  function testTimezoneFormat() {
    // Create a date in UTC
    $date = DrupalDateTime::createFromTimestamp(87654321, 'UTC');

    // Verify that the date format method displays the default time zone.
    $this->assertEqual($date->format('Y/m/d H:i:s e'), '1972/10/11 12:25:21 UTC', 'Date has default UTC time zone and correct date/time.');

    // Verify that the format method can override the time zone.
    $this->assertEqual($date->format('Y/m/d H:i:s e', array('timezone' => 'America/New_York')), '1972/10/11 08:25:21 America/New_York', 'Date displayed overidden time zone and correct date/time');

    // Verify that the date format method still displays the default time zone
    // for the date object.
    $this->assertEqual($date->format('Y/m/d H:i:s e'), '1972/10/11 12:25:21 UTC', 'Date still has default UTC time zone and correct date/time');
  }

}
