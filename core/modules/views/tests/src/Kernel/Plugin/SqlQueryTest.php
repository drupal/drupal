<?php

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\Core\Database\Database;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the sql query plugin.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\query\Sql
 */
class SqlQueryTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * {@inheritdoc}
   */
  protected function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['table']['base']['access query tag'] = 'test_tag';
    $data['views_test_data']['table']['base']['query metadata'] = ['key1' => 'test_metadata', 'key2' => 'test_metadata2'];

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

    foreach ([$main_query, $count_query] as $query) {
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

    foreach ([$main_query, $count_query] as $query) {
      // Check query access tags.
      $this->assertFalse($query->hasTag('test_tag'));

      // Check metadata.
      $this->assertIdentical($query->getMetaData('key1'), NULL);
      $this->assertIdentical($query->getMetaData('key2'), NULL);
    }
  }

  /**
   * Tests the method \Drupal\views\Plugin\views\query\Sql::getConnection().
   *
   * @covers \Drupal\views\Plugin\views\query\Sql::getConnection
   *
   * This needs to be a kernel test because the tested method uses the method
   * \Drupal\Core\Database\Database::getConnection() which is a 'final' method
   * and therefore cannot be mocked.
   */
  public function testGetConnection() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Add 3 database connections for the different options that the method
    // getConnection() supports.
    $connection_info = Database::getConnectionInfo('default');
    Database::addConnectionInfo('default', 'replica', $connection_info['default']);
    Database::addConnectionInfo('corefake', 'default', $connection_info['default']);
    Database::addConnectionInfo('corefake', 'replica', $connection_info['default']);

    // Test the database connection with no special options set.
    $this->assertSame('default', $view->getQuery()->getConnection()->getKey());
    $this->assertSame('default', $view->getQuery()->getConnection()->getTarget());

    // Test the database connection with the option 'replica' set to TRUE;
    $view->getQuery()->options['replica'] = TRUE;
    $this->assertSame('default', $view->getQuery()->getConnection()->getKey());
    $this->assertSame('replica', $view->getQuery()->getConnection()->getTarget());

    // Test the database connection with the view 'base_database' set.
    $view->getQuery()->options['replica'] = FALSE;
    $view->base_database = 'corefake';
    $this->assertSame('corefake', $view->getQuery()->getConnection()->getKey());
    $this->assertSame('default', $view->getQuery()->getConnection()->getTarget());

    // Test the database connection with the view 'base_database' set and the
    // option 'replica' set to TRUE.
    $view->getQuery()->options['replica'] = TRUE;
    $this->assertSame('corefake', $view->getQuery()->getConnection()->getKey());
    $this->assertSame('replica', $view->getQuery()->getConnection()->getTarget());

    // Clean up the created database connections.
    Database::closeConnection('replica', 'default');
    Database::closeConnection('default', 'corefake');
    Database::closeConnection('replica', 'corefake');
  }

}
