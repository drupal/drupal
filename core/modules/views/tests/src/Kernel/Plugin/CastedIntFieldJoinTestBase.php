<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\Core\Database\DriverSpecificKernelTestBase;
use Drupal\user\Entity\User;
use Drupal\views\Plugin\views\join\CastedIntFieldJoin;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;
use Drupal\views\ViewExecutable;

/**
 * Tests the "casted_int_field_join" join plugin.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\join\CastedIntFieldJoin
 */
abstract class CastedIntFieldJoinTestBase extends DriverSpecificKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'views',
    'views_test_config',
  ];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_view'];

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'casted_int_field_join';

  /**
   * A plugin manager which handles the instances of joins.
   *
   * @var \Drupal\views\Plugin\ViewsPluginManager
   */
  protected $manager;

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $rootUser;

  /**
   * The db type that should be used for casting fields as integers.
   *
   * Needs to be overridden by the extending test for each specific engine.
   */
  protected string $castingType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpFixtures();

    ViewTestData::createTestViews(self::class, ['views_test_config']);

    Views::viewsData()->clear();

    // Add a join plugin manager which can be used in all of the tests.
    $this->manager = $this->container->get('plugin.manager.views.join');
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpFixtures() {
    $this->installEntitySchema('user');
    $this->installConfig(['user']);

    // Create a record for uid 1.
    $this->rootUser = User::create(['name' => $this->randomMachineName()]);
    $this->rootUser->save();

  }

  /**
   * Tests base join functionality.
   *
   * This duplicates parts of
   * \Drupal\Tests\views\Kernel\Plugin\JoinTest::testBasePlugin() to ensure that
   * no functionality provided by the base join plugin is broken.
   */
  public function testBuildJoin(): void {
    // Setup a simple join and test the result sql.
    $view = Views::getView('test_view');
    $view->initDisplay();
    $view->initQuery();

    $connection = Database::getConnection();

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
    $this->assertInstanceOf(CastedIntFieldJoin::class, $join);
    $this->assertNull($join->extra);
    $this->assertTrue($join->adjusted);

    $join_info = $this->buildJoin($view, $configuration, 'users_field_data');
    $this->assertSame($join_info['join type'], 'LEFT');
    $this->assertSame($join_info['table'], $configuration['table']);
    $this->assertSame($join_info['alias'], 'users_field_data');
    $this->assertSame($join_info['condition'], "views_test_data.uid = CAST(users_field_data.uid AS $this->castingType)");

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
    $configuration['cast'] = 'left';
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
    $this->assertStringContainsString("CAST(views_test_data.uid AS $this->castingType) = users3.uid", $join_info['condition']);
    $this->assertStringContainsString('users3.name = :views_join_condition_0', $join_info['condition']);
    $this->assertStringContainsString('users3.name <> :views_join_condition_1', $join_info['condition']);
    $this->assertSame(array_values($join_info['arguments']), [$random_name_1, $random_name_2]);

    // Test that 'IN' conditions are properly built.
    $random_name_1 = $this->randomMachineName();
    $random_name_2 = $this->randomMachineName();
    $random_name_3 = $this->randomMachineName();
    $random_name_4 = $this->randomMachineName();
    $configuration['cast'] = 'right';
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
    $this->assertStringContainsString("views_test_data.uid = CAST(users4.uid AS $this->castingType)", $join_info['condition']);
    $this->assertStringContainsString('users4.name = :views_join_condition_0', $join_info['condition']);
    $this->assertStringContainsString('users4.name IN ( :views_join_condition_1[] )', $join_info['condition']);
    $this->assertSame($join_info['arguments'][':views_join_condition_1[]'], [$random_name_2, $random_name_3, $random_name_4]);
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
  protected function buildJoin(ViewExecutable $view, $configuration, $table_alias) {
    // Build the actual join values and read them back from the query object.
    $query = \Drupal::database()->select('node');

    $join = $this->manager->createInstance($this->pluginId, $configuration);
    $this->assertInstanceOf(CastedIntFieldJoin::class, $join);

    $table = ['alias' => $table_alias];
    $join->buildJoin($query, $table, $view->query);

    $tables = $query->getTables();
    $join_info = $tables[$table['alias']];
    return $join_info;
  }

}
