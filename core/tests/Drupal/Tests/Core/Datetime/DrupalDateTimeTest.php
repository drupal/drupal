<?php

namespace Drupal\Tests\Core\Datetime;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManager;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\Core\Datetime\DrupalDateTime
 * @group Datetime
 */
class DrupalDateTimeTest extends UnitTestCase {

  /**
   * Tests date diffs.
   *
   * @param mixed $input1
   *   A DrupalDateTime object.
   * @param mixed $input2
   *   Date argument for DrupalDateTime::diff method.
   * @param bool $absolute
   *   Absolute flag for DrupalDateTime::diff method.
   * @param \DateInterval $expected
   *   The expected result of the DrupalDateTime::diff operation.
   *
   * @dataProvider providerTestDateDiff
   */
  public function testDateDiff($input1, $input2, $absolute, \DateInterval $expected) {
    $interval = $input1->diff($input2, $absolute);
    $this->assertEquals($interval, $expected);
  }

  /**
   * Tests date diff exception caused by invalid input.
   *
   * @param mixed $input1
   *   A DateTimePlus object.
   * @param mixed $input2
   *   Date argument for DateTimePlus::diff method.
   * @param bool $absolute
   *   Absolute flag for DateTimePlus::diff method.
   *
   * @dataProvider providerTestInvalidDateDiff
   */
  public function testInvalidDateDiff($input1, $input2, $absolute) {
    $this->expectException(\BadMethodCallException::class);
    $this->expectExceptionMessage('Method Drupal\Component\Datetime\DateTimePlus::diff expects parameter 1 to be a \DateTime or \Drupal\Component\Datetime\DateTimePlus object');
    $interval = $input1->diff($input2, $absolute);
  }

  /**
   * Provides data for date tests.
   *
   * @return array
   *   An array of arrays, each containing the input parameters for
   *   DrupalDateTimeTest::testDateDiff().
   *
   * @see DrupalDateTimeTest::testDateDiff()
   */
  public function providerTestDateDiff() {

    $settings = ['langcode' => 'en'];

    $utc_tz = new \DateTimeZone('UTC');

    $empty_interval = new \DateInterval('PT0S');

    $positive_19_hours = new \DateInterval('PT19H');

    $positive_18_hours = new \DateInterval('PT18H');

    $positive_1_hour = new \DateInterval('PT1H');

    $negative_1_hour = new \DateInterval('PT1H');
    $negative_1_hour->invert = 1;

    return [

      // There should be a 19 hour time interval between
      // new years in Sydney and new years in LA in year 2000.
      [
        'input2' => DrupalDateTime::createFromFormat('Y-m-d H:i:s', '2000-01-01 00:00:00', new \DateTimeZone('Australia/Sydney'), $settings),
        'input1' => DrupalDateTime::createFromFormat('Y-m-d H:i:s', '2000-01-01 00:00:00', new \DateTimeZone('America/Los_Angeles'), $settings),
        'absolute' => FALSE,
        'expected' => $positive_19_hours,
      ],
      // In 1970 Sydney did not observe daylight savings time
      // So there is only an 18 hour time interval.
      [
        'input2' => DrupalDateTime::createFromFormat('Y-m-d H:i:s', '1970-01-01 00:00:00', new \DateTimeZone('Australia/Sydney'), $settings),
        'input1' => DrupalDateTime::createFromFormat('Y-m-d H:i:s', '1970-01-01 00:00:00', new \DateTimeZone('America/Los_Angeles'), $settings),
        'absolute' => FALSE,
        'expected' => $positive_18_hours,
      ],
      [
        'input1' => DrupalDateTime::createFromFormat('U', 3600, new \DateTimeZone('America/Los_Angeles'), $settings),
        'input2' => DrupalDateTime::createFromFormat('U', 0, $utc_tz, $settings),
        'absolute' => FALSE,
        'expected' => $negative_1_hour,
      ],
      [
        'input1' => DrupalDateTime::createFromFormat('U', 3600, $utc_tz, $settings),
        'input2' => DrupalDateTime::createFromFormat('U', 0, $utc_tz, $settings),
        'absolute' => FALSE,
        'expected' => $negative_1_hour,
      ],
      [
        'input1' => DrupalDateTime::createFromFormat('U', 3600, $utc_tz, $settings),
        'input2' => \DateTime::createFromFormat('U', 0),
        'absolute' => FALSE,
        'expected' => $negative_1_hour,
      ],
      [
        'input1' => DrupalDateTime::createFromFormat('U', 3600, $utc_tz, $settings),
        'input2' => DrupalDateTime::createFromFormat('U', 0, $utc_tz, $settings),
        'absolute' => TRUE,
        'expected' => $positive_1_hour,
      ],
      [
        'input1' => DrupalDateTime::createFromFormat('U', 3600, $utc_tz, $settings),
        'input2' => \DateTime::createFromFormat('U', 0),
        'absolute' => TRUE,
        'expected' => $positive_1_hour,
      ],
      [
        'input1' => DrupalDateTime::createFromFormat('U', 0, $utc_tz, $settings),
        'input2' => DrupalDateTime::createFromFormat('U', 0, $utc_tz, $settings),
        'absolute' => FALSE,
        'expected' => $empty_interval,
      ],
    ];
  }

  /**
   * Provides data for date tests.
   *
   * @return array
   *   An array of arrays, each containing the input parameters for
   *   DateTimePlusTest::testInvalidDateDiff().
   *
   * @see DateTimePlusTest::testInvalidDateDiff()
   */
  public function providerTestInvalidDateDiff() {
    $settings = ['langcode' => 'en'];
    $utc_tz = new \DateTimeZone('UTC');
    return [
      [
        'input1' => DrupalDateTime::createFromFormat('U', 3600, $utc_tz, $settings),
        'input2' => '1970-01-01 00:00:00',
        'absolute' => FALSE,
      ],
      [
        'input1' => DrupalDateTime::createFromFormat('U', 3600, $utc_tz, $settings),
        'input2' => NULL,
        'absolute' => FALSE,
      ],
    ];
  }

  /**
   * Tests setting the default time for date-only objects.
   */
  public function testDefaultDateTime() {
    $utc = new \DateTimeZone('UTC');

    $date = DrupalDateTime::createFromFormat('Y-m-d H:i:s', '2017-05-23 22:58:00', $utc, ['langcode' => 'en']);
    $this->assertEquals('22:58:00', $date->format('H:i:s'));
    $date->setDefaultDateTime();
    $this->assertEquals('12:00:00', $date->format('H:i:s'));
  }

  /**
   * Tests that object methods are chainable.
   *
   * @covers ::__call
   */
  public function testChainable() {
    $tz = new \DateTimeZone(date_default_timezone_get());
    $date = new DrupalDateTime('now', $tz, ['langcode' => 'en']);

    $date->setTimestamp(12345678);
    $rendered = $date->render();
    $this->assertEquals('1970-05-24 07:21:18 Australia/Sydney', $rendered);

    $date->setTimestamp(23456789);
    $rendered = $date->setTimezone(new \DateTimeZone('America/New_York'))->render();
    $this->assertEquals('1970-09-29 07:46:29 America/New_York', $rendered);
  }

  /**
   * Tests that non-chainable methods work.
   *
   * @covers ::__call
   */
  public function testChainableNonChainable() {
    $tz = new \DateTimeZone(date_default_timezone_get());
    $datetime1 = new DrupalDateTime('2009-10-11 12:00:00', $tz, ['langcode' => 'en']);
    $datetime2 = new DrupalDateTime('2009-10-13 12:00:00', $tz, ['langcode' => 'en']);
    $interval = $datetime1->diff($datetime2);
    $this->assertInstanceOf(\DateInterval::class, $interval);
    $this->assertEquals('+2 days', $interval->format('%R%a days'));
  }

  /**
   * Tests that chained calls to non-existent functions throw an exception.
   *
   * @covers ::__call
   */
  public function testChainableNonCallable() {
    $this->expectException(\BadMethodCallException::class);
    $this->expectExceptionMessage('Call to undefined method Drupal\Core\Datetime\DrupalDateTime::nonexistent()');
    $tz = new \DateTimeZone(date_default_timezone_get());
    $date = new DrupalDateTime('now', $tz, ['langcode' => 'en']);
    $date->setTimezone(new \DateTimeZone('America/New_York'))->nonexistent();
  }

  /**
   * @covers ::getPhpDateTime
   */
  public function testGetPhpDateTime() {
    $new_york = new \DateTimeZone('America/New_York');
    $berlin = new \DateTimeZone('Europe/Berlin');

    // Test retrieving a cloned copy of the wrapped \DateTime object, and that
    // altering it does not change the DrupalDateTime object.
    $drupaldatetime = DrupalDateTime::createFromFormat('Y-m-d H:i:s', '2017-07-13 22:40:00', $new_york, ['langcode' => 'en']);
    $this->assertEquals(1500000000, $drupaldatetime->getTimestamp());
    $this->assertEquals('America/New_York', $drupaldatetime->getTimezone()->getName());

    $datetime = $drupaldatetime->getPhpDateTime();
    $this->assertInstanceOf('DateTime', $datetime);
    $this->assertEquals(1500000000, $datetime->getTimestamp());
    $this->assertEquals('America/New_York', $datetime->getTimezone()->getName());

    $datetime->setTimestamp(1400000000)->setTimezone($berlin);
    $this->assertEquals(1400000000, $datetime->getTimestamp());
    $this->assertEquals('Europe/Berlin', $datetime->getTimezone()->getName());
    $this->assertEquals(1500000000, $drupaldatetime->getTimestamp());
    $this->assertEquals('America/New_York', $drupaldatetime->getTimezone()->getName());
  }

  /**
   * Tests that an RFC2822 formatted date always returns an English string.
   *
   * @see http://www.faqs.org/rfcs/rfc2822.html
   *
   * @covers ::format
   */
  public function testRfc2822DateFormat() {
    $language_manager = $this->createMock(LanguageManager::class);
    $language_manager->expects($this->any())
      ->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => $this->randomMachineName(2)]));
    $container = new ContainerBuilder();
    $container->set('language_manager', $language_manager);
    \Drupal::setContainer($container);

    $time = '2019-02-02T13:30';
    $timezone = new \DateTimeZone('Europe/Berlin');
    $langcodes = array_keys(LanguageManager::getStandardLanguageList());
    $langcodes[] = NULL;
    foreach ($langcodes as $langcode) {
      $datetime = new DrupalDateTime($time, $timezone, ['langcode' => $langcode]);
      // Check that RFC2822 format date is returned regardless of langcode.
      $this->assertEquals('Sat, 02 Feb 2019 13:30:00 +0100', $datetime->format('r'));
    }
  }

}
