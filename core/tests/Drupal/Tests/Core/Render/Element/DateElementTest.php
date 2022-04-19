<?php

namespace Drupal\Tests\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Date;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Render\Element\Date
 * @group Render
 */
class DateElementTest extends UnitTestCase {

  /**
   * @covers ::processDate
   * @group legacy
   */
  public function testProcessDate(): void {
    $element = [
      '#attributes' => ['type' => 'date'],
      '#date_date_format' => 'Y-m-d',
    ];
    $complete_form = [];
    $this->expectDeprecation('Drupal\Core\Render\Element\Date::processDate() is deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. There is no replacement. See https://www.drupal.org/node/3258267');
    Date::processDate($element, $this->createMock(FormStateInterface::class), $complete_form);
  }

}
