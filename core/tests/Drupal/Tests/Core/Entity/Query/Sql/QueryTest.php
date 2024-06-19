<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity\Query\Sql;

use Drupal\Core\Entity\EntityType;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Entity\Query\QueryException;
use Drupal\Core\Entity\Query\Sql\Query;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\Core\Entity\Query\Sql\Query
 * @group Entity
 */
class QueryTest extends UnitTestCase {

  /**
   * The query object.
   *
   * @var \Drupal\Core\Entity\Query\Sql\Query
   */
  protected $query;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $entity_type = new EntityType(['id' => 'example_entity_query']);
    $conjunction = 'AND';
    $connection = $this->getMockBuilder('Drupal\Core\Database\Connection')->disableOriginalConstructor()->getMock();
    $namespaces = ['Drupal\Core\Entity\Query\Sql'];

    $this->query = new Query($entity_type, $conjunction, $connection, $namespaces);

    $container = $this->createMock(ContainerInterface::class);
    $container->expects($this->any())
      ->method('get')
      ->with('module_handler')
      ->willReturn($this->createMock(ModuleHandler::class));
    \Drupal::setContainer($container);
  }

  /**
   * Tests entity query for entity type without base table.
   *
   * @covers ::prepare
   */
  public function testNoBaseTable(): void {
    $this->expectException(QueryException::class);
    $this->expectExceptionMessage('No base table for example_entity_query, invalid query.');
    $this->query->execute();
  }

  /**
   * Tests revision entity query for entity type without revision table.
   *
   * @covers ::prepare
   */
  public function testNoRevisionTable(): void {
    $this->expectException(QueryException::class);
    $this->expectExceptionMessage('No revision table for example_entity_query, invalid query.');
    $this->query->allRevisions()->execute();
  }

}
