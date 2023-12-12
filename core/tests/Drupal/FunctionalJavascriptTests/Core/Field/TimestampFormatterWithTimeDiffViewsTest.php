<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\Core\Field;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the timestamp formatter used with time difference setting in views.
 *
 * @group Field
 */
class TimestampFormatterWithTimeDiffViewsTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'views_test_formatter'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used in test.
   *
   * @var string[]
   */
  public static $testViews = ['formatter_timestamp_as_time_diff'];

  /**
   * Tests the timestamp formatter used with time difference setting in views.
   */
  public function testTimestampFormatterWithTimeDiff(): void {
    $this->markTestSkipped("Skipped due to frequent random test failures. See https://www.drupal.org/project/drupal/issues/3400150");
    ViewTestData::createTestViews(self::class, ['views_test_formatter']);

    $data = $this->getRowData();

    // PHPStan requires non-empty data. Without this check complains, later,
    // that $delta and $time_diff might not be defined.
    \assert(!empty($data));

    // Create the entities.
    foreach ($data as $delta => $row) {
      EntityTest::create([
        'type' => 'test',
        // Using this also as field class.
        'name' => "entity-$delta",
        'created' => $row['timestamp'],
      ])->save();
    }
    $this->drupalGet('formatter_timestamp_as_time_diff');

    $page = $this->getSession()->getPage();
    foreach ($data as $delta => $row) {
      $time_diff = $page->find('css', ".entity-$delta")->getText();
      $regex_pattern = "#{$row['pattern']}#";
      // Test that the correct time difference is displayed. Note that we are
      // able to check an exact match for rows that have a creation date more
      // distant, but we use regexp to check the entities that are only few
      // seconds away because of the latency introduced by the test run.
      $this->assertMatchesRegularExpression($regex_pattern, $time_diff);
    }

    // Wait at least 1 second + 1 millisecond to make sure the 'right now' time
    // difference was refreshed.
    $this->assertJsCondition("document.querySelector('.entity-$delta time').textContent >= '$time_diff'", 1001);
  }

  /**
   * Provides data for view rows.
   *
   * @return array[]
   *   A list of row data.
   */
  protected function getRowData(): array {
    $now = \Drupal::time()->getRequestTime();
    return [
      // One year ago.
      [
        'pattern' => '1 year ago',
        'timestamp' => $now - (60 * 60 * 24 * 365),
      ],
      // One month ago.
      [
        'pattern' => '1 month ago',
        'timestamp' => $now - (60 * 60 * 24 * 30),
      ],
      // One week ago.
      [
        'pattern' => '1 week ago',
        'timestamp' => $now - (60 * 60 * 24 * 7),
      ],
      // One day ago.
      [
        'pattern' => '1 day ago',
        'timestamp' => $now - (60 * 60 * 24),
      ],
      // One hour ago.
      [
        'pattern' => '1 hour ago',
        'timestamp' => $now - (60 * 60),
      ],
      // One minute ago.
      [
        'pattern' => '\d+ minute[s]?(?: \d+ second[s]?)? ago',
        'timestamp' => $now - 60,
      ],
      // One minute hence.
      [
        'pattern' => '\d+ second[s]?[ hence]?',
        'timestamp' => $now + 60,
      ],
      // One hour hence.
      [
        'pattern' => '59 minutes \d+ second[s]? hence',
        'timestamp' => $now + (60 * 60),
      ],
      // One day hence.
      [
        'pattern' => '23 hours 59 minutes hence',
        'timestamp' => $now + (60 * 60 * 24),
      ],
      // One week hence.
      [
        'pattern' => '6 days 23 hours hence',
        'timestamp' => $now + (60 * 60 * 24 * 7),
      ],
      // A little more than 1 year hence (one year + 1 hour).
      [
        'pattern' => '1 year hence',
        'timestamp' => $now + (60 * 60 * 24 * 365) + (60 * 60),
      ],
      // Right now.
      [
        'pattern' => '\d+ second[s]?[ ago]?',
        'timestamp' => $now,
      ],
    ];
  }

}
