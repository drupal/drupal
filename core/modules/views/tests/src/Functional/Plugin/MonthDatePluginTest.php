<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Core\Url;
use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Tests the Month Date Plugin.
 *
 * @group views
 */
class MonthDatePluginTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_month_date_plugin'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test node 1.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node1;

  /**
   * Test node 2.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node2;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);
    $utc = new \DateTimeZone('UTC');
    $format = 'Y-m-d h:i:s';
    $this->node1 = $this->drupalCreateNode([
      'created' => \DateTime::createFromFormat($format, '2020-10-01 00:00:00', $utc)->getTimestamp(),
    ]);
    $this->node2 = $this->drupalCreateNode([
      'created' => \DateTime::createFromFormat($format, '2020-11-01 00:00:00', $utc)->getTimestamp(),
    ]);
  }

  /**
   * Tests the Month Date Plugin.
   */
  public function testMonthDatePlugin(): void {
    $assert_session = $this->assertSession();

    // Test fallback value.
    $this->drupalGet('test-month-date-plugin');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains($this->node1->getTitle());
    $assert_session->pageTextContains($this->node2->getTitle());

    // Test 'all' values.
    $this->drupalGet('test-month-date-plugin/all');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains($this->node1->getTitle());
    $assert_session->pageTextContains($this->node2->getTitle());

    // Test valid month value.
    $this->drupalGet('test-month-date-plugin/10');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains($this->node1->getTitle());
    $assert_session->pageTextNotContains($this->node2->getTitle());

    // Test query parameter.
    $url = Url::fromUserInput('/test-month-date-plugin', [
      'query' => [
        'month' => 10,
      ],
    ]);
    $this->drupalGet($url);
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains($this->node1->getTitle());
    $assert_session->pageTextNotContains($this->node2->getTitle());

    // Test invalid month name.
    $this->drupalGet('test-month-date-plugin/invalid-month');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextNotContains($this->node1->getTitle());
    $assert_session->pageTextNotContains($this->node2->getTitle());
  }

}
