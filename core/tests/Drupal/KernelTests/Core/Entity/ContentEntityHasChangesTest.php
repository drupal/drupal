<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests ContentEntityBase::hasTranslationChanges().
 *
 * @group Entity
 */
class ContentEntityHasChangesTest extends KernelTestBase {

  /**
   * Bundle of entity.
   *
   * @var string
   */
  protected $bundle = 'test';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test_mulrev_changed_rev');
    $this->installSchema('system', 'sequences');
  }

  /**
   * Tests the correct functionality of the hasTranslationChanges() function.
   */
  public function testHasTranslationChanges() {
    $user1 = User::create([
      'name' => 'username1',
      'status' => 1,
    ]);
    $user1->save();

    $user2 = User::create([
      'name' => 'username2',
      'status' => 1,
    ]);
    $user2->save();

    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('entity_test_mulrev_changed_rev');
    /** @var \Drupal\entity_test\Entity\EntityTestMulRevChangedWithRevisionLog $entity */
    $entity = $storage->create([
      'name' => $this->randomString(),
    ]);
    $entity->setRevisionUserId($user1->id());
    $entity->save();

    $this->assertFalse($entity->hasTranslationChanges(), 'ContentEntityBase::hasTranslationChanges() found no changes after the entity has been saved.');

    // Update the revision metadata fields and the changed field, which should
    // be skipped from checking for changes in
    // ContentEntityBase::hasTranslationChanges().
    $entity_previous_rev_id = $entity->getRevisionId();
    // Revision metadata field revision_timestamp.
    $entity->setRevisionCreationTime(time() + 1);
    // Revision metadata field revision_uid.
    $entity->setRevisionUserId($user2->id());
    // Revision metadata field revision_log.
    $entity->setRevisionLogMessage('test');
    // Revision metadata field revision_translation_affected.
    $entity->setRevisionTranslationAffected(TRUE);
    // Changed field.
    $entity->setChangedTime(time() + 1);

    // Check that the revision metadata fields and the changed field have been
    // skipped when comparing same revisions.
    $this->assertFalse($entity->hasTranslationChanges(), 'ContentEntityBase::hasTranslationChanges() found no changes when comparing different revisions.');

    // Check that the revision metadata fields and the changed field have been
    // skipped when comparing same revisions with enforced new revision to be
    // created on save.
    $entity->setNewRevision(TRUE);
    $this->assertFalse($entity->hasTranslationChanges(), 'ContentEntityBase::hasTranslationChanges() found no changes when comparing different revisions.');

    // Save the entity in new revision with changes on the revision metadata
    // fields.
    $entity->save();

    // Check that the revision metadata fields and the changed field have been
    // skipped when comparing different revisions.
    $entity = $storage->loadRevision($entity_previous_rev_id);
    $this->assertFalse($entity->hasTranslationChanges(), 'ContentEntityBase::hasTranslationChanges() found no changes when comparing different revisions.');
  }

}
