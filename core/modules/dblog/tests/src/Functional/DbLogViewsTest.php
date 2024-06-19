<?php

declare(strict_types=1);

namespace Drupal\Tests\dblog\Functional;

use Drupal\views\Views;

/**
 * Verifies user access to log reports based on permissions.
 *
 * @see Drupal\dblog\Tests\DbLogTest
 *
 * @group dblog
 * @group #slow
 */
class DbLogViewsTest extends DbLogTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'dblog',
    'node',
    'help',
    'block',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function getLogsEntriesTable() {
    return $this->xpath('.//div[contains(@class, "views-element-container")]//table/tbody/tr');
  }

  /**
   * {@inheritdoc}
   */
  protected function filterLogsEntries($type = NULL, $severity = NULL) {
    $query = [];
    if (isset($type)) {
      $query['type[]'] = $type;
    }
    if (isset($severity)) {
      $query['severity[]'] = $severity;
    }

    $this->drupalGet('admin/reports/dblog', ['query' => $query]);
  }

  /**
   * Tests the empty text for the watchdog view is not using an input format.
   */
  public function testEmptyText(): void {
    $view = Views::getView('watchdog');
    $data = $view->storage->toArray();
    $area = $data['display']['default']['display_options']['empty']['area'];

    $this->assertEquals('text_custom', $area['plugin_id']);
    $this->assertEquals('area_text_custom', $area['field']);
    $this->assertEquals('No log messages available.', $area['content']);
  }

}
