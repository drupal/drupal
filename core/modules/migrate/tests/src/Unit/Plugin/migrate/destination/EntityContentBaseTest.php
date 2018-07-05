<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\Plugin\migrate\destination\EntityContentBaseTest
 */

namespace Drupal\Tests\migrate\Unit\Plugin\migrate\destination;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;

/**
 * Tests base entity migration destination functionality.
 *
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\destination\EntityContentBase
 * @group migrate
 */
class EntityContentBaseTest extends EntityTestBase {

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
    $this->entityType->getKey('langcode')->willReturn('');
    $this->entityType->getKey('id')->willReturn('id');
    $this->entityManager->getBaseFieldDefinitions('foo')
      ->willReturn(['id' => BaseFieldDefinitionTest::create('integer')]);

    $destination = new EntityTestDestination(
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
