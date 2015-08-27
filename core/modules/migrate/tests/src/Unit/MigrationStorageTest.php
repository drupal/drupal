<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\MigrationStorageTest.
 */

namespace Drupal\Tests\migrate\Unit;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryFactoryInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\migrate\MigrationStorage;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\migrate\MigrationStorage
 * @group migrate
 */
class MigrationStorageTest extends UnitTestCase {

  /**
   * @var \Drupal\Tests\migrate\Unit\TestMigrationStorage
   */
  protected $storage;

  /**
   * @var \Drupal\Core\Entity\Query\QueryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $query;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->query = $this->getMock(QueryInterface::class);
    $this->query->method('condition')
      ->willReturnSelf();

    $query_factory = $this->getMock(QueryFactoryInterface::class);
    $query_factory->method('get')
      ->willReturn($this->query);

    $this->storage = new TestMigrationStorage(
      $this->getMock(EntityTypeInterface::class),
      $this->getMock(ConfigFactoryInterface::class),
      $this->getMock(UuidInterface::class),
      $this->getMock(LanguageManagerInterface::class),
      $query_factory
    );
  }

  /**
   * Tests getVariantIds() when variants exist.
   *
   * @covers ::getVariantIds
   */
  public function testGetVariantIdsWithVariants() {
    $this->query->method('execute')
      ->willReturn(['d6_node__page', 'd6_node__article']);

    $ids = $this->storage->getVariantIds(['d6_node:*', 'd6_user']);
    $this->assertSame(['d6_node__page', 'd6_node__article', 'd6_user'],  $ids);
  }

  /**
   * Tests getVariantIds() when no variants exist.
   *
   * @covers ::getVariantIds
   */
  public function testGetVariantIdsNoVariants() {
    $this->query->method('execute')
      ->willReturn([]);

    $ids = $this->storage->getVariantIds(['d6_node:*', 'd6_user']);
    $this->assertSame(['d6_user'],  $ids);
  }

  /**
   * Tests getVariantIds() when no variants exist and there are no static
   * (non-variant) dependencies.
   *
   * @covers ::getVariantIds
   */
  public function testGetVariantIdsNoVariantsOrStaticDependencies() {
    $this->query->method('execute')
      ->willReturn([]);

    $ids = $this->storage->getVariantIds(['d6_node:*', 'd6_node_revision:*']);
    $this->assertSame([],  $ids);
  }

}

/**
 * Test version of \Drupal\migrate\MigrationStorage.
 *
 * Exposes protected methods for testing.
 */
class TestMigrationStorage extends MigrationStorage {

  /**
   * {@inheritdoc}
   */
  public function getVariantIds(array $ids) {
    return parent::getVariantIds($ids);
  }

}
