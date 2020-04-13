<?php

namespace Drupal\Tests\datetime\Kernel\Views;

use Drupal\node\Entity\Node;
use Drupal\views\Views;

/**
 * Tests the Drupal\datetime\Plugin\views\filter\Date handler.
 *
 * @group datetime
 */
class ArgumentDateTimeTest extends DateTimeHandlerTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_argument_datetime'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    // Add some basic test nodes.
    $dates = [
      '2000-10-10',
      '2001-10-10',
      '2002-01-01',
      // Add a date that is the year 2002 in UTC, but 2003 in the site's time
      // zone (Australia/Sydney).
      '2002-12-31T23:00:00',
    ];
    foreach ($dates as $date) {
      $node = Node::create([
        'title' => $this->randomMachineName(8),
        'type' => 'page',
        'field_date' => [
          'value' => $date,
        ],
      ]);
      $node->save();
      $this->nodes[] = $node;
    }
  }

  /**
   * Test year argument.
   *
   * @see \Drupal\datetime\Plugin\views\argument\YearDate
   */
  public function testDatetimeArgumentYear() {
    $view = Views::getView('test_argument_datetime');

    // The 'default' display has the 'year' argument.
    $view->setDisplay('default');
    $this->executeView($view, ['2000']);
    $expected = [];
    $expected[] = ['nid' => $this->nodes[0]->id()];
    $this->assertIdenticalResultset($view, $expected, $this->map);
    $view->destroy();

    $view->setDisplay('default');
    $this->executeView($view, ['2002']);
    $expected = [];
    $expected[] = ['nid' => $this->nodes[2]->id()];
    $this->assertIdenticalResultset($view, $expected, $this->map);
    $view->destroy();

    $view->setDisplay('default');
    $this->executeView($view, ['2003']);
    $expected = [];
    $expected[] = ['nid' => $this->nodes[3]->id()];
    $this->assertIdenticalResultset($view, $expected, $this->map);
    $view->destroy();

    // Tests different system timezone with the same nodes.
    $this->setSiteTimezone('America/Vancouver');

    $view->setDisplay('default');
    $this->executeView($view, ['2002']);
    $expected = [];
    // Only the 3rd node is returned here since UTC 2002-01-01T00:00:00 is still
    // in 2001 for this user timezone.
    $expected[] = ['nid' => $this->nodes[3]->id()];
    $this->assertIdenticalResultset($view, $expected, $this->map);
    $view->destroy();
  }

  /**
   * Test month argument.
   *
   * @see \Drupal\datetime\Plugin\views\argument\MonthDate
   */
  public function testDatetimeArgumentMonth() {
    $view = Views::getView('test_argument_datetime');
    // The 'embed_1' display has the 'month' argument.
    $view->setDisplay('embed_1');

    $this->executeView($view, ['10']);
    $expected = [];
    $expected[] = ['nid' => $this->nodes[0]->id()];
    $expected[] = ['nid' => $this->nodes[1]->id()];
    $this->assertIdenticalResultset($view, $expected, $this->map);
    $view->destroy();

    $view->setDisplay('embed_1');
    $this->executeView($view, ['01']);
    $expected = [];
    $expected[] = ['nid' => $this->nodes[2]->id()];
    $expected[] = ['nid' => $this->nodes[3]->id()];
    $this->assertIdenticalResultset($view, $expected, $this->map);
    $view->destroy();
  }

  /**
   * Test day argument.
   *
   * @see \Drupal\datetime\Plugin\views\argument\DayDate
   */
  public function testDatetimeArgumentDay() {
    $view = Views::getView('test_argument_datetime');

    // The 'embed_2' display has the 'day' argument.
    $view->setDisplay('embed_2');
    $this->executeView($view, ['10']);
    $expected = [];
    $expected[] = ['nid' => $this->nodes[0]->id()];
    $expected[] = ['nid' => $this->nodes[1]->id()];
    $this->assertIdenticalResultset($view, $expected, $this->map);
    $view->destroy();

    $view->setDisplay('embed_2');
    $this->executeView($view, ['01']);
    $expected = [];
    $expected[] = ['nid' => $this->nodes[2]->id()];
    $expected[] = ['nid' => $this->nodes[3]->id()];
    $this->assertIdenticalResultset($view, $expected, $this->map);
    $view->destroy();
  }

  /**
   * Test year, month, and day arguments combined.
   */
  public function testDatetimeArgumentAll() {
    $view = Views::getView('test_argument_datetime');
    // The 'embed_3' display has year, month, and day arguments.
    $view->setDisplay('embed_3');

    $this->executeView($view, ['2000', '10', '10']);
    $expected = [];
    $expected[] = ['nid' => $this->nodes[0]->id()];
    $this->assertIdenticalResultset($view, $expected, $this->map);
    $view->destroy();

    $view->setDisplay('embed_3');
    $this->executeView($view, ['2002', '01', '01']);
    $expected = [];
    $expected[] = ['nid' => $this->nodes[2]->id()];
    $this->assertIdenticalResultset($view, $expected, $this->map);
    $view->destroy();
  }

  /**
   * Test week WW argument.
   */
  public function testDatetimeArgumentWeek() {
    $view = Views::getView('test_argument_datetime');
    // The 'embed_4' display has WW argument.
    $view->setDisplay('embed_4');

    $this->executeView($view, ['41']);
    $expected = [];
    $expected[] = ['nid' => $this->nodes[0]->id()];
    $expected[] = ['nid' => $this->nodes[1]->id()];
    $this->assertIdenticalResultset($view, $expected, $this->map);
    $view->destroy();

    $view->setDisplay('embed_4');
    $this->executeView($view, ['01']);
    $expected = [];
    $expected[] = ['nid' => $this->nodes[2]->id()];
    $expected[] = ['nid' => $this->nodes[3]->id()];
    $this->assertIdenticalResultset($view, $expected, $this->map);
    $view->destroy();
  }

  /**
   * Test full_date CCYYMMDD argument.
   */
  public function testDatetimeArgumentFullDate() {
    $view = Views::getView('test_argument_datetime');
    // The 'embed_5' display has CCYYMMDD argument.
    $view->setDisplay('embed_5');

    $this->executeView($view, ['20001010']);
    $expected = [];
    $expected[] = ['nid' => $this->nodes[0]->id()];
    $this->assertIdenticalResultset($view, $expected, $this->map);
    $view->destroy();

    $view->setDisplay('embed_5');
    $this->executeView($view, ['20020101']);
    $expected = [];
    $expected[] = ['nid' => $this->nodes[2]->id()];
    $this->assertIdenticalResultset($view, $expected, $this->map);
    $view->destroy();
  }

  /**
   * Test year_month CCYYMM argument.
   */
  public function testDatetimeArgumentYearMonth() {
    $view = Views::getView('test_argument_datetime');
    // The 'embed_6' display has CCYYMM argument.
    $view->setDisplay('embed_6');

    $this->executeView($view, ['200010']);
    $expected = [];
    $expected[] = ['nid' => $this->nodes[0]->id()];
    $this->assertIdenticalResultset($view, $expected, $this->map);
    $view->destroy();

    $view->setDisplay('embed_6');
    $this->executeView($view, ['200201']);
    $expected = [];
    $expected[] = ['nid' => $this->nodes[2]->id()];
    $this->assertIdenticalResultset($view, $expected, $this->map);
    $view->destroy();
  }

}
