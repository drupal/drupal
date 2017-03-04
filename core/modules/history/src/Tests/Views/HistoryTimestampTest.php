<?php

namespace Drupal\history\Tests\Views;

use Drupal\views\Views;
use Drupal\views\Tests\ViewTestBase;

/**
 * Tests the history timestamp handlers.
 *
 * @group history
 * @see \Drupal\history\Plugin\views\field\HistoryTimestamp.
 * @see \Drupal\history\Plugin\views\filter\HistoryTimestamp.
 */
class HistoryTimestampTest extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['history', 'node'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_history'];

  /**
   * Tests the handlers.
   */
  public function testHandlers() {
    $nodes = [];
    $nodes[] = $this->drupalCreateNode();
    $nodes[] = $this->drupalCreateNode();

    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    \Drupal::currentUser()->setAccount($account);

    db_insert('history')
      ->fields([
        'uid' => $account->id(),
        'nid' => $nodes[0]->id(),
        'timestamp' => REQUEST_TIME - 100,
      ])->execute();

    db_insert('history')
      ->fields([
        'uid' => $account->id(),
        'nid' => $nodes[1]->id(),
        'timestamp' => REQUEST_TIME + 100,
      ])->execute();


    $column_map = [
      'nid' => 'nid',
    ];

    // Test the history field.
    $view = Views::getView('test_history');
    $view->setDisplay('page_1');
    $this->executeView($view);
    $this->assertEqual(count($view->result), 2);
    $output = $view->preview();
    $this->setRawContent(\Drupal::service('renderer')->renderRoot($output));
    $result = $this->xpath('//span[@class=:class]', [':class' => 'marker']);
    $this->assertEqual(count($result), 1, 'Just one node is marked as new');

    // Test the history filter.
    $view = Views::getView('test_history');
    $view->setDisplay('page_2');
    $this->executeView($view);
    $this->assertEqual(count($view->result), 1);
    $this->assertIdenticalResultset($view, [['nid' => $nodes[0]->id()]], $column_map);

    // Install Comment module and make sure that content types without comment
    // field will not break the view.
    // See \Drupal\history\Plugin\views\filter\HistoryUserTimestamp::query()
    \Drupal::service('module_installer')->install(['comment']);
    $view = Views::getView('test_history');
    $view->setDisplay('page_2');
    $this->executeView($view);

  }

}
