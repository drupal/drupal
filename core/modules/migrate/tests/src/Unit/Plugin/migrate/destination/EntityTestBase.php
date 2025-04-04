<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\Plugin\migrate\destination;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Base test class for entity migration destination functionality.
 */
class EntityTestBase extends UnitTestCase {

  /**
   * The migration entity.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->migration = $this->prophesize(MigrationInterface::class);
    $this->storage = $this->prophesize(EntityStorageInterface::class);

    $this->entityType = $this->prophesize(EntityTypeInterface::class);
    $this->entityType->getPluralLabel()->willReturn('foo');
    $this->storage->getEntityType()->willReturn($this->entityType->reveal());
    $this->storage->getEntityTypeId()->willReturn('foo');

    $this->entityFieldManager = $this->prophesize(EntityFieldManagerInterface::class);
  }

}

/**
 * Stub class for BaseFieldDefinition.
 */
class BaseFieldDefinitionTest extends BaseFieldDefinition {

  /**
   * {@inheritdoc}
   */
  public static function create($type) {
    return new static([]);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'integer';
  }

}
