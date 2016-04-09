<?php

namespace Drupal\Tests\Core\Datetime;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Datetime\FormattedDateDiff;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\StringTranslation\TranslatableMarkup;

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
      ->method('translateString')
      ->willReturnCallback(function (TranslatableMarkup $arg) {
        return $arg->getUntranslatedString();
      });

    // Check if the granularity is specified.
    if ($granularity) {
      $result = $this->dateFormatter->formatInterval($interval, $granularity, $langcode);
    }
    else {
      $result = $this->dateFormatter->formatInterval($interval);
    }

    $this->assertEquals(new TranslatableMarkup($expected, [], ['langcode' => $langcode], $this->stringTranslation), $result);
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
      array(3601, 2, '1 hour'),
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
    $result = $this->dateFormatter->formatInterval(0, 1, 'xxx-lolspeak');
    $this->assertEquals(new TranslatableMarkup('0 sec', array(), array('langcode' => 'xxx-lolspeak'), $this->stringTranslation), $result);
  }

  /**
   * Tests the getSampleDateFormats method.
   *
   * @covers \Drupal\Core\Datetime\DateFormatter::getSampleDateFormats
   */
  public function testGetSampleDateFormats() {
    $timestamp = strtotime('2015-03-22 14:23:00');
    $expected = $this->dateFormatter->getSampleDateFormats('en', $timestamp, 'Australia/Sydney');

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
      ->expects($this->at(0))
      ->method('formatDiff')
      ->with($timestamp, $request_time, $options)
      ->will($this->returnValue($expected));

    $this->dateFormatterStub
      ->expects($this->at(1))
      ->method('formatDiff')
      ->with($timestamp, $request_time, $options + ['return_as_object' => TRUE])
      ->will($this->returnValue(new FormattedDateDiff('1 second', 1)));

    $request = Request::createFromGlobals();
    $request->server->set('REQUEST_TIME', $request_time);
    // Mocks a the request stack getting the current request.
    $this->requestStack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);

    $this->assertEquals($expected, $this->dateFormatterStub->formatTimeDiffSince($timestamp, $options));
    $options['return_as_object'] = TRUE;
    $expected_object = new FormattedDateDiff('1 second', 1);
    $this->assertEquals($expected_object, $this->dateFormatterStub->formatTimeDiffSince($timestamp, $options));
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
      ->expects($this->at(0))
      ->method('formatDiff')
      ->with($request_time, $timestamp, $options)
      ->will($this->returnValue($expected));

    $this->dateFormatterStub
      ->expects($this->at(1))
      ->method('formatDiff')
      ->with($request_time, $timestamp, $options + ['return_as_object' => TRUE])
      ->will($this->returnValue(new FormattedDateDiff('1 second', 1)));

    $request = Request::createFromGlobals();
    $request->server->set('REQUEST_TIME', $request_time);
    // Mocks a the request stack getting the current request.
    $this->requestStack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);

    $this->assertEquals($expected, $this->dateFormatterStub->formatTimeDiffUntil($timestamp, $options));
    $options['return_as_object'] = TRUE;
    $expected_object = new FormattedDateDiff('1 second', 1);
    $this->assertEquals($expected_object, $this->dateFormatterStub->formatTimeDiffUntil($timestamp, $options));
  }

  /**
   * Tests the formatDiff method.
   *
   * @dataProvider providerTestFormatDiff
   *
   * @covers ::formatDiff
   */
  public function testformatDiff($expected, $max_age, $timestamp1, $timestamp2, $options = array()) {
    // Mocks a simple translateString implementation.
    $this->stringTranslation->expects($this->any())
      ->method('translateString')
      ->willReturnCallback(function (TranslatableMarkup $arg) {
        return $arg->getUntranslatedString();
      });

    if (isset($options['langcode'])) {
      $expected_markup = new TranslatableMarkup($expected, [], ['langcode' => $options['langcode']], $this->stringTranslation);
    }
    else {
      $expected_markup = new TranslatableMarkup($expected, [], [], $this->stringTranslation);
    }
    $this->assertEquals($expected_markup, $this->dateFormatter->formatDiff($timestamp1, $timestamp2, $options));

    $options['return_as_object'] = TRUE;
    $expected_object = new FormattedDateDiff($expected, $max_age);
    $this->assertEquals($expected_object, $this->dateFormatter->formatDiff($timestamp1, $timestamp2, $options));
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
      array('0 seconds', 0, $request_time, $request_time),

      // Checks for seconds only.
      array('1 second', 1, $this->createTimestamp('2013-12-11 10:09:07'), $request_time),
      array('1 second', 1, $this->createTimestamp('2013-12-11 10:09:07'), $request_time),
      array('1 second', 1, $this->createTimestamp('2013-12-11 10:09:07'), $request_time, $granularity_3 + $langcode_en),
      array('1 second', 1, $this->createTimestamp('2013-12-11 10:09:07'), $request_time, $granularity_4 + $langcode_lolspeak),
      array('2 seconds', 1, $this->createTimestamp('2013-12-11 10:09:06'), $request_time),
      array('59 seconds', 1, $this->createTimestamp('2013-12-11 10:08:09'), $request_time),
      array('59 seconds', 1, $this->createTimestamp('2013-12-11 10:08:09'), $request_time),

      // Checks for minutes and possibly seconds.
      array('1 minute', 60, $this->createTimestamp('2013-12-11 10:08:08'), $request_time),
      array('1 minute', 60, $this->createTimestamp('2013-12-11 10:08:08'), $request_time),
      array('1 minute 1 second', 1, $this->createTimestamp('2013-12-11 10:08:07'), $request_time),
      array('1 minute 59 seconds', 1, $this->createTimestamp('2013-12-11 10:07:09'), $request_time),
      array('2 minutes', 60, $this->createTimestamp('2013-12-11 10:07:08'), $request_time),
      array('2 minutes 1 second', 1, $this->createTimestamp('2013-12-11 10:07:07'), $request_time),
      array('2 minutes 2 seconds', 1, $this->createTimestamp('2013-12-11 10:07:06'), $request_time),
      array('2 minutes 2 seconds', 1, $this->createTimestamp('2013-12-11 10:07:06'), $request_time, $granularity_3),
      array('2 minutes 2 seconds', 1, $this->createTimestamp('2013-12-11 10:07:06'), $request_time, $granularity_4),
      array('30 minutes', 60, $this->createTimestamp('2013-12-11 09:39:08'), $request_time),
      array('59 minutes 59 seconds', 1, $this->createTimestamp('2013-12-11 09:09:09'), $request_time),
      array('59 minutes 59 seconds', 1, $this->createTimestamp('2013-12-11 09:09:09'), $request_time),

      // Checks for hours and possibly minutes or seconds.
      array('1 hour', 3600, $this->createTimestamp('2013-12-11 09:09:08'), $request_time),
      array('1 hour', 3600, $this->createTimestamp('2013-12-11 09:09:08'), $request_time),
      array('1 hour', 3600, $this->createTimestamp('2013-12-11 09:09:07'), $request_time),
      array('1 hour', 3600, $this->createTimestamp('2013-12-11 09:09:06'), $request_time),
      array('1 hour 1 minute', 60, $this->createTimestamp('2013-12-11 09:08:08'), $request_time),
      array('1 hour 1 minute 1 second', 1, $this->createTimestamp('2013-12-11 09:08:07'), $request_time, $granularity_3),
      array('1 hour 1 minute 2 seconds', 1, $this->createTimestamp('2013-12-11 09:08:06'), $request_time, $granularity_4),
      array('1 hour 30 minutes', 60, $this->createTimestamp('2013-12-11 08:39:08'), $request_time),
      array('2 hours', 3600, $this->createTimestamp('2013-12-11 08:09:08'), $request_time),
      array('23 hours 59 minutes', 60, $this->createTimestamp('2013-12-10 10:10:08'), $request_time),

      // Checks for days and possibly hours, minutes or seconds.
      array('1 day', 86400, $this->createTimestamp('2013-12-10 10:09:08'), $request_time),
      array('1 day', 86400, $this->createTimestamp('2013-12-10 10:09:07'), $request_time),
      array('1 day 1 hour', 3600, $this->createTimestamp('2013-12-10 09:09:08'), $request_time),
      array('1 day 1 hour 1 minute', 60, $this->createTimestamp('2013-12-10 09:08:07'), $request_time, $granularity_3 + $langcode_en),
      array('1 day 1 hour 1 minute 1 second', 1, $this->createTimestamp('2013-12-10 09:08:07'), $request_time, $granularity_4 + $langcode_lolspeak),
      array('1 day 2 hours 2 minutes 2 seconds', 1, $this->createTimestamp('2013-12-10 08:07:06'), $request_time, $granularity_4),
      array('2 days', 86400, $this->createTimestamp('2013-12-09 10:09:08'), $request_time),
      array('2 days', 86400, $this->createTimestamp('2013-12-09 10:07:08'), $request_time),
      array('2 days 2 hours', 3600, $this->createTimestamp('2013-12-09 08:09:08'), $request_time),
      array('2 days 2 hours 2 minutes', 60, $this->createTimestamp('2013-12-09 08:07:06'), $request_time, $granularity_3 + $langcode_en),
      array('2 days 2 hours 2 minutes 2 seconds', 1, $this->createTimestamp('2013-12-09 08:07:06'), $request_time, $granularity_4 + $langcode_lolspeak),

      // Checks for weeks and possibly days, hours, minutes or seconds.
      array('1 week', 7 * 86400, $this->createTimestamp('2013-12-04 10:09:08'), $request_time),
      array('1 week 1 day', 86400, $this->createTimestamp('2013-12-03 10:09:08'), $request_time),
      array('2 weeks', 7 * 86400, $this->createTimestamp('2013-11-27 10:09:08'), $request_time),
      array('2 weeks 2 days', 86400, $this->createTimestamp('2013-11-25 08:07:08'), $request_time),
      array('2 weeks 2 days 2 hours 2 minutes', 60, $this->createTimestamp('2013-11-25 08:07:08'), $request_time, $granularity_4),
      array('4 weeks', 7 * 86400, $this->createTimestamp('2013-11-13 10:09:08'), $request_time),
      array('4 weeks 1 day', 86400, $this->createTimestamp('2013-11-12 10:09:08'), $request_time),

      // Checks for months and possibly days, hours, minutes or seconds.
      array('1 month', 30 * 86400, $this->createTimestamp('2013-11-11 10:09:08'), $request_time),
      array('1 month', 30 * 86400, $this->createTimestamp('2013-11-11 10:09:07'), $request_time),
      array('1 month', 30 * 86400, $this->createTimestamp('2013-11-11 09:09:08'), $request_time),
      array('1 month', 30 * 86400, $this->createTimestamp('2013-11-11 09:08:07'), $request_time, $granularity_3),
      array('1 month', 30 * 86400, $this->createTimestamp('2013-11-11 09:08:07'), $request_time, $granularity_4),
      array('1 month 4 weeks', 7 * 86400, $this->createTimestamp('2013-10-13 10:09:08'), $request_time),
      array('1 month 4 weeks 1 day', 86400, $this->createTimestamp('2013-10-13 10:09:08'), $request_time, $granularity_3),
      array('1 month 4 weeks', 7 * 86400, $this->createTimestamp('2013-10-12 10:09:08'), $request_time),
      array('1 month 4 weeks 2 days', 86400, $this->createTimestamp('2013-10-12 10:09:08'), $request_time, $granularity_3),
      array('2 months', 30 * 86400, $this->createTimestamp('2013-10-11 10:09:08'), $request_time),
      array('2 months', 30 * 86400, $this->createTimestamp('2013-10-10 10:09:08'), $request_time),
      array('2 months', 30 * 86400, $this->createTimestamp('2013-10-09 08:07:06'), $request_time),
      array('2 months', 30 * 86400, $this->createTimestamp('2013-10-09 08:07:06'), $request_time, $granularity_3),
      array('2 months', 30 * 86400, $this->createTimestamp('2013-10-09 08:07:06'), $request_time, $granularity_4),
      array('6 months', 30 * 86400, $this->createTimestamp('2013-06-09 10:09:08'), $request_time),
      array('11 months', 30 * 86400, $this->createTimestamp('2013-01-11 07:09:08'), $request_time),
      array('11 months 4 weeks', 7 * 86400, $this->createTimestamp('2012-12-12 10:09:08'), $request_time),
      array('11 months 4 weeks 2 days', 86400, $this->createTimestamp('2012-12-12 10:09:08'), $request_time, $granularity_3),

      // Checks for years and possibly months, days, hours, minutes or seconds.
      array('1 year', 365 * 86400, $this->createTimestamp('2012-12-11 10:09:08'), $request_time),
      array('1 year', 365 * 86400, $this->createTimestamp('2012-12-11 10:08:08'), $request_time),
      array('1 year', 365 * 86400, $this->createTimestamp('2012-12-10 10:09:08'), $request_time),
      array('2 years', 365 * 86400, $this->createTimestamp('2011-12-11 10:09:08'), $request_time),
      array('2 years', 365 * 86400, $this->createTimestamp('2011-12-11 10:07:08'), $request_time),
      array('2 years', 365 * 86400, $this->createTimestamp('2011-12-09 10:09:08'), $request_time),
      array('2 years 2 months', 30 * 86400, $this->createTimestamp('2011-10-09 08:07:06'), $request_time, $granularity_3),
      array('2 years 2 months', 30 * 86400, $this->createTimestamp('2011-10-09 08:07:06'), $request_time, $granularity_4),
      array('10 years', 365 * 86400, $this->createTimestamp('2003-12-11 10:09:08'), $request_time),
      array('100 years', 365 * 86400, $this->createTimestamp('1913-12-11 10:09:08'), $request_time),

      // Checks the non-strict option vs. strict (default).
      array('1 second', 1, $this->createTimestamp('2013-12-11 10:09:08'), $this->createTimestamp('2013-12-11 10:09:07'), $non_strict),
      array('0 seconds', 0, $this->createTimestamp('2013-12-11 10:09:08'), $this->createTimestamp('2013-12-11 10:09:07')),

      // Checks granularity limit.
      array('2 years 3 months 1 week', 7 * 86400, $this->createTimestamp('2011-08-30 11:15:57'), $request_time, $granularity_3),
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
