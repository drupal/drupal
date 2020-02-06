<?php

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\views\Plugin\views\join\FieldOrLanguageJoin;
use Drupal\views\Views;

/**
 * Tests the "field OR language" join plugin.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\join\FieldOrLanguageJoin
 */
class FieldOrLanguageJoinTest extends RelationshipJoinTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_view'];

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'field_or_language_join';

  /**
   * A plugin manager which handlers the instances of joins.
   *
   * @var \Drupal\views\Plugin\ViewsPluginManager
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp();

    // Add a join plugin manager which can be used in all of the tests.
    $this->manager = $this->container->get('plugin.manager.views.join');
  }

  /**
   * Tests base join functionality.
   *
   * This duplicates parts of
   * \Drupal\Tests\views\Kernel\Plugin\JoinTest::testBasePlugin() to ensure that
   * no functionality provided by the base join plugin is broken.
   */
  public function testBase() {
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
    $join = $this->manager->createInstance($this->pluginId, $configuration);
    $this->assertTrue($join instanceof FieldOrLanguageJoin);
    $this->assertNull($join->extra);
    $this->assertTrue($join->adjusted);

    $join_info = $this->buildJoin($view, $configuration, 'users_field_data');
    $this->assertSame($join_info['join type'], 'LEFT');
    $this->assertSame($join_info['table'], $configuration['table']);
    $this->assertSame($join_info['alias'], 'users_field_data');
    $this->assertSame($join_info['condition'], 'views_test_data.uid = users_field_data.uid');

    // Set a different alias and make sure table info is as expected.
    $join_info = $this->buildJoin($view, $configuration, 'users1');
    $this->assertSame($join_info['alias'], 'users1');

    // Set a different join type (INNER) and make sure it is used.
    $configuration['type'] = 'INNER';
    $join_info = $this->buildJoin($view, $configuration, 'users2');
    $this->assertSame($join_info['join type'], 'INNER');

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
    $join_info = $this->buildJoin($view, $configuration, 'users3');
    $this->assertContains('views_test_data.uid = users3.uid', $join_info['condition']);
    $this->assertContains('users3.name = :views_join_condition_0', $join_info['condition']);
    $this->assertContains('users3.name <> :views_join_condition_1', $join_info['condition']);
    $this->assertSame(array_values($join_info['arguments']), [$random_name_1, $random_name_2]);

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
    $join_info = $this->buildJoin($view, $configuration, 'users4');
    $this->assertContains('views_test_data.uid = users4.uid', $join_info['condition']);
    $this->assertContains('users4.name = :views_join_condition_0', $join_info['condition']);
    $this->assertContains('users4.name IN ( :views_join_condition_1[] )', $join_info['condition']);
    $this->assertSame($join_info['arguments'][':views_join_condition_1[]'], [$random_name_2, $random_name_3, $random_name_4]);
  }

  /**
   * Tests the adding of conditions by the join plugin.
   */
  public function testLanguageBundleConditions() {
    // Setup a simple join and test the result sql.
    $view = Views::getView('test_view');
    $view->initDisplay();
    $view->initQuery();

    // Set the various options on the join object with only a langcode
    // condition.
    $configuration = [
      'table' => 'node__field_tags',
      'left_table' => 'views_test_data',
      'left_field' => 'nid',
      'field' => 'entity_id',
      'extra' => [
        [
          'left_field' => 'langcode',
          'field' => 'langcode',
        ],
      ],
    ];
    $join_info = $this->buildJoin($view, $configuration, 'node__field_tags');
    $this->assertContains('AND (node__field_tags.langcode = views_test_data.langcode)', $join_info['condition']);

    array_unshift($configuration['extra'], [
      'field' => 'deleted',
      'value' => 0,
      'numeric' => TRUE,
    ]);
    $join_info = $this->buildJoin($view, $configuration, 'node__field_tags');
    $this->assertContains('AND (node__field_tags.langcode = views_test_data.langcode)', $join_info['condition']);

    // Replace the language condition with a bundle condition.
    $configuration['extra'][1] = [
      'field' => 'bundle',
      'value' => ['page'],
    ];
    $join_info = $this->buildJoin($view, $configuration, 'node__field_tags');
    $this->assertContains('AND (node__field_tags.bundle = :views_join_condition_1)', $join_info['condition']);

    // Now re-add a language condition to make sure the bundle and language
    // conditions are combined with an OR.
    $configuration['extra'][] = [
      'left_field' => 'langcode',
      'field' => 'langcode',
    ];
    $join_info = $this->buildJoin($view, $configuration, 'node__field_tags');
    $this->assertContains('AND (node__field_tags.bundle = :views_join_condition_1 OR node__field_tags.langcode = views_test_data.langcode)', $join_info['condition']);
  }

  /**
   * Builds a join using the given configuration.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view used in this test.
   * @param $configuration
   *   The join plugin configuration.
   * @param $table_alias
   *   The table alias to use for the join.
   *
   * @return array
   *   The join information for the joined table. See
   *   \Drupal\Core\Database\Query\Select::$tables for more information on the
   *   structure of the array.
   */
  protected function buildJoin($view, $configuration, $table_alias) {
    // Build the actual join values and read them back from the query object.
    $query = \Drupal::database()->select('node');

    $join = $this->manager->createInstance('field_or_language_join', $configuration);
    $this->assertInstanceOf(FieldOrLanguageJoin::class, $join, 'The correct join class got loaded.');

    $table = ['alias' => $table_alias];
    $join->buildJoin($query, $table, $view->query);

    $tables = $query->getTables();
    $join_info = $tables[$table['alias']];
    return $join_info;
  }

}
