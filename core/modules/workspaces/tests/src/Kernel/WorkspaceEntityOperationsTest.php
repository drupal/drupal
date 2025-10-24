<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Kernel;

use Drupal\entity_test\Entity\EntityTestMulRevPub;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\workspaces\Entity\Workspace;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests entity operations with workspaces.
 */
#[Group('workspaces')]
class WorkspaceEntityOperationsTest extends KernelTestBase {

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
    'workspaces_test',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->workspaceManager = \Drupal::service('workspaces.manager');

    $this->installEntitySchema('entity_test_mulrevpub');
    $this->installEntitySchema('workspace');

    $this->installSchema('workspaces', ['workspace_association']);

    $this->installConfig(['system']);

    $this->setUpCurrentUser([], [
      'create workspace',
      'view any workspace',
      'edit any workspace',
      'delete any workspace',
    ]);

    $this->workspaces['stage'] = Workspace::create(['id' => 'stage', 'label' => 'Stage']);
    $this->workspaces['stage']->save();
  }

  /**
   * Test published entity creation in a workspace.
   */
  public function testEntityCreation(): void {
    $this->switchToWorkspace('stage');

    // Create a published entity in the workspace.
    $entity = EntityTestMulRevPub::create([
      'name' => 'Test published entity in workspace',
      'status' => TRUE,
      'revision_test_field' => $this->randomString(),
    ]);
    $entity->save();

    // Get the revision sequence that was tracked during entity saves.
    $sequence_key = 'entity_test_mulrevpub.' . $entity->uuid() . '.revision_sequence';
    $revision_sequence = \Drupal::keyValue('ws_test')->get($sequence_key, []);

    $field_sequence_key = 'entity_test_mulrevpub.' . $entity->uuid() . '.field_revision_sequence';
    $field_revision_sequence = \Drupal::keyValue('ws_test')->get($field_sequence_key, []);

    // We expect exactly 2 presave calls when a published entity is created in a
    // workspace:
    // 1. First save: unpublished default revision.
    // 2. Second save: published pending revision.
    $this->assertCount(2, $revision_sequence);
    $this->assertCount(2, $field_revision_sequence);

    // Verify the is_new_revision status.
    $this->assertTrue($revision_sequence[0]['is_new_revision']);
    $this->assertTrue($revision_sequence[1]['is_new_revision']);
    $this->assertTrue($field_revision_sequence[0]['is_new_revision']);
    $this->assertTrue($field_revision_sequence[1]['is_new_revision']);

    // Verify the is_new status.
    $this->assertTrue($revision_sequence[0]['is_new']);
    $this->assertFalse($revision_sequence[1]['is_new']);
    $this->assertTrue($field_revision_sequence[0]['is_new']);
    $this->assertFalse($field_revision_sequence[1]['is_new']);

    // Verify the publishing status of each revision.
    $this->assertFalse($revision_sequence[0]['is_published']);
    $this->assertTrue($revision_sequence[1]['is_published']);

    // The entity presave hook is fired after the field's presave()
    // implementation, so at this point the first revision is still published.
    $this->assertTrue($field_revision_sequence[0]['is_published']);
    $this->assertTrue($field_revision_sequence[1]['is_published']);

    // Verify the default revision status.
    $this->assertTrue($revision_sequence[0]['is_default_revision']);
    $this->assertFalse($revision_sequence[1]['is_default_revision']);
    $this->assertTrue($field_revision_sequence[0]['is_default_revision']);
    $this->assertFalse($field_revision_sequence[1]['is_default_revision']);
  }

}
