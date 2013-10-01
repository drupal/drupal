<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Datetime\DrupalDateTimeIntlTest.
 */

namespace Drupal\system\Tests\Datetime;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests use of PHP's internationalization extension to format dates.
 */
class DrupalDateTimeIntlTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system');

  public static function getInfo() {
    return array(
      'name' => 'DrupalDateTimeIntl',
      'description' => 'Test DrupalDateTime Intl functionality.',
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
  function testDrupalDateTimeIntl() {
    $input_value = '2013-09-27 17:40:41';
    $timezone = drupal_get_user_timezone();
    $format = 'yyyy-MM-dd HH:mm:ss';
    $format_settings = array(
      'country' => 'UA',
      'langcode' => 'ru',
      'format_string_type' => DrupalDateTime::INTL
    );
    $date = DrupalDateTime::createFromFormat($format, $input_value, $timezone, $format_settings);
    $output_value = $date->format($format, $format_settings);
    $this->assertIdentical($input_value, $output_value);
  }

}
