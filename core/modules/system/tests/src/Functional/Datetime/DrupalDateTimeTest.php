<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Datetime;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Tests DrupalDateTime functionality.
 *
 * @group Datetime
 */
class DrupalDateTimeTest extends BrowserTestBase {

  /**
   * Set up required modules.
   *
   * @var string[]
   */
  protected static $modules = [];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test setup.
   */
  protected function setUp(): void {
    parent::setUp();

  }

  /**
   * Tests that DrupalDateTime can detect the right timezone to use.
   *
   * Test with a variety of less commonly used timezone names to
   * help ensure that the system timezone will be different than the
   * stated timezones.
   */
  public function testDateTimezone(): void {
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
    $this->assertSame($system_timezone, $timezone, 'DrupalDateTime uses the system timezone when there is no site timezone.');

    // Create a date object with a specified timezone.
    $date = new DrupalDateTime($date_string, 'America/Yellowknife');
    $timezone = $date->getTimezone()->getName();
    $this->assertSame('America/Yellowknife', $timezone, 'DrupalDateTime uses the specified timezone if provided.');

    // Set a site timezone.
    $this->config('system.date')->set('timezone.default', 'Europe/Warsaw')->save();

    // Create a date object with an unspecified timezone, which should
    // end up using the site timezone.
    $date = new DrupalDateTime($date_string);
    $timezone = $date->getTimezone()->getName();
    $this->assertSame('Europe/Warsaw', $timezone, 'DrupalDateTime uses the site timezone if provided.');

    // Create user.
    $this->config('system.date')->set('timezone.user.configurable', 1)->save();
    $test_user = $this->drupalCreateUser([]);
    $this->drupalLogin($test_user);

    // Set up the user with a different timezone than the site.
    $edit = ['mail' => $test_user->getEmail(), 'timezone' => 'Asia/Manila'];
    $this->drupalGet('user/' . $test_user->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Reload the user and reset the timezone in AccountProxy::setAccount().
    \Drupal::entityTypeManager()->getStorage('user')->resetCache();
    $this->container->get('current_user')->setAccount(User::load($test_user->id()));

    // Create a date object with an unspecified timezone, which should
    // end up using the user timezone.
    $date = new DrupalDateTime($date_string);
    $timezone = $date->getTimezone()->getName();
    $this->assertSame('Asia/Manila', $timezone, 'DrupalDateTime uses the user timezone, if configurable timezones are used and it is set.');
  }

  /**
   * Tests the ability to override the time zone in the format method.
   */
  public function testTimezoneFormat(): void {
    // Create a date in UTC
    $date = DrupalDateTime::createFromTimestamp(87654321, 'UTC');

    // Verify that the date format method displays the default time zone.
    $this->assertEquals('1972/10/11 12:25:21 UTC', $date->format('Y/m/d H:i:s e'), 'Date has default UTC time zone and correct date/time.');

    // Verify that the format method can override the time zone.
    $this->assertEquals('1972/10/11 08:25:21 America/New_York', $date->format('Y/m/d H:i:s e', ['timezone' => 'America/New_York']), 'Date displayed overridden time zone and correct date/time');

    // Verify that the date format method still displays the default time zone
    // for the date object.
    $this->assertEquals('1972/10/11 12:25:21 UTC', $date->format('Y/m/d H:i:s e'), 'Date still has default UTC time zone and correct date/time');
  }

}
