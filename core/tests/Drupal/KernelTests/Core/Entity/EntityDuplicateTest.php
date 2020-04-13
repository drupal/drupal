<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\entity_test\Entity\EntityTestRev;

/**
 * Test entity duplication.
 *
 * @group Entity
 */
class EntityDuplicateTest extends EntityKernelTestBase {

  /**
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $entityTestRevStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test_rev');
    $this->entityTestRevStorage = $this->container->get('entity_type.manager')->getStorage('entity_test_rev');
  }

  /**
   * Test duplicating a non-default revision.
   */
  public function testDuplicateNonDefaultRevision() {
    $entity = EntityTestRev::create([
      'name' => 'First Revision',
    ]);
    $entity->save();
    $first_revision_id = $entity->getRevisionId();

    $entity->setNewRevision(TRUE);
    $entity->name = 'Second Revision';
    $entity->save();

    $duplicate_first_revision = $this->entityTestRevStorage->loadRevision($first_revision_id)->createDuplicate();
    $this->assertTrue($duplicate_first_revision->isDefaultRevision(), 'Duplicating a non-default revision creates a default revision.');
    $this->assertEquals($duplicate_first_revision->label(), 'First Revision');
    $duplicate_first_revision->save();

    $duplicate_first_revision->name = 'Updated name';
    $duplicate_first_revision->save();

    $this->entityTestRevStorage->resetCache();
    $duplicate_first_revision = EntityTestRev::load($duplicate_first_revision->id());
    $this->assertEquals('Updated name', $duplicate_first_revision->label());
  }

}
