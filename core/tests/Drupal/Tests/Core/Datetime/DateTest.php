<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Datetime\DateTest.
 */

namespace Drupal\Tests\Core\Datetime;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Datetime\Date
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
   * The tested date service class.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  protected function setUp() {
    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $this->languageManager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');
    $this->stringTranslation = $this->getMock('Drupal\Core\StringTranslation\TranslationInterface');

    $this->dateFormatter = new DateFormatter($this->entityManager, $this->languageManager, $this->stringTranslation, $this->getConfigFactoryStub());
  }

  /**
   * Tests the formatPlugin method.
   *
   * @dataProvider providerTestFormatInterval
   *
   * @see \Drupal\Core\Datetime\DateFormatter::formatInterval()
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

}
