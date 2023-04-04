<?php

namespace Drupal\Tests\workspaces\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\workspaces\Entity\Workspace;

/**
 * @coversDefaultClass \Drupal\workspaces\WorkspaceAssociation
 * @group legacy
 */
class WorkspaceAssociationDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['path_alias', 'user', 'workspaces'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('workspace');
    $this->installSchema('workspaces', ['workspace_association']);
  }

  /**
   * @covers ::postPublish
   */
  public function testPostPublishDeprecation(): void {
    $this->expectDeprecation('Drupal\workspaces\WorkspaceAssociation::postPublish() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use the \Drupal\workspaces\Event\WorkspacePostPublishEvent event instead. See https://www.drupal.org/node/3242573');

    $workspace = Workspace::create([
      'id' => 'test',
      'label' => 'Test',
    ]);
    $workspace->save();
    \Drupal::service('workspaces.association')->postPublish($workspace);
  }

}
