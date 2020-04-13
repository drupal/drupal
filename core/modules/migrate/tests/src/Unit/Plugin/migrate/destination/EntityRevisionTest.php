<?php

namespace Drupal\Tests\migrate\Unit\Plugin\migrate\destination;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\destination\EntityRevision;
use Drupal\migrate\Row;

/**
 * Tests entity revision destination functionality.
 *
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\destination\EntityRevision
 * @group migrate
 */
class EntityRevisionTest extends EntityTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->migration = $this->prophesize(MigrationInterface::class);
    $this->storage = $this->prophesize(EntityStorageInterface::class);

    $this->entityType = $this->prophesize(EntityTypeInterface::class);
    $this->entityType->getSingularLabel()->willReturn('foo');
    $this->entityType->getPluralLabel()->willReturn('bar');
    $this->storage->getEntityType()->willReturn($this->entityType->reveal());
    $this->storage->getEntityTypeId()->willReturn('foo');
  }

  /**
   * Tests that revision destination fails for unrevisionable entities.
   */
  public function testUnrevisionable() {
    $this->entityType->getKey('id')->willReturn('id');
    $this->entityType->getKey('revision')->willReturn('');
    $this->entityFieldManager->getBaseFieldDefinitions('foo')
      ->willReturn([
        'id' => BaseFieldDefinitionTest::create('integer'),
      ]);

    $destination = new EntityRevisionTestDestination(
      [],
      '',
      [],
      $this->migration->reveal(),
      $this->storage->reveal(),
      [],
      $this->entityFieldManager->reveal(),
      $this->prophesize(FieldTypePluginManagerInterface::class)->reveal()
    );
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('The "foo" entity type does not support revisions.');
    $destination->getIds();
  }

  /**
   * Tests that translation destination fails for untranslatable entities.
   */
  public function testUntranslatable() {
    $this->entityType->getKey('id')->willReturn('id');
    $this->entityType->getKey('revision')->willReturn('vid');
    $this->entityType->getKey('langcode')->willReturn('');
    $this->entityFieldManager->getBaseFieldDefinitions('foo')
      ->willReturn([
        'id' => BaseFieldDefinitionTest::create('integer'),
        'vid' => BaseFieldDefinitionTest::create('integer'),
      ]);

    $destination = new EntityRevisionTestDestination(
      ['translations' => TRUE],
      '',
      [],
      $this->migration->reveal(),
      $this->storage->reveal(),
      [],
      $this->entityFieldManager->reveal(),
      $this->prophesize(FieldTypePluginManagerInterface::class)->reveal()
    );
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('The "foo" entity type does not support translations.');
    $destination->getIds();
  }

}

/**
 * Stub class for testing EntityRevision methods.
 */
class EntityRevisionTestDestination extends EntityRevision {

  private $entity = NULL;

  public function setEntity($entity) {
    $this->entity = $entity;
  }

  protected function getEntity(Row $row, array $old_destination_id_values) {
    return $this->entity;
  }

  public static function getEntityTypeId($plugin_id) {
    return 'foo';
  }

}
