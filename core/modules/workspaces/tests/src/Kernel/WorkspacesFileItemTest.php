<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Kernel;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\file\Kernel\FileItemTest;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\workspaces\Entity\Workspace;

/**
 * Tests using entity fields of the file field type in a workspace.
 *
 * @group workspaces
 */
class WorkspacesFileItemTest extends FileItemTest {

  use UserCreationTrait;
  use WorkspaceTestTrait;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'workspaces',
    'workspaces_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = \Drupal::entityTypeManager();

    $this->installEntitySchema('workspace');
    $this->installSchema('workspaces', ['workspace_association']);

    // Create a new workspace and activate it.
    Workspace::create(['id' => 'stage', 'label' => 'Stage'])->save();
    $this->switchToWorkspace('stage');
  }

  /**
   * {@inheritdoc}
   */
  public function testFileItem(): void {
    // Ignore entity types that are not being tested, in order to fully re-use
    // the parent test method.
    $this->ignoreEntityType('entity_test');
    $this->ignoreEntityType('entity_view_display');

    parent::testFileItem();
  }

}
