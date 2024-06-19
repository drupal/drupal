<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Kernel;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\workspaces\Entity\Handler\IgnoredWorkspaceHandler;
use Drupal\workspaces\Entity\Workspace;
use Drupal\workspaces_test\EntityTestRevPubWorkspaceHandler;

/**
 * Tests the workspace information service.
 *
 * @coversDefaultClass \Drupal\workspaces\WorkspaceInformation
 *
 * @group workspaces
 */
class WorkspaceInformationTest extends KernelTestBase {

  use UserCreationTrait;
  use WorkspaceTestTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The workspace information service.
   *
   * @var \Drupal\wse\Core\WorkspaceInformationInterface
   */
  protected $workspaceInformation;

  /**
   * The state store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'user',
    'workspaces',
    'workspaces_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->workspaceInformation = \Drupal::service('workspaces.information');
    $this->state = \Drupal::state();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_rev');
    $this->installEntitySchema('entity_test_revpub');
    $this->installEntitySchema('workspace');

    $this->installSchema('workspaces', ['workspace_association']);

    // Create a new workspace and activate it.
    Workspace::create(['id' => 'stage', 'label' => 'Stage'])->save();
    $this->switchToWorkspace('stage');
  }

  /**
   * Tests fully supported entity types.
   */
  public function testSupportedEntityTypes(): void {
    // Check a supported entity type.
    $entity = $this->entityTypeManager->getStorage('entity_test_revpub')->create();

    $this->assertTrue($this->workspaceInformation->isEntitySupported($entity));
    $this->assertTrue($this->workspaceInformation->isEntityTypeSupported($entity->getEntityType()));

    $this->assertFalse($this->workspaceInformation->isEntityIgnored($entity));
    $this->assertFalse($this->workspaceInformation->isEntityTypeIgnored($entity->getEntityType()));

    // Check that supported entity types are tracked in a workspace. This entity
    // is published by default, so the second revision will be tracked.
    $entity->save();
    $this->assertWorkspaceAssociation(['stage' => [2]], 'entity_test_revpub');
  }

  /**
   * Tests an entity type with a custom workspace handler.
   */
  public function testCustomSupportEntityTypes(): void {
    $entity_type = clone $this->entityTypeManager->getDefinition('entity_test_revpub');
    $entity_type->setHandlerClass('workspace', EntityTestRevPubWorkspaceHandler::class);
    $this->state->set('entity_test_revpub.entity_type', $entity_type);
    $this->entityTypeManager->clearCachedDefinitions();

    $entity = $this->entityTypeManager->getStorage('entity_test_revpub')->create([
      'type' => 'supported_bundle',
    ]);

    $this->assertTrue($this->workspaceInformation->isEntitySupported($entity));
    $this->assertTrue($this->workspaceInformation->isEntityTypeSupported($entity->getEntityType()));
    $this->assertFalse($this->workspaceInformation->isEntityIgnored($entity));
    $this->assertFalse($this->workspaceInformation->isEntityTypeIgnored($entity->getEntityType()));

    // Check that supported entity types are tracked in a workspace. This entity
    // is published by default, so the second revision will be tracked.
    $entity->save();
    $this->assertWorkspaceAssociation(['stage' => [2]], 'entity_test_revpub');

    $entity = $this->entityTypeManager->getStorage('entity_test_revpub')->create([
      'type' => 'ignored_bundle',
    ]);

    $this->assertFalse($this->workspaceInformation->isEntitySupported($entity));
    $this->assertTrue($this->workspaceInformation->isEntityTypeSupported($entity->getEntityType()));
    $this->assertTrue($this->workspaceInformation->isEntityIgnored($entity));
    $this->assertFalse($this->workspaceInformation->isEntityTypeIgnored($entity->getEntityType()));

    // Check that an ignored entity can be saved, but won't be tracked.
    $entity->save();
    $this->assertWorkspaceAssociation(['stage' => [2]], 'entity_test_revpub');
  }

  /**
   * Tests ignored entity types.
   */
  public function testIgnoredEntityTypes(): void {
    $entity_type = clone $this->entityTypeManager->getDefinition('entity_test_rev');
    $entity_type->setHandlerClass('workspace', IgnoredWorkspaceHandler::class);
    $this->state->set('entity_test_rev.entity_type', $entity_type);
    $this->entityTypeManager->clearCachedDefinitions();

    // Check an ignored entity type. CRUD operations for an ignored entity type
    // are allowed in a workspace, but their revisions are not tracked.
    $entity = $this->entityTypeManager->getStorage('entity_test_rev')->create();
    $this->assertTrue($this->workspaceInformation->isEntityIgnored($entity));
    $this->assertTrue($this->workspaceInformation->isEntityTypeIgnored($entity->getEntityType()));

    $this->assertFalse($this->workspaceInformation->isEntitySupported($entity));
    $this->assertFalse($this->workspaceInformation->isEntityTypeSupported($entity->getEntityType()));

    // Check that ignored entity types are not tracked in a workspace.
    $entity->save();
    $this->assertWorkspaceAssociation(['stage' => []], 'entity_test_rev');
  }

  /**
   * Tests unsupported entity types.
   */
  public function testUnsupportedEntityTypes(): void {
    // Check an unsupported entity type.
    $entity_test = $this->entityTypeManager->getDefinition('entity_test');
    $this->assertFalse($entity_test->hasHandlerClass('workspace'));

    $entity = $this->entityTypeManager->getStorage('entity_test')->create();
    $this->assertFalse($this->workspaceInformation->isEntitySupported($entity));
    $this->assertFalse($this->workspaceInformation->isEntityTypeSupported($entity_test));

    $this->assertFalse($this->workspaceInformation->isEntityIgnored($entity));
    $this->assertFalse($this->workspaceInformation->isEntityTypeIgnored($entity_test));

    // Check that unsupported entity types can not be saved in a workspace.
    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('This entity can only be saved in the default workspace.');
    $entity->save();
  }

}
