<?php

namespace Drupal\Tests\views\Kernel\Plugin;

use DateInterval;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests views date handling across timezones.
 *
 * @group views
 *
 * @see \Drupal\views\Plugin\views\query\Sql
 */
class SqlDateTimezoneTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_group_by_date'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'entity_test',
    'system',
    'field',
    'user',
    'language',
  ];

  /**
   * The storage for the test entity type.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  public $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');

    $this->storage = $this->container->get('entity_type.manager')->getStorage('entity_test');
  }

  /**
   * Test transitions across DST.
   *
   * The data provider will give a before and after midnight for the day of
   * a transition in each direction. Depending on the time of year that the test
   * is run, we would expect different tests to fail if the handling is not
   * correct. All 4 scenarios must pass to indicate correct handling.
   *
   * @param string $created_base
   *   The base time for created. This should be the day of transition and the
   *   time we want to test (within 1 hour either side of midnight).
   *
   * @dataProvider dataDstTransition
   */
  public function testDstTransition(string $created_base) {
    // Get the time so we can force consistent hours across DST changes.
    $created_base = new DrupalDateTime($created_base);

    // Let's test to ensure the time zone is as expected, as a change in the
    // tests' default timezone could silently stop this test actually checking
    // what it needs to, as not all timezones have DST and the transition date
    // and direction varies depending on time zone.
    $this->assertSame('Australia/Sydney', $created_base->getTimezone()->getName(), 'Correct time zone for test');

    // Get the hour/minute so we can reset when adding/subtracting days across
    // the transition.
    $hour = $created_base->format('H');
    $minute = $created_base->format('i');

    // Create entities that span +/- 3 days around the transition.
    $this->storage
      ->create(['created' => $created_base->getTimestamp()])
      ->save();

    for ($i = 1; $i <= 3; $i++) {
      // Before the transition.
      $created = (clone $created_base)
        ->sub(new DateInterval("P{$i}D"))
        ->setTime($hour, $minute);
      $this->storage
        ->create(['created' => $created->getTimestamp()])
        ->save();

      // After the transition.
      $created = (clone $created_base)
        ->add(new DateInterval("P{$i}D"))
        ->setTime($hour, $minute);
      $this->storage
        ->create(['created' => $created->getTimestamp()])
        ->save();
    }

    // Load our view. It is an aggregated view that is sorted by date with a
    // granularity of 1 day and returns a count of matches for each day.
    $view = Views::getView('test_group_by_date');
    $view->execute();

    // We expect exactly one entity in each grouped date, with 7 results.
    $expected = [
      ['id' => 1],
      ['id' => 1],
      ['id' => 1],
      ['id' => 1],
      ['id' => 1],
      ['id' => 1],
      ['id' => 1],
    ];
    $column_map = ['id' => 'id'];
    $this->assertIdenticalResultset($view, $expected, $column_map, 'Date grouping works across DST transitions');
  }

  /**
   * Data provider for ::testDstTransition.
   */
  public function dataDstTransition() {
    // Before midnight across the end of DST in Australia/Sydney.
    yield 'before-end' => ['2020-10-04 23:30:00'];

    // After midnight across the end of DST in Australia/Sydney.
    yield 'after-end' => ['2020-10-04 00:30:00'];

    // Before midnight across the start of DST in Australia/Sydney.
    yield 'before-start' => ['2020-04-05 23:30:00'];

    // After midnight across the start of DST in Australia/Sydney.
    yield 'after-start' => ['2020-04-05 00:30:00'];
  }

}
