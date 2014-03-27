<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Datetime\DateTimePlusIntlTest.
 */

namespace Drupal\system\Tests\Datetime;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests use of PHP's internationalization extension to format dates.
 */
class DateTimePlusIntlTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system');

  public static function getInfo() {
    return array(
      'name' => 'DateTimePlusIntl',
      'description' => 'Test DateTimePlus PECL Intl functionality.',
      'group' => 'Datetime',
    );
  }

  public function setUp() {
    parent::setUp();
    // Install default config for system.
    $this->installConfig(array('system'));
  }

  /**
   * Ensures that PHP's Intl extension is installed.
   *
   * @return array
   *   Array of errors containing a list of unmet requirements.
   */
  function checkRequirements() {
    if (!class_exists('IntlDateFormatter')) {
      return array(
        'PHP\'s Intl extension needs to be installed and enabled.',
      );
    }
    return parent::checkRequirements();
  }

  /**
   * Tests that PHP and Intl default formats are equivalent.
   */
  function testDateTimestampIntl() {

    // Create date object from a unix timestamp and display it in local time.
    $input = '2007-01-31 21:00:00';
    $timezone = 'UTC';
    $intl_settings = array(
      'format_string_type' => DateTimePlus::INTL,
      'country' => 'US',
      'langcode' => 'en',
    );
    $php_settings = array(
      'country' => NULL,
      'langcode' => 'en',
    );

    $intl_date = new DateTimePlus($input, $timezone, $intl_settings);
    $php_date = new DateTimePlus($input, $timezone, $php_settings);

    $this->assertTrue($intl_date->canUseIntl(), 'DateTimePlus object can use intl when provided with country and langcode settings.');
    $this->assertFalse($php_date->canUseIntl(), 'DateTimePlus object will fallback to use PHP when not provided with country setting.');

    $default_formats = $this->container->get('entity.manager')
      ->getStorage('date_format')
      ->loadMultiple();

    foreach ($default_formats as $format) {
      $php_format = $php_date->format($format->getPattern(DrupalDateTime::PHP), $php_settings);
      $intl_format = $intl_date->format($format->getPattern(DrupalDateTime::INTL), $intl_settings);
      $this->assertIdentical($intl_format, $php_format);
    }
  }

}
