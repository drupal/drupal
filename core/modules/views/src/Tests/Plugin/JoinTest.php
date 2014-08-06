<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Plugin\JoinTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views_test_data\Plugin\views\join\JoinTest as JoinTestPlugin;
use Drupal\views\Plugin\views\join\JoinPluginBase;
use Drupal\views\Views;


/**
 * Tests the join plugin.
 *
 * @group views
 * @see \Drupal\views_test_data\Plugin\views\join\JoinTest
 * @see \Drupal\views\Plugin\views\join\JoinPluginBase
 */
class JoinTest extends RelationshipJoinTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  /**
   * A plugin manager which handlers the instances of joins.
   *
   * @var \Drupal\views\Plugin\ViewsPluginManager
   */
  protected $manager;

  protected function setUp() {
    parent::setUp();

    // Add a join plugin manager which can be used in all of the tests.
    $this->manager = $this->container->get('plugin.manager.views.join');
  }

  /**
   * Tests an example join plugin.
   */
  public function testExamplePlugin() {

    // Setup a simple join and test the result sql.
    $view = Views::getView('test_view');
    $view->initDisplay();
    $view->initQuery();

    $configuration = array(
      'left_table' => 'views_test_data',
      'left_field' => 'uid',
      'table' => 'users',
      'field' => 'uid',
    );
    $join = $this->manager->createInstance('join_test', $configuration);
    $this->assertTrue($join instanceof JoinTestPlugin, 'The correct join class got loaded.');

    $rand_int = rand(0, 1000);
    $join->setJoinValue($rand_int);

    $query = db_select('views_test_data');
    $table = array('alias' => 'users');
    $join->buildJoin($query, $table, $view->query);

    $tables = $query->getTables();
    $join_info = $tables['users'];
    $this->assertTrue(strpos($join_info['condition'], "views_test_data.uid = $rand_int") !== FALSE, 'Make sure that the custom join plugin can extend the join base and alter the result.');
  }

  /**
   * Tests the join plugin base.
   */
  public function testBasePlugin() {

    // Setup a simple join and test the result sql.
    $view = Views::getView('test_view');
    $view->initDisplay();
    $view->initQuery();

    // First define a simple join without an extra condition.
    // Set the various options on the join object.
    $configuration = array(
      'left_table' => 'views_test_data',
      'left_field' => 'uid',
      'table' => 'users',
      'field' => 'uid',
      'adjusted' => TRUE,
    );
    $join = $this->manager->createInstance('standard', $configuration);
    $this->assertTrue($join instanceof JoinPluginBase, 'The correct join class got loaded.');
    $this->assertNull($join->extra, 'The field extra was not overriden.');
    $this->assertTrue($join->adjusted, 'The field adjusted was set correctly.');

    // Build the actual join values and read them back from the dbtng query
    // object.
    $query = db_select('views_test_data');
    $table = array('alias' => 'users');
    $join->buildJoin($query, $table, $view->query);

    $tables = $query->getTables();
    $join_info = $tables['users'];
    $this->assertEqual($join_info['join type'], 'LEFT', 'Make sure the default join type is LEFT');
    $this->assertEqual($join_info['table'], $configuration['table']);
    $this->assertEqual($join_info['alias'], 'users');
    $this->assertEqual($join_info['condition'], 'views_test_data.uid = users.uid');

    // Set a different alias and make sure table info is as expected.
    $join = $this->manager->createInstance('standard', $configuration);
    $table = array('alias' => 'users1');
    $join->buildJoin($query, $table, $view->query);

    $tables = $query->getTables();
    $join_info = $tables['users1'];
    $this->assertEqual($join_info['alias'], 'users1');

    // Set a different join type (INNER) and make sure it is used.
    $configuration['type'] = 'INNER';
    $join = $this->manager->createInstance('standard', $configuration);
    $table = array('alias' => 'users2');
    $join->buildJoin($query, $table, $view->query);

    $tables = $query->getTables();
    $join_info = $tables['users2'];
    $this->assertEqual($join_info['join type'], 'INNER');

    // Setup addition conditions and make sure it is used.
    $random_name_1 = $this->randomMachineName();
    $random_name_2 = $this->randomMachineName();
    $configuration['extra'] = array(
      array(
        'field' => 'name',
        'value' => $random_name_1
      ),
      array(
        'field' => 'name',
        'value' => $random_name_2,
        'operator' => '<>'
      )
    );
    $join = $this->manager->createInstance('standard', $configuration);
    $table = array('alias' => 'users3');
    $join->buildJoin($query, $table, $view->query);

    $tables = $query->getTables();
    $join_info = $tables['users3'];
    $this->assertTrue(strpos($join_info['condition'], "users3.name = :views_join_condition_0") !== FALSE, 'Make sure the first extra join condition appears in the query and uses the first placeholder.');
    $this->assertTrue(strpos($join_info['condition'], "users3.name <> :views_join_condition_1") !== FALSE, 'Make sure the second extra join condition appears in the query and uses the second placeholder.');
    $this->assertEqual(array_values($join_info['arguments']), array($random_name_1, $random_name_2), 'Make sure the arguments are in the right order');
  }

}
