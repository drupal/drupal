<?php

namespace Drupal\Tests\migrate\Unit\Plugin\migrate\destination;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\destination\EntityRevision;
use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;

/**
 * Tests entity revision destination functionality.
 *
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\destination\EntityRevision
 * @group migrate
 */
class EntityRevisionTest extends UnitTestCase {

  /**
   * The migration.
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
   * The entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->migration = $this->prophesize(MigrationInterface::class);
    $this->storage = $this->prophesize(EntityStorageInterface::class);

    $this->entityType = $this->prophesize(EntityTypeInterface::class);
    $this->entityType->getSingularLabel()->willReturn('foo');
    $this->entityType->getPluralLabel()->willReturn('bar');
    $this->storage->getEntityType()->willReturn($this->entityType->reveal());
    $this->storage->getEntityTypeId()->willReturn('foo');

    $this->entityManager = $this->prophesize(EntityManagerInterface::class);
  }

  /**
   * Tests that revision destination fails for unrevisionable entities.
   */
  public function testUnrevisionable() {
    $this->entityType->getKey('id')->willReturn('id');
    $this->entityType->getKey('revision')->willReturn('');
    $this->entityManager->getBaseFieldDefinitions('foo')
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
      $this->entityManager->reveal(),
      $this->prophesize(FieldTypePluginManagerInterface::class)->reveal()
    );
    $this->setExpectedException(MigrateException::class, 'The "foo" entity type does not support revisions.');
    $destination->getIds();
  }

  /**
   * Tests that translation destination fails for untranslatable entities.
   */
  public function testUntranslatable() {
    $this->entityType->getKey('id')->willReturn('id');
    $this->entityType->getKey('revision')->willReturn('vid');
    $this->entityType->getKey('langcode')->willReturn('');
    $this->entityManager->getBaseFieldDefinitions('foo')
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
      $this->entityManager->reveal(),
      $this->prophesize(FieldTypePluginManagerInterface::class)->reveal()
    );
    $this->setExpectedException(MigrateException::class, 'The "foo" entity type does not support translations.');
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
