<?php

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\Core\Database\Database;
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
  public static $testViews = ['test_view'];

  /**
   * A plugin manager which handlers the instances of joins.
   *
   * @var \Drupal\views\Plugin\ViewsPluginManager
   */
  protected $manager;

  protected function setUp($import_test_views = TRUE): void {
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

    $configuration = [
      'left_table' => 'views_test_data',
      'left_field' => 'uid',
      'table' => 'users_field_data',
      'field' => 'uid',
    ];
    $join = $this->manager->createInstance('join_test', $configuration);
    $this->assertInstanceOf(JoinTestPlugin::class, $join);

    $rand_int = rand(0, 1000);
    $join->setJoinValue($rand_int);

    $query = Database::getConnection()->select('views_test_data');
    $table = ['alias' => 'users_field_data'];
    $join->buildJoin($query, $table, $view->query);

    $tables = $query->getTables();
    $join_info = $tables['users_field_data'];
    $this->assertStringContainsString("views_test_data.uid = $rand_int", $join_info['condition'], 'Make sure that the custom join plugin can extend the join base and alter the result.');
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
    $configuration = [
      'left_table' => 'views_test_data',
      'left_field' => 'uid',
      'table' => 'users_field_data',
      'field' => 'uid',
      'adjusted' => TRUE,
    ];
    $join = $this->manager->createInstance('standard', $configuration);
    $this->assertInstanceOf(JoinPluginBase::class, $join);
    $this->assertNull($join->extra, 'The field extra was not overridden.');
    $this->assertTrue($join->adjusted, 'The field adjusted was set correctly.');

    // Build the actual join values and read them back from the dbtng query
    // object.
    $query = Database::getConnection()->select('views_test_data');
    $table = ['alias' => 'users_field_data'];
    $join->buildJoin($query, $table, $view->query);

    $tables = $query->getTables();
    $join_info = $tables['users_field_data'];
    $this->assertEquals('LEFT', $join_info['join type'], 'Make sure the default join type is LEFT');
    $this->assertEquals($configuration['table'], $join_info['table']);
    $this->assertEquals('users_field_data', $join_info['alias']);
    $this->assertEquals('views_test_data.uid = users_field_data.uid', $join_info['condition']);

    // Set a different alias and make sure table info is as expected.
    $join = $this->manager->createInstance('standard', $configuration);
    $table = ['alias' => 'users1'];
    $join->buildJoin($query, $table, $view->query);

    $tables = $query->getTables();
    $join_info = $tables['users1'];
    $this->assertEquals('users1', $join_info['alias']);

    // Set a different join type (INNER) and make sure it is used.
    $configuration['type'] = 'INNER';
    $join = $this->manager->createInstance('standard', $configuration);
    $table = ['alias' => 'users2'];
    $join->buildJoin($query, $table, $view->query);

    $tables = $query->getTables();
    $join_info = $tables['users2'];
    $this->assertEquals('INNER', $join_info['join type']);

    // Setup addition conditions and make sure it is used.
    $random_name_1 = $this->randomMachineName();
    $random_name_2 = $this->randomMachineName();
    $configuration['extra'] = [
      [
        'field' => 'name',
        'value' => $random_name_1,
      ],
      [
        'field' => 'name',
        'value' => $random_name_2,
        'operator' => '<>',
      ],
    ];
    $join = $this->manager->createInstance('standard', $configuration);
    $table = ['alias' => 'users3'];
    $join->buildJoin($query, $table, $view->query);

    $tables = $query->getTables();
    $join_info = $tables['users3'];
    $this->assertStringContainsString("views_test_data.uid = users3.uid", $join_info['condition'], 'Make sure the join condition appears in the query.');
    $this->assertStringContainsString("users3.name = :views_join_condition_0", $join_info['condition'], 'Make sure the first extra join condition appears in the query and uses the first placeholder.');
    $this->assertStringContainsString("users3.name <> :views_join_condition_1", $join_info['condition'], 'Make sure the second extra join condition appears in the query and uses the second placeholder.');
    $this->assertEquals([$random_name_1, $random_name_2], array_values($join_info['arguments']), 'Make sure the arguments are in the right order');

    // Test that 'IN' conditions are properly built.
    $random_name_1 = $this->randomMachineName();
    $random_name_2 = $this->randomMachineName();
    $random_name_3 = $this->randomMachineName();
    $random_name_4 = $this->randomMachineName();
    $configuration['extra'] = [
      [
        'field' => 'name',
        'value' => $random_name_1,
      ],
      [
        'field' => 'name',
        'value' => [$random_name_2, $random_name_3, $random_name_4],
      ],
    ];
    $join = $this->manager->createInstance('standard', $configuration);
    $table = ['alias' => 'users4'];
    $join->buildJoin($query, $table, $view->query);

    $tables = $query->getTables();
    $join_info = $tables['users4'];
    $this->assertStringContainsString("views_test_data.uid = users4.uid", $join_info['condition'], 'Make sure the join condition appears in the query.');
    $this->assertStringContainsString("users4.name = :views_join_condition_2", $join_info['condition'], 'Make sure the first extra join condition appears in the query.');
    $this->assertStringContainsString("users4.name IN ( :views_join_condition_3[] )", $join_info['condition'], 'The IN condition for the join is properly formed.');
    $this->assertEquals([$random_name_2, $random_name_3, $random_name_4], $join_info['arguments'][':views_join_condition_3[]'], 'Make sure the IN arguments are still part of an array.');

    // Test that all the conditions are properly built.
    $configuration['extra'] = [
      [
        'field' => 'langcode',
        'value' => 'en',
      ],
      [
        'left_field' => 'status',
        'value' => 0,
        'numeric' => TRUE,
      ],
      [
        'field' => 'name',
        'left_field' => 'name',
      ],
    ];
    $join = $this->manager->createInstance('standard', $configuration);
    $table = ['alias' => 'users5'];
    $join->buildJoin($query, $table, $view->query);

    $tables = $query->getTables();
    $join_info = $tables['users5'];
    $this->assertStringContainsString("views_test_data.uid = users5.uid", $join_info['condition'], 'Make sure the join condition appears in the query.');
    $this->assertStringContainsString("users5.langcode = :views_join_condition_4", $join_info['condition'], 'Make sure the first extra join condition appears in the query.');
    $this->assertStringContainsString("views_test_data.status = :views_join_condition_5", $join_info['condition'], 'Make sure the second extra join condition appears in the query.');
    $this->assertStringContainsString("users5.name = views_test_data.name", $join_info['condition'], 'Make sure the third extra join condition appears in the query.');
    $this->assertEquals(['en', 0], array_values($join_info['arguments']), 'Make sure the arguments are in the right order');

    // Test that joins using 'left_formula' are properly built.
    $configuration['left_formula'] = 'MAX(views_test_data.uid)';
    // When 'left_formula' is present, 'left_field' is no longer required.
    unset($configuration['left_field']);
    $join = $this->manager->createInstance('standard', $configuration);
    $table = ['alias' => 'users6'];
    $join->buildJoin($query, $table, $view->query);

    $tables = $query->getTables();
    $join_info = $tables['users6'];
    $this->assertStringContainsString("MAX(views_test_data.uid) = users6.uid", $join_info['condition'], 'Make sure the join condition appears in the query.');
    $this->assertStringContainsString("users6.langcode = :views_join_condition_7", $join_info['condition'], 'Make sure the first extra join condition appears in the query.');
    $this->assertStringContainsString("views_test_data.status = :views_join_condition_8", $join_info['condition'], 'Make sure the second extra join condition appears in the query.');
    $this->assertStringContainsString("users6.name = views_test_data.name", $join_info['condition'], 'Make sure the third extra join condition appears in the query.');
    $this->assertEquals(['en', 0], array_values($join_info['arguments']), 'Make sure the arguments are in the right order');
  }

}
