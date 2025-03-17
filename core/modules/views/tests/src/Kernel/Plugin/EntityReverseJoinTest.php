<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\views\Views;
use Drupal\views_test_data\Plugin\views\join\JoinTest;

/**
 * Tests the EntityReverse join plugin.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\relationship\EntityReverse
 */
class EntityReverseJoinTest extends RelationshipJoinTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_view'];

  /**
   * Tests that the EntityReverse plugin loads the correct join plugin.
   */
  public function testJoinThroughRelationship(): void {
    $relationship_manager = $this->container->get('plugin.manager.views.relationship');
    // Setup a simple join and test the result sql.
    $view = Views::getView('test_view');
    $view->initDisplay();
    $view->initQuery();

    $configuration = [
      'id' => 'entity_reverse',
      'base' => 'users_field_data',
      'table' => 'users_field_data',
      'field table' => 'users_field_data',
      'field field' => 'uid',
      'base field' => 'uid',
      'field_name' => 'uid',
      'join_id' => 'join_test',
    ];

    $relationship = $relationship_manager->createInstance('entity_reverse', $configuration);
    $relationship->tableAlias = 'users_field_data';
    $relationship->table = 'users_field_data';
    $relationship->query = $view->getQuery();
    $relationship->query();
    $this->assertInstanceOf(JoinTest::class, $relationship->query->getTableQueue()[$relationship->alias]['join']);
  }

}
