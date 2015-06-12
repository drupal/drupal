<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Datetime\DateTest.
 */

namespace Drupal\Tests\Core\Datetime;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\Core\Datetime\DateFormatter
 * @group Datetime
 */
class DateTest extends UnitTestCase {

  /**
   * The mocked entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The mocked language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * The mocked string translation.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $stringTranslation;

  /**
   * The mocked string translation.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $requestStack;

  /**
   * The mocked date formatter class.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The date formatter class where methods can be stubbed.
   *
   * @var \Drupal\Core\Datetime\DateFormatter|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $dateFormatterStub;

  protected function setUp() {
    parent::setUp();

    $entity_storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');

    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $this->entityManager->expects($this->any())->method('getStorage')->with('date_format')->willReturn($entity_storage);

    $this->languageManager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');
    $this->stringTranslation = $this->getMock('Drupal\Core\StringTranslation\TranslationInterface');
    $this->requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

    $config_factory = $this->getConfigFactoryStub(['system.date' => ['country' => ['default' => 'GB']]]);
    $container = new ContainerBuilder();
    $container->set('config.factory', $config_factory);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->dateFormatter = new DateFormatter($this->entityManager, $this->languageManager, $this->stringTranslation, $this->getConfigFactoryStub(), $this->requestStack);

    $this->dateFormatterStub = $this->getMockBuilder('\Drupal\Core\Datetime\DateFormatter')
      ->setConstructorArgs([$this->entityManager, $this->languageManager, $this->stringTranslation, $this->getConfigFactoryStub(), $this->requestStack])
      ->setMethods(['formatDiff'])
      ->getMock();
  }

  /**
   * Tests the formatInterval method.
   *
   * @dataProvider providerTestFormatInterval
   *
   * @covers ::formatInterval
   */
  public function testFormatInterval($interval, $granularity, $expected, $langcode = NULL) {
    // Mocks a simple formatPlural implementation.
    $this->stringTranslation->expects($this->any())
      ->method('formatPlural')
      ->with($this->anything(), $this->anything(), $this->anything(), array(), array('langcode' => $langcode))
      ->will($this->returnCallback(function($count, $one, $multiple) {
        return $count == 1 ? $one : str_replace('@count', $count, $multiple);
      }));

    // Check if the granularity is specified.
    if ($granularity) {
      $result = $this->dateFormatter->formatInterval($interval, $granularity, $langcode);
    }
    else {
      $result = $this->dateFormatter->formatInterval($interval);
    }

    $this->assertEquals($expected, $result);
  }

  /**
   * Provides some test data for the format interval test.
   */
  public function providerTestFormatInterval() {
    $data = array(
      // Checks for basic seconds.
      array(1, 1, '1 sec'),
      array(1, 2, '1 sec'),
      array(2, 1, '2 sec'),
      array(2, 2, '2 sec'),
      // Checks for minutes with seconds.
      array(61, 1, '1 min'),
      array(61, 2, '1 min 1 sec'),
      array(62, 2, '1 min 2 sec'),
      array(121, 1, '2 min'),
      array(121, 2, '2 min 1 sec'),
      // Check for hours with minutes and seconds.
      array(3601, 1, '1 hour'),
      array(3601, 2, '1 hour 1 sec'),
      // Check for higher units.
      array(86401, 1, '1 day'),
      array(604800, 1, '1 week'),
      array(2592000 * 2, 1, '2 months'),
      array(31536000 * 2, 1, '2 years'),
      // Check for a complicated one with months weeks and days.
      array(2592000 * 2 + 604800 * 3 + 86400 * 4, 3, '2 months 3 weeks 4 days'),
      // Check for the langcode.
      array(61, 1, '1 min', 'xxx-lolspeak'),
      // Check with an unspecified granularity.
      array(61, NULL, '1 min 1 sec'),
    );

    return $data;
  }

  /**
   * Tests the formatInterval method for 0 second.
   */
  public function testFormatIntervalZeroSecond() {
    $this->stringTranslation->expects($this->once())
      ->method('translate')
      ->with('0 sec', array(), array('langcode' => 'xxx-lolspeak'))
      ->will($this->returnValue('0 sec'));

    $result = $this->dateFormatter->formatInterval(0, 1, 'xxx-lolspeak');

    $this->assertEquals('0 sec', $result);
  }

  /**
   * Tests the getSampleDateFormats method.
   *
   * @covers \Drupal\Core\Datetime\DateFormatter::getSampleDateFormats
   */
  public function testGetSampleDateFormats() {
    $timestamp = strtotime('2015-03-22 14:23:00');
    $expected = $this->dateFormatter->getSampleDateFormats('en', $timestamp, 'Europe/London');

    // Removed characters related to timezone 'e' and 'T', as test does not have
    // timezone set.
    $date_characters = 'dDjlNSwzWFmMntLoYyaABgGhHisuIOPZcrU';
    $date_chars = str_split($date_characters);

    foreach ($date_chars as $val) {
      $this->assertEquals($expected[$val], date($val, $timestamp));
    }
  }

  /**
   * Tests the formatTimeDiffUntil method.
   *
   * @covers ::formatTimeDiffUntil
   */
  public function testFormatTimeDiffUntil() {
    $expected = '1 second';
    $request_time = $this->createTimestamp('2013-12-11 10:09:08');
    $timestamp = $this->createTimestamp('2013-12-11 10:09:09');
    $options = array();

    // Mocks the formatDiff function of the dateformatter object.
    $this->dateFormatterStub
      ->expects($this->any())
      ->method('formatDiff')
      ->with($timestamp, $request_time, $options)
      ->will($this->returnValue($expected));

    $request = Request::createFromGlobals();
    $request->server->set('REQUEST_TIME', $request_time);
    // Mocks a the request stack getting the current request.
    $this->requestStack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);

    $this->assertEquals($expected, $this->dateFormatterStub->formatTimeDiffSince($timestamp, $options));
  }

  /**
   * Tests the formatTimeDiffSince method.
   *
   * @covers ::formatTimeDiffSince
   */
  public function testFormatTimeDiffSince() {
    $expected = '1 second';
    $timestamp = $this->createTimestamp('2013-12-11 10:09:07');
    $request_time = $this->createTimestamp('2013-12-11 10:09:08');
    $options = array();

    // Mocks the formatDiff function of the dateformatter object.
    $this->dateFormatterStub
      ->expects($this->any())
      ->method('formatDiff')
      ->with($request_time, $timestamp, $options)
      ->will($this->returnValue($expected));

    $request = Request::createFromGlobals();
    $request->server->set('REQUEST_TIME', $request_time);
    // Mocks a the request stack getting the current request.
    $this->requestStack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);

    $this->assertEquals($expected, $this->dateFormatterStub->formatTimeDiffUntil($timestamp, $options));
  }

  /**
   * Tests the formatDiff method.
   *
   * @dataProvider providerTestFormatDiff
   *
   * @covers ::formatDiff
   */
  public function testformatDiff($expected, $timestamp1, $timestamp2, $options = array()) {

    // Mocks a simple formatPlural implementation.
    $this->stringTranslation->expects($this->any())
      ->method('formatPlural')
      ->with($this->anything(), $this->anything(), $this->anything(), array(), array('langcode' => isset($options['langcode']) ? $options['langcode'] : NULL))
      ->will($this->returnCallback(function($count, $one, $multiple) {
        return $count == 1 ? $one : str_replace('@count', $count, $multiple);
      }));

    // Mocks a simple translate implementation.
    $this->stringTranslation->expects($this->any())
      ->method('translate')
      ->with($this->anything())
      ->will($this->returnCallback(function($string, $args, $options) {
        return $string;
      }));

    $this->assertEquals($expected, $this->dateFormatter->formatDiff($timestamp1, $timestamp2, $options));
  }

  /**
   * Data provider for testformatDiff().
   */
  public function providerTestFormatDiff() {
    // This is the fixed request time in the test.
    $request_time = $this->createTimestamp('2013-12-11 10:09:08');

    $granularity_3 = array('granularity' => 3);
    $granularity_4 = array('granularity' => 4);

    $langcode_en = array('langcode' => 'en');
    $langcode_lolspeak = array('langcode' => 'xxx-lolspeak');

    $non_strict = array('strict' => FALSE);

    $data = array(
      // Checks for equal timestamps.
      array('0 seconds', $request_time, $request_time),

      // Checks for seconds only.
      array('1 second', $this->createTimestamp('2013-12-11 10:09:07'), $request_time),
      array('1 second', $this->createTimestamp('2013-12-11 10:09:07'), $request_time),
      array('1 second', $this->createTimestamp('2013-12-11 10:09:07'), $request_time, $granularity_3 + $langcode_en),
      array('1 second', $this->createTimestamp('2013-12-11 10:09:07'), $request_time, $granularity_4 + $langcode_lolspeak),
      array('2 seconds', $this->createTimestamp('2013-12-11 10:09:06'), $request_time),
      array('59 seconds', $this->createTimestamp('2013-12-11 10:08:09'), $request_time),
      array('59 seconds', $this->createTimestamp('2013-12-11 10:08:09'), $request_time),

      // Checks for minutes and possibly seconds.
      array('1 minute', $this->createTimestamp('2013-12-11 10:08:08'), $request_time),
      array('1 minute', $this->createTimestamp('2013-12-11 10:08:08'), $request_time),
      array('1 minute 1 second', $this->createTimestamp('2013-12-11 10:08:07'), $request_time),
      array('1 minute 59 seconds', $this->createTimestamp('2013-12-11 10:07:09'), $request_time),
      array('2 minutes', $this->createTimestamp('2013-12-11 10:07:08'), $request_time),
      array('2 minutes 1 second', $this->createTimestamp('2013-12-11 10:07:07'), $request_time),
      array('2 minutes 2 seconds', $this->createTimestamp('2013-12-11 10:07:06'), $request_time),
      array('2 minutes 2 seconds', $this->createTimestamp('2013-12-11 10:07:06'), $request_time, $granularity_3),
      array('2 minutes 2 seconds', $this->createTimestamp('2013-12-11 10:07:06'), $request_time, $granularity_4),
      array('30 minutes', $this->createTimestamp('2013-12-11 09:39:08'), $request_time),
      array('59 minutes 59 seconds', $this->createTimestamp('2013-12-11 09:09:09'), $request_time),
      array('59 minutes 59 seconds', $this->createTimestamp('2013-12-11 09:09:09'), $request_time),

      // Checks for hours and possibly minutes or seconds.
      array('1 hour', $this->createTimestamp('2013-12-11 09:09:08'), $request_time),
      array('1 hour', $this->createTimestamp('2013-12-11 09:09:08'), $request_time),
      array('1 hour 1 second', $this->createTimestamp('2013-12-11 09:09:07'), $request_time),
      array('1 hour 2 seconds', $this->createTimestamp('2013-12-11 09:09:06'), $request_time),
      array('1 hour 1 minute', $this->createTimestamp('2013-12-11 09:08:08'), $request_time),
      array('1 hour 1 minute 1 second', $this->createTimestamp('2013-12-11 09:08:07'), $request_time, $granularity_3),
      array('1 hour 1 minute 2 seconds', $this->createTimestamp('2013-12-11 09:08:06'), $request_time, $granularity_4),
      array('1 hour 30 minutes', $this->createTimestamp('2013-12-11 08:39:08'), $request_time),
      array('2 hours', $this->createTimestamp('2013-12-11 08:09:08'), $request_time),
      array('23 hours 59 minutes', $this->createTimestamp('2013-12-10 10:10:08'), $request_time),

      // Checks for days and possibly hours, minutes or seconds.
      array('1 day', $this->createTimestamp('2013-12-10 10:09:08'), $request_time),
      array('1 day 1 second', $this->createTimestamp('2013-12-10 10:09:07'), $request_time),
      array('1 day 1 hour', $this->createTimestamp('2013-12-10 09:09:08'), $request_time),
      array('1 day 1 hour 1 minute', $this->createTimestamp('2013-12-10 09:08:07'), $request_time, $granularity_3 + $langcode_en),
      array('1 day 1 hour 1 minute 1 second', $this->createTimestamp('2013-12-10 09:08:07'), $request_time, $granularity_4 + $langcode_lolspeak),
      array('1 day 2 hours 2 minutes 2 seconds', $this->createTimestamp('2013-12-10 08:07:06'), $request_time, $granularity_4),
      array('2 days', $this->createTimestamp('2013-12-09 10:09:08'), $request_time),
      array('2 days 2 minutes', $this->createTimestamp('2013-12-09 10:07:08'), $request_time),
      array('2 days 2 hours', $this->createTimestamp('2013-12-09 08:09:08'), $request_time),
      array('2 days 2 hours 2 minutes', $this->createTimestamp('2013-12-09 08:07:06'), $request_time, $granularity_3 + $langcode_en),
      array('2 days 2 hours 2 minutes 2 seconds', $this->createTimestamp('2013-12-09 08:07:06'), $request_time, $granularity_4 + $langcode_lolspeak),

      // Checks for weeks and possibly days, hours, minutes or seconds.
      array('1 week', $this->createTimestamp('2013-12-04 10:09:08'), $request_time),
      array('1 week 1 day', $this->createTimestamp('2013-12-03 10:09:08'), $request_time),
      array('2 weeks', $this->createTimestamp('2013-11-27 10:09:08'), $request_time),
      array('2 weeks 2 days', $this->createTimestamp('2013-11-25 08:07:08'), $request_time),
      array('2 weeks 2 days 2 hours 2 minutes', $this->createTimestamp('2013-11-25 08:07:08'), $request_time, $granularity_4),
      array('4 weeks', $this->createTimestamp('2013-11-13 10:09:08'), $request_time),
      array('4 weeks 1 day', $this->createTimestamp('2013-11-12 10:09:08'), $request_time),

      // Checks for months and possibly days, hours, minutes or seconds.
      array('1 month', $this->createTimestamp('2013-11-11 10:09:08'), $request_time),
      array('1 month 1 second', $this->createTimestamp('2013-11-11 10:09:07'), $request_time),
      array('1 month 1 hour', $this->createTimestamp('2013-11-11 09:09:08'), $request_time),
      array('1 month 1 hour 1 minute', $this->createTimestamp('2013-11-11 09:08:07'), $request_time, $granularity_3),
      array('1 month 1 hour 1 minute 1 second', $this->createTimestamp('2013-11-11 09:08:07'), $request_time, $granularity_4),
      array('1 month 4 weeks', $this->createTimestamp('2013-10-13 10:09:08'), $request_time),
      array('1 month 4 weeks 1 day', $this->createTimestamp('2013-10-13 10:09:08'), $request_time, $granularity_3),
      array('1 month 4 weeks', $this->createTimestamp('2013-10-12 10:09:08'), $request_time),
      array('1 month 4 weeks 2 days', $this->createTimestamp('2013-10-12 10:09:08'), $request_time, $granularity_3),
      array('2 months', $this->createTimestamp('2013-10-11 10:09:08'), $request_time),
      array('2 months 1 day', $this->createTimestamp('2013-10-10 10:09:08'), $request_time),
      array('2 months 2 days', $this->createTimestamp('2013-10-09 08:07:06'), $request_time),
      array('2 months 2 days 2 hours', $this->createTimestamp('2013-10-09 08:07:06'), $request_time, $granularity_3),
      array('2 months 2 days 2 hours 2 minutes', $this->createTimestamp('2013-10-09 08:07:06'), $request_time, $granularity_4),
      array('6 months 2 days', $this->createTimestamp('2013-06-09 10:09:08'), $request_time),
      array('11 months 3 hours', $this->createTimestamp('2013-01-11 07:09:08'), $request_time),
      array('11 months 4 weeks', $this->createTimestamp('2012-12-12 10:09:08'), $request_time),
      array('11 months 4 weeks 2 days', $this->createTimestamp('2012-12-12 10:09:08'), $request_time, $granularity_3),

      // Checks for years and possibly months, days, hours, minutes or seconds.
      array('1 year', $this->createTimestamp('2012-12-11 10:09:08'), $request_time),
      array('1 year 1 minute', $this->createTimestamp('2012-12-11 10:08:08'), $request_time),
      array('1 year 1 day', $this->createTimestamp('2012-12-10 10:09:08'), $request_time),
      array('2 years', $this->createTimestamp('2011-12-11 10:09:08'), $request_time),
      array('2 years 2 minutes', $this->createTimestamp('2011-12-11 10:07:08'), $request_time),
      array('2 years 2 days', $this->createTimestamp('2011-12-09 10:09:08'), $request_time),
      array('2 years 2 months 2 days', $this->createTimestamp('2011-10-09 08:07:06'), $request_time, $granularity_3),
      array('2 years 2 months 2 days 2 hours', $this->createTimestamp('2011-10-09 08:07:06'), $request_time, $granularity_4),
      array('10 years', $this->createTimestamp('2003-12-11 10:09:08'), $request_time),
      array('100 years', $this->createTimestamp('1913-12-11 10:09:08'), $request_time),

      // Checks the non-strict option vs. strict (default).
      array('1 second', $this->createTimestamp('2013-12-11 10:09:08'), $this->createTimestamp('2013-12-11 10:09:07'), $non_strict),
      array('0 seconds', $this->createTimestamp('2013-12-11 10:09:08'), $this->createTimestamp('2013-12-11 10:09:07')),

      // Checks granularity limit.
      array('2 years 3 months 1 week', $this->createTimestamp('2011-08-30 11:15:57'), $request_time, $granularity_3),
    );

    return $data;
  }

  /**
   * Creates a UNIX timestamp given a date and time string in the format
   * year-month-day hour:minute:seconds (e.g. 2013-12-11 10:09:08).
   *
   * @param string $dateTimeString
   *   The formatted date and time string.
   *
   * @return int
   *   The UNIX timestamp.
   */
  private function createTimestamp($dateTimeString) {
    return \DateTime::createFromFormat('Y-m-d G:i:s', $dateTimeString)->getTimestamp();
  }

}
