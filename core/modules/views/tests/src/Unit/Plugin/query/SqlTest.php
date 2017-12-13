<?php

namespace Drupal\Tests\views\Unit\Plugin\query;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\query\DateSqlInterface;
use Drupal\views\Plugin\views\query\Sql;
use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewEntityInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\ViewsData;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\views\Plugin\views\query\Sql
 *
 * @group views
 */
class SqlTest extends UnitTestCase {

  /**
   * @covers ::getCacheTags
   * @covers ::getAllEntities
   */
  public function testGetCacheTags() {
    $view = $this->prophesize('Drupal\views\ViewExecutable')->reveal();
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $date_sql = $this->prophesize(DateSqlInterface::class);

    $query = new Sql([], 'sql', [], $entity_type_manager->reveal(), $date_sql->reveal());
    $query->view = $view;

    $result = [];
    $view->result = $result;

    // Add a row with an entity.
    $row = new ResultRow();
    $prophecy = $this->prophesize('Drupal\Core\Entity\EntityInterface');
    $prophecy->getCacheTags()->willReturn(['entity_test:123']);
    $entity = $prophecy->reveal();
    $row->_entity = $entity;

    $result[] = $row;
    $view->result = $result;

    // Add a row with an entity and a relationship entity.
    $row = new ResultRow();
    $prophecy = $this->prophesize('Drupal\Core\Entity\EntityInterface');
    $prophecy->getCacheTags()->willReturn(['entity_test:124']);
    $entity = $prophecy->reveal();
    $row->_entity = $entity;

    $prophecy = $this->prophesize('Drupal\Core\Entity\EntityInterface');
    $prophecy->getCacheTags()->willReturn(['entity_test:125']);
    $entity = $prophecy->reveal();
    $row->_relationship_entities[] = $entity;
    $prophecy = $this->prophesize('Drupal\Core\Entity\EntityInterface');
    $prophecy->getCacheTags()->willReturn(['entity_test:126']);
    $entity = $prophecy->reveal();
    $row->_relationship_entities[] = $entity;

    $result[] = $row;
    $view->result = $result;

    $this->assertEquals(['entity_test:123', 'entity_test:124', 'entity_test:125', 'entity_test:126'], $query->getCacheTags());
  }

  /**
   * @covers ::getCacheTags
   * @covers ::getAllEntities
   */
  public function testGetCacheMaxAge() {
    $view = $this->prophesize('Drupal\views\ViewExecutable')->reveal();
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $date_sql = $this->prophesize(DateSqlInterface::class);

    $query = new Sql([], 'sql', [], $entity_type_manager->reveal(), $date_sql->reveal());
    $query->view = $view;

    $view->result = [];

    // Add a row with an entity.
    $row = new ResultRow();
    $prophecy = $this->prophesize('Drupal\Core\Entity\EntityInterface');
    $prophecy->getCacheMaxAge()->willReturn(10);
    $entity = $prophecy->reveal();

    $row->_entity = $entity;
    $view->result[] = $row;

    // Add a row with an entity and a relationship entity.
    $row = new ResultRow();
    $prophecy = $this->prophesize('Drupal\Core\Entity\EntityInterface');
    $prophecy->getCacheMaxAge()->willReturn(20);
    $entity = $prophecy->reveal();
    $row->_entity = $entity;

    $prophecy = $this->prophesize('Drupal\Core\Entity\EntityInterface');
    $prophecy->getCacheMaxAge()->willReturn(30);
    $entity = $prophecy->reveal();
    $row->_relationship_entities[] = $entity;
    $prophecy = $this->prophesize('Drupal\Core\Entity\EntityInterface');
    $prophecy->getCacheMaxAge()->willReturn(40);
    $entity = $prophecy->reveal();
    $row->_relationship_entities[] = $entity;

    $this->assertEquals(10, $query->getCacheMaxAge());
  }

  /**
   * Sets up the views data in the container.
   *
   * @param \Drupal\views\ViewsData $views_data
   *   The views data.
   */
  protected function setupViewsData(ViewsData $views_data) {
    $container = \Drupal::hasContainer() ? \Drupal::getContainer() : new ContainerBuilder();
    $container->set('views.views_data', $views_data);
    \Drupal::setContainer($container);
  }

  /**
   * Sets up the entity type manager in the container.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  protected function setupEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $container = \Drupal::hasContainer() ? \Drupal::getContainer() : new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity.manager', $entity_type_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Sets up some test entity types and corresponding views data.
   *
   * @param \Drupal\Core\Entity\EntityInterface[][] $entities_by_type
   *   Test entities keyed by entity type and entity ID.
   * @param \Drupal\Core\Entity\EntityInterface[][] $entity_revisions_by_type
   *   Test entities keyed by entity type and revision ID.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy
   */
  protected function setupEntityTypes($entities_by_type = [], $entity_revisions_by_type = []) {
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type0 = new EntityType([
      'label' => 'First',
      'id' => 'first',
      'base_table' => 'entity_first',
      'revision_table' => 'entity_first__revision',
      'entity_keys' => [
        'id' => 'id',
        'revision' => 'vid',
      ],
    ]);
    $entity_type1 = new EntityType([
      'label' => 'second',
      'id' => 'second',
      'base_table' => 'entity_second',
      'revision_table' => 'entity_second__revision',
      'entity_keys' => [
        'id' => 'id',
        'revision' => 'vid',
      ],
    ]);

    $entity_type_manager->getDefinitions()->willReturn([
      'first' => $entity_type0,
      'second' => $entity_type1,
      'base_table' => 'entity_second',
    ]);

    $entity_type_manager->getDefinition('first')->willReturn($entity_type0);
    $entity_type_manager->getDefinition('second')->willReturn($entity_type1);

    // Setup the views data corresponding to the entity types.
    $views_data = $this->prophesize(ViewsData::class);
    $views_data->get('entity_first')->willReturn([
      'table' => [
        'entity type' => 'first',
        'entity revision' => FALSE,
      ],
    ]);
    $views_data->get('entity_first__revision')->willReturn([
      'table' => [
        'entity type' => 'first',
        'entity revision' => TRUE,
      ],
    ]);
    $views_data->get('entity_second')->willReturn([
      'table' => [
        'entity type' => 'second',
        'entity revision' => FALSE,
      ],
    ]);
    $views_data->get('entity_second__revision')->willReturn([
      'table' => [
        'entity type' => 'second',
        'entity revision' => TRUE,
      ],
    ]);
    $views_data->get('entity_first_field_data')->willReturn([
      'table' => [
        'entity type' => 'first',
        'entity revision' => FALSE,
      ],
    ]);
    $this->setupViewsData($views_data->reveal());

    // Setup the loading of entities and entity revisions.
    $entity_storages = [
      'first' => $this->prophesize(EntityStorageInterface::class),
      'second' => $this->prophesize(EntityStorageInterface::class),
    ];

    foreach ($entities_by_type as $entity_type_id => $entities) {
      foreach ($entities as $entity_id => $entity) {
        $entity_storages[$entity_type_id]->load($entity_id)->willReturn($entity);
      }
      $entity_storages[$entity_type_id]->loadMultiple(array_keys($entities))->willReturn($entities);
    }

    foreach ($entity_revisions_by_type as $entity_type_id => $entity_revisions) {
      foreach ($entity_revisions as $revision_id => $revision) {
        $entity_storages[$entity_type_id]->loadRevision($revision_id)->willReturn($revision);
      }
    }

    $entity_type_manager->getStorage('first')->willReturn($entity_storages['first']);
    $entity_type_manager->getStorage('second')->willReturn($entity_storages['second']);

    $this->setupEntityTypeManager($entity_type_manager->reveal());

    return $entity_type_manager;
  }

  /**
   * @covers ::loadEntities
   * @covers ::assignEntitiesToResult
   */
  public function testLoadEntitiesWithEmptyResult() {
    $view = $this->prophesize('Drupal\views\ViewExecutable')->reveal();
    $view_entity = $this->prophesize(ViewEntityInterface::class);
    $view_entity->get('base_table')->willReturn('entity_first');
    $view_entity->get('base_field')->willReturn('id');
    $view->storage = $view_entity->reveal();

    $entity_type_manager = $this->setupEntityTypes();
    $date_sql = $this->prophesize(DateSqlInterface::class);

    $query = new Sql([], 'sql', [], $entity_type_manager->reveal(), $date_sql->reveal());
    $query->view = $view;

    $result = [];
    $query->addField('entity_first', 'id', 'id');
    $query->loadEntities($result);
    $this->assertEmpty($result);
  }

  /**
   * @covers ::loadEntities
   * @covers ::assignEntitiesToResult
   */
  public function testLoadEntitiesWithNoRelationshipAndNoRevision() {
    $view = $this->prophesize('Drupal\views\ViewExecutable')->reveal();
    $view_entity = $this->prophesize(ViewEntityInterface::class);
    $view_entity->get('base_table')->willReturn('entity_first');
    $view_entity->get('base_field')->willReturn('id');
    $view->storage = $view_entity->reveal();

    $entities = [
      'first' => [
        1 => $this->prophesize(EntityInterface::class)->reveal(),
        2 => $this->prophesize(EntityInterface::class)->reveal(),
      ],
    ];
    $entity_type_manager = $this->setupEntityTypes($entities);
    $date_sql = $this->prophesize(DateSqlInterface::class);

    $query = new Sql([], 'sql', [], $entity_type_manager->reveal(), $date_sql->reveal());
    $query->view = $view;

    $result = [];
    $result[] = new ResultRow([
      'id' => 1,
    ]);
    // Note: Let the same entity be returned multiple times, for example to
    // support the translation usecase.
    $result[] = new ResultRow([
      'id' => 2,
    ]);
    $result[] = new ResultRow([
      'id' => 2,
    ]);

    $query->addField('entity_first', 'id', 'id');
    $query->loadEntities($result);

    $this->assertSame($entities['first'][1], $result[0]->_entity);
    $this->assertSame($entities['first'][2], $result[1]->_entity);
    $this->assertSame($entities['first'][2], $result[2]->_entity);
  }

  /**
   * Create a view with a relationship.
   */
  protected function setupViewWithRelationships(ViewExecutable $view, $base = 'entity_second') {
    // We don't use prophecy, because prophecy enforces methods.
    $relationship = $this->getMockBuilder(RelationshipPluginBase::class)->disableOriginalConstructor()->getMock();
    $relationship->definition['base'] = $base;
    $relationship->tableAlias = $base;
    $relationship->alias = $base;

    $view->relationship[$base] = $relationship;
  }

  /**
   * @covers ::loadEntities
   * @covers ::assignEntitiesToResult
   */
  public function testLoadEntitiesWithRelationship() {
    // We don't use prophecy, because prophecy enforces methods.
    $view = $this->getMockBuilder(ViewExecutable::class)->disableOriginalConstructor()->getMock();
    $this->setupViewWithRelationships($view);

    $view_entity = $this->prophesize(ViewEntityInterface::class);
    $view_entity->get('base_table')->willReturn('entity_first');
    $view_entity->get('base_field')->willReturn('id');
    $view->storage = $view_entity->reveal();

    $entities = [
      'first' => [
        1 => $this->prophesize(EntityInterface::class)->reveal(),
        2 => $this->prophesize(EntityInterface::class)->reveal(),
      ],
      'second' => [
        11 => $this->prophesize(EntityInterface::class)->reveal(),
        12 => $this->prophesize(EntityInterface::class)->reveal(),
      ],
    ];
    $entity_type_manager = $this->setupEntityTypes($entities);
    $date_sql = $this->prophesize(DateSqlInterface::class);

    $query = new Sql([], 'sql', [], $entity_type_manager->reveal(), $date_sql->reveal());
    $query->view = $view;

    $result = [];
    $result[] = new ResultRow([
      'id' => 1,
      'entity_second__id' => 11,
    ]);
    // Provide an explicit NULL value, to test the case of a non required
    // relationship.
    $result[] = new ResultRow([
      'id' => 2,
      'entity_second__id' => NULL,
    ]);
    $result[] = new ResultRow([
      'id' => 2,
      'entity_second__id' => 12,
    ]);

    $query->addField('entity_first', 'id', 'id');
    $query->addField('entity_second', 'id', 'entity_second__id');
    $query->loadEntities($result);

    $this->assertSame($entities['first'][1], $result[0]->_entity);
    $this->assertSame($entities['first'][2], $result[1]->_entity);
    $this->assertSame($entities['first'][2], $result[2]->_entity);

    $this->assertSame($entities['second'][11], $result[0]->_relationship_entities['entity_second']);
    $this->assertEquals([], $result[1]->_relationship_entities);
    $this->assertSame($entities['second'][12], $result[2]->_relationship_entities['entity_second']);
  }

  /**
   * @covers ::loadEntities
   * @covers ::assignEntitiesToResult
   */
  public function testLoadEntitiesWithNonEntityRelationship() {
    // We don't use prophecy, because prophecy enforces methods.
    $view = $this->getMockBuilder(ViewExecutable::class)->disableOriginalConstructor()->getMock();
    $this->setupViewWithRelationships($view, 'entity_first_field_data');

    $view_entity = $this->prophesize(ViewEntityInterface::class);
    $view_entity->get('base_table')->willReturn('entity_first');
    $view_entity->get('base_field')->willReturn('id');
    $view->storage = $view_entity->reveal();

    $entities = [
      'first' => [
        1 => $this->prophesize(EntityInterface::class)->reveal(),
        2 => $this->prophesize(EntityInterface::class)->reveal(),
      ],
    ];
    $entity_type_manager = $this->setupEntityTypes($entities);
    $date_sql = $this->prophesize(DateSqlInterface::class);

    $query = new Sql([], 'sql', [], $entity_type_manager->reveal(), $date_sql->reveal());
    $query->view = $view;

    $result = [];
    $result[] = new ResultRow([
      'id' => 1,
    ]);
    $result[] = new ResultRow([
      'id' => 2,
    ]);

    $query->addField('entity_first', 'id', 'id');
    $query->loadEntities($result);
    $entity_information = $query->getEntityTableInfo();

    $this->assertSame($entities['first'][1], $result[0]->_entity);
    $this->assertSame($entities['first'][2], $result[1]->_entity);

    $this->assertEquals([], $result[0]->_relationship_entities);
    $this->assertEquals([], $result[1]->_relationship_entities);

    // This is an entity table and should be in $entity_information.
    $this->assertContains('first', array_keys($entity_information));
    // This is not an entity table and should not be in $entity_information.
    $this->assertNotContains('entity_first_field_data__entity_first_field_data', array_keys($entity_information));
  }

  /**
   * @covers ::loadEntities
   * @covers ::assignEntitiesToResult
   */
  public function testLoadEntitiesWithRevision() {
    // We don't use prophecy, because prophecy enforces methods.
    $view = $this->getMockBuilder(ViewExecutable::class)
      ->disableOriginalConstructor()
      ->getMock();

    $view_entity = $this->prophesize(ViewEntityInterface::class);
    $view_entity->get('base_table')->willReturn('entity_first__revision');
    $view_entity->get('base_field')->willReturn('vid');
    $view->storage = $view_entity->reveal();

    $entity_revisions = [
      'first' => [
        1 => $this->prophesize(EntityInterface::class)->reveal(),
        3 => $this->prophesize(EntityInterface::class)->reveal(),
      ],
    ];
    $entity_type_manager = $this->setupEntityTypes([], $entity_revisions);
    $date_sql = $this->prophesize(DateSqlInterface::class);

    $query = new Sql([], 'sql', [], $entity_type_manager->reveal(), $date_sql->reveal());
    $query->view = $view;

    $result = [];
    $result[] = new ResultRow([
      'vid' => 1,
    ]);
    $result[] = new ResultRow([
      'vid' => 1,
    ]);
    $result[] = new ResultRow([
      'vid' => 3,
    ]);

    $query->addField('entity_first__revision', 'vid', 'vid');
    $query->loadEntities($result);

    $this->assertSame($entity_revisions['first'][1], $result[0]->_entity);
    $this->assertSame($entity_revisions['first'][1], $result[1]->_entity);
    $this->assertSame($entity_revisions['first'][3], $result[2]->_entity);
  }

  /**
   * @covers ::loadEntities
   * @covers ::assignEntitiesToResult
   */
  public function testLoadEntitiesWithRevisionOfSameEntityType() {
    // We don't use prophecy, because prophecy enforces methods.
    $view = $this->getMockBuilder(ViewExecutable::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->setupViewWithRelationships($view, 'entity_first__revision');

    $view_entity = $this->prophesize(ViewEntityInterface::class);
    $view_entity->get('base_table')->willReturn('entity_first');
    $view_entity->get('base_field')->willReturn('id');
    $view->storage = $view_entity->reveal();

    $entity = [
      'first' => [
        1 => $this->prophesize(EntityInterface::class)->reveal(),
        2 => $this->prophesize(EntityInterface::class)->reveal(),
      ],
    ];
    $entity_revisions = [
      'first' => [
        1 => $this->prophesize(EntityInterface::class)->reveal(),
        2 => $this->prophesize(EntityInterface::class)->reveal(),
        3 => $this->prophesize(EntityInterface::class)->reveal(),
      ],
    ];
    $entity_type_manager = $this->setupEntityTypes($entity, $entity_revisions);
    $date_sql = $this->prophesize(DateSqlInterface::class);

    $query = new Sql([], 'sql', [], $entity_type_manager->reveal(), $date_sql->reveal());
    $query->view = $view;

    $result = [];
    $result[] = new ResultRow([
      'id' => 1,
      'entity_first__revision__vid' => 1,
    ]);
    $result[] = new ResultRow([
      'id' => 2,
      'entity_first__revision__vid' => 2,
    ]);
    $result[] = new ResultRow([
      'id' => 2,
      'entity_first__revision__vid' => 3,
    ]);

    $query->addField('entity_first', 'id', 'id');
    $query->addField('entity_first__revision', 'vid', 'entity_first__revision__vid');
    $query->loadEntities($result);

    $this->assertSame($entity['first'][1], $result[0]->_entity);
    $this->assertSame($entity['first'][2], $result[1]->_entity);
    $this->assertSame($entity['first'][2], $result[2]->_entity);
    $this->assertSame($entity_revisions['first'][1], $result[0]->_relationship_entities['entity_first__revision']);
    $this->assertSame($entity_revisions['first'][2], $result[1]->_relationship_entities['entity_first__revision']);
    $this->assertSame($entity_revisions['first'][3], $result[2]->_relationship_entities['entity_first__revision']);
  }

  /**
   * @covers ::loadEntities
   * @covers ::assignEntitiesToResult
   */
  public function testLoadEntitiesWithRelationshipAndRevision() {
    // We don't use prophecy, because prophecy enforces methods.
    $view = $this->getMockBuilder(ViewExecutable::class)->disableOriginalConstructor()->getMock();
    $this->setupViewWithRelationships($view);

    $view_entity = $this->prophesize(ViewEntityInterface::class);
    $view_entity->get('base_table')->willReturn('entity_first__revision');
    $view_entity->get('base_field')->willReturn('vid');
    $view->storage = $view_entity->reveal();

    $entities = [
      'second' => [
        11 => $this->prophesize(EntityInterface::class)->reveal(),
        12 => $this->prophesize(EntityInterface::class)->reveal(),
      ],
    ];
    $entity_revisions = [
      'first' => [
        1 => $this->prophesize(EntityInterface::class)->reveal(),
        3 => $this->prophesize(EntityInterface::class)->reveal(),
      ],
    ];
    $entity_type_manager = $this->setupEntityTypes($entities, $entity_revisions);
    $date_sql = $this->prophesize(DateSqlInterface::class);

    $query = new Sql([], 'sql', [], $entity_type_manager->reveal(), $date_sql->reveal());
    $query->view = $view;

    $result = [];
    $result[] = new ResultRow([
      'vid' => 1,
      'entity_second__id' => 11,
    ]);
    // Provide an explicit NULL value, to test the case of a non required
    // relationship.
    $result[] = new ResultRow([
      'vid' => 1,
      'entity_second__id' => NULL,
    ]);
    $result[] = new ResultRow([
      'vid' => 3,
      'entity_second__id' => 12,
    ]);

    $query->addField('entity_first__revision', 'vid', 'vid');
    $query->addField('entity_second', 'id', 'entity_second__id');
    $query->loadEntities($result);

    $this->assertSame($entity_revisions['first'][1], $result[0]->_entity);
    $this->assertSame($entity_revisions['first'][1], $result[1]->_entity);
    $this->assertSame($entity_revisions['first'][3], $result[2]->_entity);

    $this->assertSame($entities['second'][11], $result[0]->_relationship_entities['entity_second']);
    $this->assertEquals([], $result[1]->_relationship_entities);
    $this->assertSame($entities['second'][12], $result[2]->_relationship_entities['entity_second']);
  }

}
