<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Datetime;

use Drupal\Core\Datetime\DateHelper;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Datetime\DateHelper
 * @group Datetime
 */
class DateHelperTest extends UnitTestCase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $config = ['system.date' => ['first_day' => 'Sunday']];
    $container->set('config.factory', $this->getConfigFactoryStub($config));

    $this->languageManager = $this->createMock('\Drupal\Core\Language\LanguageManagerInterface');
    $language = new Language(['langcode' => 'en']);
    $this->languageManager->expects($this->any())
      ->method('getDefaultLanguage')
      ->willReturn($language);
    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->willReturn($language);
    $container->set('language_manager', $this->languageManager);

    \Drupal::setContainer($container);
  }

  /**
   * @covers ::weekDaysOrdered
   * @dataProvider providerTestWeekDaysOrdered
   */
  public function testWeekDaysOrdered($first_day, $expected): void {
    $container = new ContainerBuilder();
    $config = ['system.date' => ['first_day' => $first_day]];
    $container->set('config.factory', $this->getConfigFactoryStub($config));
    \Drupal::setContainer($container);

    $weekdays = DateHelper::weekDaysUntranslated();
    // self::assertSame() MUST be used here as it checks for array key order.
    $this->assertSame($expected, DateHelper::weekDaysOrdered($weekdays));
  }

  public static function providerTestWeekDaysOrdered() {
    $data = [];
    $data[] = [
      0,
      [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
      ],
    ];
    $data[] = [
      1,
      [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        0 => 'Sunday',
      ],
    ];
    $data[] = [
      2,
      [
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        0 => 'Sunday',
        1 => 'Monday',
      ],
    ];
    $data[] = [
      3,
      [
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
      ],
    ];
    $data[] = [
      4,
      [
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
      ],
    ];
    $data[] = [
      5,
      [
        5 => 'Friday',
        6 => 'Saturday',
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
      ],
    ];
    $data[] = [
      6,
      [
        6 => 'Saturday',
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
      ],
    ];
    $data[] = [
      7,
      [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
      ],
    ];
    return $data;
  }

  /**
   * @covers ::daysInMonth
   */
  public function testDaysInMonth(): void {
    // @todo Consider deprecating passing NULL in
    //   https://www.drupal.org/project/drupal/issues/3299788
    // Passing NULL, FALSE, or an empty string should default to now. Just
    // check these are NOT null to avoid copying the implementation here.
    $this->assertNotNull(DateHelper::daysInMonth());
    $this->assertNotNull(DateHelper::daysInMonth(FALSE));
    $this->assertNotNull(DateHelper::daysInMonth(''));

    // Pass nothing and expect to get NULL.
    $this->assertNull(DateHelper::daysInMonth(0));
    $this->assertNull(DateHelper::daysInMonth('0'));

    $value = '2022-12-31 00:00:00';
    $dateString = DateHelper::daysInMonth($value);
    $this->assertEquals('31', $dateString);

    $value = '2020-11-30 00:00:00';
    $dateString = DateHelper::daysInMonth($value);
    $this->assertEquals('30', $dateString);
  }

  /**
   * @covers ::daysInYear
   */
  public function testDaysInYear(): void {
    // Passing NULL, FALSE, or an empty string should default to now. Just
    // check these are NOT null to avoid copying the implementation here.
    $this->assertNotNull(DateHelper::daysInYear());
    $this->assertNotNull(DateHelper::daysInYear(FALSE));
    $this->assertNotNull(DateHelper::daysInYear(''));

    // Pass nothing and expect to get NULL.
    $this->assertNull(DateHelper::daysInYear(0));
    $this->assertNull(DateHelper::daysInYear('0'));

    $value = '2022-12-31 00:00:00';
    $dateString = DateHelper::daysInYear($value);
    $this->assertEquals('365', $dateString);

    // 2020 is a leap year.
    $value = '2020-11-30 00:00:00';
    $dateString = DateHelper::daysInYear($value);
    $this->assertEquals('366', $dateString);
  }

  /**
   * @covers ::dayOfWeek
   */
  public function testDayOfWeek(): void {
    // Passing NULL, FALSE, or an empty string should default to now. Just
    // check these are NOT null to avoid copying the implementation here.
    $this->assertNotNull(DateHelper::dayOfWeek());
    $this->assertNotNull(DateHelper::dayOfWeek(FALSE));
    $this->assertNotNull(DateHelper::dayOfWeek(''));

    // Pass nothing and expect to get NULL.
    $this->assertNull(DateHelper::dayOfWeek(0));
    $this->assertNull(DateHelper::dayOfWeek('0'));

    // December 31st 2022 is a Saturday.
    $value = '2022-12-31 00:00:00';
    $dateString = DateHelper::dayOfWeek($value);
    $this->assertEquals('6', $dateString);

    // November 30th 2020 is a Monday.
    $value = '2020-11-30 00:00:00';
    $dateString = DateHelper::dayOfWeek($value);
    $this->assertEquals('1', $dateString);
  }

  /**
   * @covers ::dayOfWeekName
   */
  public function testDayOfWeekName(): void {
    // Passing NULL, FALSE, or an empty string should default to now. Just
    // check these are NOT null to avoid copying the implementation here.
    $this->assertNotNull(DateHelper::dayOfWeekName());
    $this->assertNotNull(DateHelper::dayOfWeekName(FALSE));
    $this->assertNotNull(DateHelper::dayOfWeekName(''));

    // Pass nothing and expect to get NULL.
    $this->assertNull(DateHelper::dayOfWeekName(0));
    $this->assertNull(DateHelper::dayOfWeekName('0'));

    // December 31st 2022 is a Saturday.
    $value = '2022-12-31 00:00:00';
    $dateString = DateHelper::dayOfWeekName($value);
    $this->assertEquals('Sat', $dateString);

    // November 30th 2020 is a Monday.
    $value = '2020-11-30 00:00:00';
    $dateString = DateHelper::dayOfWeekName($value);
    $this->assertEquals('Mon', $dateString);
  }

}
