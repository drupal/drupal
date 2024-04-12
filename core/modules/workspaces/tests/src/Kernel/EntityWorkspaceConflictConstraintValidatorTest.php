<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_test\Entity\EntityTestMulRevPub;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\workspaces\Entity\Workspace;

/**
 * @coversDefaultClass \Drupal\workspaces\Plugin\Validation\Constraint\EntityWorkspaceConflictConstraintValidator
 * @group workspaces
 */
class EntityWorkspaceConflictConstraintValidatorTest extends KernelTestBase {

  use UserCreationTrait;
  use WorkspaceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'system',
    'user',
    'workspaces',
  ];

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = \Drupal::entityTypeManager();

    $this->installSchema('workspaces', ['workspace_association']);

    $this->installEntitySchema('entity_test_mulrevpub');
    $this->installEntitySchema('workspace');
    $this->installEntitySchema('user');
    $this->createUser();
  }

  /**
   * @covers ::validate
   */
  public function testNewEntitiesAllowedInDefaultWorkspace(): void {
    // Create two top-level workspaces and a second-level one.
    $stage = Workspace::create(['id' => 'stage', 'label' => 'Stage']);
    $stage->save();
    $dev = Workspace::create(['id' => 'dev', 'label' => 'Dev', 'parent' => 'stage']);
    $dev->save();
    $other = Workspace::create(['id' => 'other', 'label' => 'Other']);
    $other->save();

    // Create an entity in Live, and check that the validation is skipped.
    $entity = EntityTestMulRevPub::create();
    $this->assertCount(0, $entity->validate());
    $entity->save();
    $entity = $this->reloadEntity($entity);
    $this->assertCount(0, $entity->validate());

    // Edit the entity in Stage.
    $this->switchToWorkspace('stage');
    $entity->save();
    $entity = $this->reloadEntity($entity);
    $this->assertCount(0, $entity->validate());

    $expected_message = 'The content is being edited in the Stage workspace. As a result, your changes cannot be saved.';

    // Check that the entity can no longer be edited in Live.
    $this->switchToLive();
    $entity = $this->reloadEntity($entity);
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $this->assertSame($expected_message, (string) $violations->get(0)->getMessage());

    // Check that the entity can no longer be edited in another top-level
    // workspace.
    $this->switchToWorkspace('other');
    $entity = $this->reloadEntity($entity);
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $this->assertSame($expected_message, (string) $violations->get(0)->getMessage());

    // Check that the entity can still be edited in a sub-workspace of Stage.
    $this->switchToWorkspace('dev');
    $entity = $this->reloadEntity($entity);
    $this->assertCount(0, $entity->validate());

    // Edit the entity in Dev.
    $this->switchToWorkspace('dev');
    $entity->save();
    $entity = $this->reloadEntity($entity);
    $this->assertCount(0, $entity->validate());

    $expected_message = 'The content is being edited in the Dev workspace. As a result, your changes cannot be saved.';

    // Check that the entity can no longer be edited in Live.
    $this->switchToLive();
    $entity = $this->reloadEntity($entity);
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $this->assertSame($expected_message, (string) $violations->get(0)->getMessage());

    // Check that the entity can no longer be edited in the parent workspace.
    $this->switchToWorkspace('stage');
    $entity = $this->reloadEntity($entity);
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $this->assertSame($expected_message, (string) $violations->get(0)->getMessage());

    // Check that the entity can no longer be edited in another top-level
    // workspace.
    $this->switchToWorkspace('other');
    $entity = $this->reloadEntity($entity);
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $this->assertSame($expected_message, (string) $violations->get(0)->getMessage());
  }

  /**
   * Reloads the given entity from the storage and returns it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be reloaded.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The reloaded entity.
   */
  protected function reloadEntity(EntityInterface $entity): EntityInterface {
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $storage->resetCache([$entity->id()]);
    return $storage->load($entity->id());
  }

}
