<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\SqlQueryTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Views;

/**
 * Tests the sql query plugin.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\query\Sql
 */
class SqlQueryTest extends ViewUnitTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  /**
   * {@inheritdoc}
   */
  protected function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['table']['base']['access query tag'] = 'test_tag';
    $data['views_test_data']['table']['base']['query metadata'] = array('key1' => 'test_metadata', 'key2' => 'test_metadata2');

    return $data;
  }

  /**
   * Tests adding some metadata/tags to the views query.
   */
  public function testExecuteMetadata() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $view->initQuery();
    $view->execute();
    /** @var \Drupal\Core\Database\Query\Select $query */
    $main_query = $view->build_info['query'];
    /** @var \Drupal\Core\Database\Query\Select $count_query */
    $count_query = $view->build_info['count_query'];

    foreach (array($main_query, $count_query) as $query) {
      // Check query access tags.
      $this->assertTrue($query->hasTag('test_tag'));

      // Check metadata.
      $this->assertIdentical($query->getMetaData('key1'), 'test_metadata');
      $this->assertIdentical($query->getMetaData('key2'), 'test_metadata2');
    }

    $query_options = $view->display_handler->getOption('query');
    $query_options['options']['disable_sql_rewrite'] = TRUE;
    $view->display_handler->setOption('query', $query_options);
    $view->save();
    $view->destroy();

    $view = Views::getView('test_view');
    $view->setDisplay();
    $view->initQuery();
    $view->execute();
    /** @var \Drupal\Core\Database\Query\Select $query */
    $main_query = $view->build_info['query'];
    /** @var \Drupal\Core\Database\Query\Select $count_query */
    $count_query = $view->build_info['count_query'];

    foreach (array($main_query, $count_query) as $query) {
      // Check query access tags.
      $this->assertFalse($query->hasTag('test_tag'));

      // Check metadata.
      $this->assertIdentical($query->getMetaData('key1'), NULL);
      $this->assertIdentical($query->getMetaData('key2'), NULL);
    }
  }

}
