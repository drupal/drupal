<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\Plugin\migrate\destination\EntityContentBaseTest
 */

namespace Drupal\Tests\migrate\Unit\Plugin\migrate\destination;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;

/**
 * Tests base entity migration destination functionality.
 *
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\destination\EntityContentBase
 * @group migrate
 */
class EntityContentBaseTest extends UnitTestCase {

  /**
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
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
    $this->entityManager = $this->prophesize(EntityManagerInterface::class);
  }

  /**
   * Test basic entity save.
   *
   * @covers ::import
   */
  public function testImport() {
    $bundles = [];
    $destination = new EntityTestDestination([], '', [],
      $this->migration->reveal(),
      $this->storage->reveal(),
      $bundles,
      $this->entityManager->reveal(),
      $this->prophesize(FieldTypePluginManagerInterface::class)->reveal());
    $entity = $this->prophesize(ContentEntityInterface::class);
    // Assert that save is called.
    $entity->save()
      ->shouldBeCalledTimes(1);
    // Set an id for the entity
    $entity->id()
      ->willReturn(5);
    $destination->setEntity($entity->reveal());
    // Ensure the id is saved entity id is returned from import.
    $this->assertEquals([5], $destination->import(new Row()));
    // Assert that import set the rollback action.
    $this->assertEquals(MigrateIdMapInterface::ROLLBACK_DELETE, $destination->rollbackAction());
  }

  /**
   * Test row skipping when we can't get an entity to save.
   *
   * @covers ::import
   */
  public function testImportEntityLoadFailure() {
    $bundles = [];
    $destination = new EntityTestDestination([], '', [],
      $this->migration->reveal(),
      $this->storage->reveal(),
      $bundles,
      $this->entityManager->reveal(),
      $this->prophesize(FieldTypePluginManagerInterface::class)->reveal());
    $destination->setEntity(FALSE);
    $this->setExpectedException(MigrateException::class, 'Unable to get entity');
    $destination->import(new Row());
  }

  /**
   * Test that translation destination fails for untranslatable entities.
   */
  public function testUntranslatable() {
    // An entity type without a language.
    $entity_type = $this->prophesize(ContentEntityType::class);
    $entity_type->getKey('langcode')->willReturn('');
    $entity_type->getKey('id')->willReturn('id');
    $this->entityManager->getBaseFieldDefinitions('foo')
      ->willReturn(['id' => BaseFieldDefinitionTest::create('integer')]);

    $this->storage->getEntityType()->willReturn($entity_type->reveal());

    $destination = new EntityTestDestination(
      ['translations' => TRUE ],
      '',
      [],
      $this->migration->reveal(),
      $this->storage->reveal(),
      [],
      $this->entityManager->reveal(),
      $this->prophesize(FieldTypePluginManagerInterface::class)->reveal()
    );
    $this->setExpectedException(MigrateException::class, 'This entity type does not support translation');
    $destination->getIds();
  }

}

/**
 * Stub class for testing EntityContentBase methods.
 *
 * We want to test things without testing the base class implementations.
 */
class EntityTestDestination extends EntityContentBase {

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
