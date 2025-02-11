<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Kernel;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\workspaces\Entity\Workspace;

/**
 * Tests the entity repository integration for workspaces.
 *
 * @group workspaces
 */
class WorkspaceEntityRepositoryTest extends KernelTestBase {

  use UserCreationTrait;
  use WorkspaceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'language',
    'system',
    'user',
    'workspaces',
  ];

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity repository.
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->entityRepository = $this->container->get('entity.repository');

    $this->installEntitySchema('entity_test_revpub');
    $this->installEntitySchema('entity_test_mulrevpub');
    $this->installEntitySchema('workspace');

    $this->installSchema('workspaces', ['workspace_association']);

    $this->installConfig(['system', 'language']);
    ConfigurableLanguage::createFromLangcode('ro')
      ->setWeight(1)
      ->save();

    Workspace::create(['id' => 'ham', 'label' => 'Ham'])->save();
    Workspace::create(['id' => 'cheese', 'label' => 'Cheese'])->save();
  }

  /**
   * Tests retrieving active variants in a workspace.
   *
   * @covers \Drupal\Core\Entity\EntityRepository::getActive
   * @covers \Drupal\Core\Entity\EntityRepository::getActiveMultiple
   */
  public function testGetActive(): void {
    $en_contexts = ['langcode' => 'en'];
    $ro_contexts = ['langcode' => 'ro'];

    // Check that the correct active variant is returned for a non-translatable
    // revisionable entity.
    $entity_type_id = 'entity_test_revpub';
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $values = ['name' => $this->randomString()];
    $entity = $storage->create($values);
    $storage->save($entity);

    // Create revisions in two workspaces, then another one in Live.
    $this->switchToWorkspace('ham');
    $ham_revision = $storage->createRevision($entity);
    $storage->save($ham_revision);
    $active = $this->entityRepository->getActive($entity_type_id, $entity->id(), $en_contexts);
    $this->assertSame($ham_revision->getLoadedRevisionId(), $active->getLoadedRevisionId());

    // Check that the active variant in Live is still the default revision.
    $this->switchToLive();
    $active = $this->entityRepository->getActive($entity_type_id, $entity->id(), $en_contexts);
    $this->assertSame($entity->getLoadedRevisionId(), $active->getLoadedRevisionId());

    $this->switchToWorkspace('cheese');
    $cheese_revision = $storage->createRevision($entity);
    $storage->save($cheese_revision);
    $active = $this->entityRepository->getActive($entity_type_id, $entity->id(), $en_contexts);
    $this->assertSame($cheese_revision->getLoadedRevisionId(), $active->getLoadedRevisionId());

    $this->switchToLive();
    $live_revision = $storage->createRevision($entity);
    $storage->save($live_revision);
    $active = $this->entityRepository->getActive($entity_type_id, $entity->id(), $en_contexts);
    $this->assertSame($live_revision->getLoadedRevisionId(), $active->getLoadedRevisionId());

    // Switch back to the two workspaces and check that workspace-specific
    // revision are returned even when there's a newer revision in Live.
    $this->switchToWorkspace('ham');
    $active = $this->entityRepository->getActive($entity_type_id, $entity->id(), $en_contexts);
    $this->assertSame($ham_revision->getLoadedRevisionId(), $active->getLoadedRevisionId());

    $this->switchToWorkspace('cheese');
    $active = $this->entityRepository->getActive($entity_type_id, $entity->id(), $en_contexts);
    $this->assertSame($cheese_revision->getLoadedRevisionId(), $active->getLoadedRevisionId());

    // Check that a revision created in a workspace does not leak into other
    // workspaces.
    $entity_2 = $storage->create(['name' => $this->randomString()]);
    $storage->save($entity_2);

    // Create a new revision in a workspace.
    $this->switchToWorkspace('ham');
    $ham_revision = $storage->createRevision($entity_2);
    $storage->save($ham_revision);
    $active = $this->entityRepository->getActive($entity_type_id, $entity_2->id(), $en_contexts);
    $this->assertSame($ham_revision->getLoadedRevisionId(), $active->getLoadedRevisionId());

    // Check that the default revision is returned in another workspace.
    $this->switchToWorkspace('cheese');
    $active = $this->entityRepository->getActive($entity_type_id, $entity_2->id(), $en_contexts);
    $this->assertSame($entity_2->getLoadedRevisionId(), $active->getLoadedRevisionId());

    // Check that the correct active variant is returned for a translatable and
    // revisionable entity.
    $this->switchToLive();
    $entity_type_id = 'entity_test_mulrevpub';
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $values = ['name' => $this->randomString()];
    $initial_revision = $storage->create($values);
    $storage->save($initial_revision);

    $revision_translation = $initial_revision->addTranslation('ro', $values);
    $revision_translation = $storage->createRevision($revision_translation);
    $storage->save($revision_translation);

    // Add a translation in a workspace.
    $this->switchToWorkspace('ham');
    $ham_revision_ro = $storage->createRevision($revision_translation);
    $storage->save($ham_revision_ro);

    $active = $this->entityRepository->getActive($entity_type_id, $entity->id(), $ro_contexts);
    $this->assertSame($ham_revision_ro->getLoadedRevisionId(), $active->getLoadedRevisionId());
    $this->assertSame($ham_revision_ro->language()->getId(), $active->language()->getId());

    // Add a new translation in another workspace.
    $this->switchToWorkspace('cheese');
    $cheese_revision_ro = $storage->createRevision($revision_translation);
    $storage->save($cheese_revision_ro);

    $active = $this->entityRepository->getActive($entity_type_id, $entity->id(), $ro_contexts);
    $this->assertSame($cheese_revision_ro->getLoadedRevisionId(), $active->getLoadedRevisionId());
    $this->assertSame($cheese_revision_ro->language()->getId(), $active->language()->getId());

    // Add a new translations in Live.
    $this->switchToLive();
    $live_revision_ro = $storage->createRevision($revision_translation);
    $storage->save($live_revision_ro);

    $active = $this->entityRepository->getActive($entity_type_id, $entity->id(), $ro_contexts);
    $this->assertSame($live_revision_ro->getLoadedRevisionId(), $active->getLoadedRevisionId());
    $this->assertSame($live_revision_ro->language()->getId(), $active->language()->getId());

    $this->switchToWorkspace('ham');
    $active = $this->entityRepository->getActive($entity_type_id, $entity->id(), $ro_contexts);
    $this->assertSame($ham_revision_ro->getLoadedRevisionId(), $active->getLoadedRevisionId());
    $this->assertSame($ham_revision_ro->language()->getId(), $active->language()->getId());

    $this->switchToWorkspace('cheese');
    $active = $this->entityRepository->getActive($entity_type_id, $entity->id(), $ro_contexts);
    $this->assertSame($cheese_revision_ro->getLoadedRevisionId(), $active->getLoadedRevisionId());
    $this->assertSame($cheese_revision_ro->language()->getId(), $active->language()->getId());
  }

}
