<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\destination;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\migrate\Plugin\migrate\destination\EntityRevision as RealEntityRevision;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Tests entity revision destination.
 *
 * @group migrate
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\destination\EntityRevision
 */
class EntityRevisionTest extends UnitTestCase {

  /**
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected MigrationInterface $migration;

  /**
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $storage;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected FieldTypePluginManagerInterface $fieldTypeManager;

  /**
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected AccountSwitcherInterface $accountSwitcher;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Setup mocks to be used when creating a revision destination.
    $this->migration = $this->prophesize(MigrationInterface::class)->reveal();
    $this->storage = $this->prophesize(RevisionableStorageInterface::class);

    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->getSingularLabel()->willReturn('crazy');
    $entity_type->getPluralLabel()->willReturn('craziness');
    $entity_type->getKey('id')->willReturn('nid');
    $entity_type->getKey('revision')->willReturn('vid');
    $this->storage->getEntityType()->willReturn($entity_type->reveal());

    $this->entityFieldManager = $this->prophesize(EntityFieldManagerInterface::class)->reveal();
    $this->fieldTypeManager = $this->prophesize(FieldTypePluginManagerInterface::class)->reveal();
    $this->accountSwitcher = $this->prophesize(AccountSwitcherInterface::class)->reveal();
  }

  /**
   * Tests that passed old destination values are used by default.
   *
   * @covers ::getEntity
   */
  public function testGetEntityDestinationValues(): void {
    $destination = $this->getEntityRevisionDestination([]);
    // Return a dummy because we don't care what gets called.
    $entity = $this->prophesize(RevisionableInterface::class);
    // Assert that the first ID from the destination values is used to load the
    // entity.
    $this->storage->loadRevision(12)
      ->shouldBeCalled()
      ->willReturn($entity->reveal());
    $row = new Row();
    $this->assertEquals($entity->reveal(), $destination->getEntity($row, [12, 13]));
  }

  /**
   * Tests that revision updates update.
   *
   * @covers ::getEntity
   */
  public function testGetEntityUpdateRevision(): void {
    $destination = $this->getEntityRevisionDestination([]);
    $entity = $this->prophesize(RevisionableInterface::class);

    // Assert we load the correct revision.
    $this->storage->loadRevision(2)
      ->shouldBeCalled()
      ->willReturn($entity->reveal());
    // Make sure its set as an update and not the default revision.
    $entity->setNewRevision(FALSE)->shouldBeCalled();
    $entity->isDefaultRevision(FALSE)->shouldBeCalled();

    $row = new Row(['nid' => 1, 'vid' => 2], ['nid' => 1, 'vid' => 2]);
    $row->setDestinationProperty('vid', 2);
    $this->assertEquals($entity->reveal(), $destination->getEntity($row, []));
  }

  /**
   * Tests that new revisions are flagged to be written as new.
   *
   * @covers ::getEntity
   */
  public function testGetEntityNewRevision(): void {
    $destination = $this->getEntityRevisionDestination([]);
    $entity = $this->prophesize(RevisionableInterface::class);

    // Enforce is new should be disabled.
    $entity->enforceIsNew(FALSE)->shouldBeCalled();
    // And toggle this as new revision but not the default revision.
    $entity->setNewRevision(TRUE)->shouldBeCalled();
    $entity->isDefaultRevision(FALSE)->shouldBeCalled();

    // Assert we load the correct revision.
    $this->storage->load(1)
      ->shouldBeCalled()
      ->willReturn($entity->reveal());

    $row = new Row(['nid' => 1, 'vid' => 2], ['nid' => 1, 'vid' => 2]);
    $row->setDestinationProperty('nid', 1);
    $this->assertEquals($entity->reveal(), $destination->getEntity($row, []));
  }

  /**
   * Tests entity load failure.
   *
   * @covers ::getEntity
   */
  public function testGetEntityLoadFailure(): void {
    $destination = $this->getEntityRevisionDestination([]);

    // Return a failed load and make sure we don't fail and we return FALSE.
    $this->storage->load(1)
      ->shouldBeCalled()
      ->willReturn(FALSE);

    $row = new Row(['nid' => 1, 'vid' => 2], ['nid' => 1, 'vid' => 2]);
    $row->setDestinationProperty('nid', 1);
    $this->assertFalse($destination->getEntity($row, []));
  }

  /**
   * Tests entity revision save.
   *
   * @covers ::save
   */
  public function testSave(): void {
    $entity = $this->prophesize(ContentEntityInterface::class);
    $entity->save()
      ->shouldBeCalled();
    // Syncing should be set once.
    $entity->setSyncing(Argument::exact(TRUE))
      ->shouldBeCalledTimes(1);
    $entity->getRevisionId()
      ->shouldBeCalled()
      ->willReturn(1234);
    $destination = $this->getEntityRevisionDestination();
    $this->assertEquals([1234], $destination->save($entity->reveal(), []));
  }

  /**
   * Helper method to create an entity revision destination with mock services.
   *
   * @see \Drupal\Tests\migrate\Unit\Destination\EntityRevision
   *
   * @param $configuration
   *   Configuration for the destination.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   *
   * @return \Drupal\Tests\migrate\Unit\destination\EntityRevision
   *   Mocked destination.
   */
  protected function getEntityRevisionDestination(array $configuration = [], $plugin_id = 'entity_revision', array $plugin_definition = []) {
    return new EntityRevision($configuration, $plugin_id, $plugin_definition,
      $this->migration,
      $this->storage->reveal(),
      [],
      $this->entityFieldManager,
      $this->fieldTypeManager,
      $this->accountSwitcher,
    );
  }

}

/**
 * Mock that exposes from internal methods for testing.
 */
class EntityRevision extends RealEntityRevision {

  /**
   * Allow public access for testing.
   */
  public function getEntity(Row $row, array $old_destination_id_values) {
    return parent::getEntity($row, $old_destination_id_values);
  }

  /**
   * Allow public access for testing.
   */
  public function save(ContentEntityInterface $entity, array $old_destination_id_values = []) {
    return parent::save($entity, $old_destination_id_values);
  }

  /**
   * Don't test method from base class.
   *
   * This method is from the parent and we aren't concerned with the inner
   * workings of its implementation which would trickle into mock assertions. An
   * empty implementation avoids this.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to update.
   * @param \Drupal\migrate\Row $row
   *   The row object to update from.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   An updated entity from row values.
   */
  protected function updateEntity(EntityInterface $entity, Row $row) {
    return $entity;
  }

}
