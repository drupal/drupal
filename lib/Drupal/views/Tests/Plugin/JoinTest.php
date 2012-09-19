<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Plugin\JoinTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views_test_data\Plugin\views\join\JoinTest as JoinTestPlugin;
use Drupal\views\Plugin\views\join\JoinPluginBase;


/**
 * Tests a generic join plugin and the join plugin base.
 */
class JoinTest extends PluginTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Join',
      'description' => 'Tests the join plugin.',
      'group' => 'Views Plugins',
    );
  }

  /**
   * Tests an example join plugin.
   */
  public function testExamplePlugin() {
    $join = drupal_container()->get('plugin.manager.views.join')->createInstance('join_test');
    $this->assertTrue($join instanceof JoinTestPlugin, 'The correct join class got loaded.');

    // Setup a simple join and test the result sql.
    $view = views_get_view('frontpage');
    $view->initDisplay();
    $view->initQuery();

    $definition = array(
      'left_table' => 'node',
      'left_field' => 'uid',
      'table' => 'users',
      'field' => 'uid',
    );
    $join->definition = $definition;
    $join->construct();

    $rand_int = rand(0, 1000);
    $join->setJoinValue($rand_int);

    $query = db_select('node');
    $table = array('alias' => 'users');
    $join->build_join($query, $table, $view->query);

    $tables = $query->getTables();
    $join_info = $tables['users'];
    $this->assertTrue(strpos($join_info['condition'], "node.uid = $rand_int") !== FALSE, 'Make sure that the custom join plugin can extend the join base and alter the result.');
  }

  /**
   * Tests the join plugin base.
   */
  public function testBasePlugin() {
    $join = drupal_container()->get('plugin.manager.views.join')->createInstance('standard');
    $this->assertTrue($join instanceof JoinPluginBase, 'The correct join class got loaded.');

    // Setup a simple join and test the result sql.
    $view = views_get_view('frontpage');
    $view->initDisplay();
    $view->initQuery();

    // First define a simple join without an extra condition.
    // Set the various options on the join object.
    $definition = array(
      'left_table' => 'node',
      'left_field' => 'uid',
      'table' => 'users',
      'field' => 'uid',
    );
    $join->definition = $definition;
    $join->construct();

    // Build the actual join values and read them back from the dbtng query
    // object.
    $query = db_select('node');
    $table = array('alias' => 'users');
    $join->build_join($query, $table, $view->query);

    $tables = $query->getTables();
    $join_info = $tables['users'];
    $this->assertEqual($join_info['join type'], 'LEFT', 'Make sure the default join type is LEFT');
    $this->assertEqual($join_info['table'], $definition['table']);
    $this->assertEqual($join_info['alias'], 'users');
    $this->assertEqual($join_info['condition'], 'node.uid = users.uid');

    // Set a different alias and make sure table info is as expected.
    $join = drupal_container()->get('plugin.manager.views.join')->createInstance('standard');
    $join->definition = $definition;
    $join->construct();
    $table = array('alias' => 'users1');
    $join->build_join($query, $table, $view->query);

    $tables = $query->getTables();
    $join_info = $tables['users1'];
    $this->assertEqual($join_info['alias'], 'users1');

    // Set a different join type (INNER) and make sure it is used.
    $join = drupal_container()->get('plugin.manager.views.join')->createInstance('standard');
    $definition['type'] = 'INNER';
    $join->definition = $definition;
    $join->construct();
    $table = array('alias' => 'users2');
    $join->build_join($query, $table, $view->query);

    $tables = $query->getTables();
    $join_info = $tables['users2'];
    $this->assertEqual($join_info['join type'], 'INNER');

    // Setup addition conditions and make sure it is used.
    $join = drupal_container()->get('plugin.manager.views.join')->createInstance('standard');
    $random_name_1 = $this->randomName();
    $random_name_2 = $this->randomName();
    $definition['extra'] = array(
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
    $join->definition = $definition;
    $join->construct();
    $table = array('alias' => 'users3');
    $join->build_join($query, $table, $view->query);

    $tables = $query->getTables();
    $join_info = $tables['users3'];
    $this->assertTrue(strpos($join_info['condition'], "users3.name = :views_join_condition_0") !== FALSE, 'Make sure the first extra join condition appears in the query and uses the first placeholder.');
    $this->assertTrue(strpos($join_info['condition'], "users3.name <> :views_join_condition_1") !== FALSE, 'Make sure the second extra join condition appears in the query and uses the second placeholder.');
    $this->assertEqual(array_values($join_info['arguments']), array($random_name_1, $random_name_2), 'Make sure the arguments are in the right order');
  }

}
