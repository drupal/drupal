<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Kernel;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_test\Entity\EntityTestMulRevPub;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\workspaces\Entity\Workspace;
use Drupal\workspaces\Plugin\Validation\Constraint\EntityWorkspaceConflictConstraintValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\workspaces\Plugin\Validation\Constraint\EntityWorkspaceConflictConstraintValidator.
 */
#[CoversClass(EntityWorkspaceConflictConstraintValidator::class)]
#[Group('workspaces')]
#[RunTestsInSeparateProcesses]
class EntityWorkspaceConflictConstraintValidatorTest extends KernelTestBase {

  use UserCreationTrait;
  use WorkspaceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
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

    $this->installSchema('workspaces', ['workspace_association', 'workspace_association_revision']);

    $this->installEntitySchema('entity_test_mulrevpub');
    $this->installEntitySchema('workspace');
    $this->installEntitySchema('user');
    $this->createUser();
  }

  /**
   * Tests new entities allowed in default workspace.
   *
   * @legacy-covers ::validate
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
    $this->assertCount(0, $entity->validate());

    // Edit the entity in Stage.
    $this->switchToWorkspace('stage');
    $entity->save();
    $this->assertCount(0, $entity->validate());

    $expected_message = 'The content is being edited in the Stage workspace. As a result, your changes cannot be saved.';

    // Check that the entity can no longer be edited in Live.
    $this->switchToLive();
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $this->assertSame($expected_message, (string) $violations->get(0)->getMessage());

    // Check that the entity can no longer be edited in another top-level
    // workspace.
    $this->switchToWorkspace('other');
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $this->assertSame($expected_message, (string) $violations->get(0)->getMessage());

    // Check that the entity can still be edited in a sub-workspace of Stage.
    $this->switchToWorkspace('dev');
    $this->assertCount(0, $entity->validate());

    // Edit the entity in Dev.
    $this->switchToWorkspace('dev');
    $entity->save();
    $this->assertCount(0, $entity->validate());

    $expected_message = 'The content is being edited in the Dev workspace. As a result, your changes cannot be saved.';

    // Check that the entity can no longer be edited in Live.
    $this->switchToLive();
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $this->assertSame($expected_message, (string) $violations->get(0)->getMessage());

    // Check that the entity can no longer be edited in the parent workspace.
    $this->switchToWorkspace('stage');
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $this->assertSame($expected_message, (string) $violations->get(0)->getMessage());

    // Check that the entity can no longer be edited in another top-level
    // workspace.
    $this->switchToWorkspace('other');
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $this->assertSame($expected_message, (string) $violations->get(0)->getMessage());
  }

}
