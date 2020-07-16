<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\Plugin\migrate\destination\EntityTestBase
 */

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
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;
  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->migration = $this->prophesize(MigrationInterface::class);
    $this->storage = $this->prophesize(EntityStorageInterface::class);

    $this->entityType = $this->prophesize(EntityTypeInterface::class);
    $this->entityType->getPluralLabel()->willReturn('wonkiness');
    $this->storage->getEntityType()->willReturn($this->entityType->reveal());
    $this->storage->getEntityTypeId()->willReturn('foo');

    $this->entityFieldManager = $this->prophesize(EntityFieldManagerInterface::class);
  }

}

/**
 * Stub class for BaseFieldDefinition.
 */
class BaseFieldDefinitionTest extends BaseFieldDefinition {

  public static function create($type) {
    return new static([]);
  }

  public function getSettings() {
    return [];
  }

  public function getType() {
    return 'integer';
  }

}
