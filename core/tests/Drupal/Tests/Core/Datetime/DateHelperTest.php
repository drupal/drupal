<?php

namespace Drupal\Tests\Core\Datetime;

use Drupal\Core\Datetime\DateHelper;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Datetime\DateHelper
 * @group Datetime
 */
class DateHelperTest extends UnitTestCase {

  /**
   * @covers ::weekDaysOrdered
   * @dataProvider providerTestWeekDaysOrdered
   */
  public function testWeekDaysOrdered($first_day, $expected) {
    $container = new ContainerBuilder();
    $config = ['system.date' => ['first_day' => $first_day]];
    $container->set('config.factory', $this->getConfigFactoryStub($config));
    \Drupal::setContainer($container);

    $weekdays = DateHelper::weekDaysUntranslated();
    // self::assertSame() MUST be used here as it checks for array key order.
    $this->assertSame($expected, DateHelper::weekDaysOrdered($weekdays));
  }

  public function providerTestWeekDaysOrdered() {
    $data = [];
    $data[] = [0, [
      0 => 'Sunday',
      1 => 'Monday',
      2 => 'Tuesday',
      3 => 'Wednesday',
      4 => 'Thursday',
      5 => 'Friday',
      6 => 'Saturday',
    ]];
    $data[] = [1, [
      1 => 'Monday',
      2 => 'Tuesday',
      3 => 'Wednesday',
      4 => 'Thursday',
      5 => 'Friday',
      6 => 'Saturday',
      0 => 'Sunday',
    ]];
    $data[] = [2, [
      2 => 'Tuesday',
      3 => 'Wednesday',
      4 => 'Thursday',
      5 => 'Friday',
      6 => 'Saturday',
      0 => 'Sunday',
      1 => 'Monday',
    ]];
    $data[] = [3, [
      3 => 'Wednesday',
      4 => 'Thursday',
      5 => 'Friday',
      6 => 'Saturday',
      0 => 'Sunday',
      1 => 'Monday',
      2 => 'Tuesday',
    ]];
    $data[] = [4, [
      4 => 'Thursday',
      5 => 'Friday',
      6 => 'Saturday',
      0 => 'Sunday',
      1 => 'Monday',
      2 => 'Tuesday',
      3 => 'Wednesday',
    ]];
    $data[] = [5, [
      5 => 'Friday',
      6 => 'Saturday',
      0 => 'Sunday',
      1 => 'Monday',
      2 => 'Tuesday',
      3 => 'Wednesday',
      4 => 'Thursday',
    ]];
    $data[] = [6, [
      6 => 'Saturday',
      0 => 'Sunday',
      1 => 'Monday',
      2 => 'Tuesday',
      3 => 'Wednesday',
      4 => 'Thursday',
      5 => 'Friday',
    ]];
    $data[] = [7, [
      0 => 'Sunday',
      1 => 'Monday',
      2 => 'Tuesday',
      3 => 'Wednesday',
      4 => 'Thursday',
      5 => 'Friday',
      6 => 'Saturday',
    ]];
    return $data;
  }

}
