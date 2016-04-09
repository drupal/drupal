<?php

namespace Drupal\Tests\views\Unit\Plugin\query;

use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\query\Sql;
use Drupal\views\ResultRow;

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

    $query = new Sql([], 'sql', []);
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

    $query = new Sql([], 'sql', []);
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

}
