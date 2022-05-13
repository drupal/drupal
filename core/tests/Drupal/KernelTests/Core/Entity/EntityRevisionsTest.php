<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the loaded Revision of an entity.
 *
 * @coversDefaultClass \Drupal\Core\Entity\ContentEntityBase
 *
 * @group entity
 */
class EntityRevisionsTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'entity_test',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test_mulrev');
  }

  /**
   * Tests getLoadedRevisionId() returns the correct ID throughout the process.
   */
  public function testLoadedRevisionId() {
    // Create a basic EntityTestMulRev entity and save it.
    $entity = EntityTestMulRev::create();
    $entity->save();

    // Load the created entity and create a new revision.
    $loaded = EntityTestMulRev::load($entity->id());
    $loaded->setNewRevision(TRUE);

    // Before saving, the loaded Revision ID should be the same as the created
    // entity, not the same as the loaded entity (which does not have a revision
    // ID yet).
    $this->assertEquals($entity->getRevisionId(), $loaded->getLoadedRevisionId());
    $this->assertNotEquals($loaded->getRevisionId(), $loaded->getLoadedRevisionId());
    $this->assertSame(NULL, $loaded->getRevisionId());

    // After updating the loaded Revision ID the result should be the same.
    $loaded->updateLoadedRevisionId();
    $this->assertEquals($entity->getRevisionId(), $loaded->getLoadedRevisionId());
    $this->assertNotEquals($loaded->getRevisionId(), $loaded->getLoadedRevisionId());
    $this->assertSame(NULL, $loaded->getRevisionId());

    $loaded->save();

    // In entity_test_entity_update() the loaded Revision ID was stored in
    // state. This should be the same as we had before calling $loaded->save().
    /** @var \Drupal\Core\Entity\ContentEntityInterface $loaded_original */
    $loadedRevisionId = \Drupal::state()->get('entity_test.loadedRevisionId');
    $this->assertEquals($entity->getRevisionId(), $loadedRevisionId);
    $this->assertNotEquals($loaded->getRevisionId(), $loadedRevisionId);

    // The revision ID and loaded Revision ID should be different for the two
    // versions of the entity, but the same for a saved entity.
    $this->assertNotEquals($loaded->getRevisionId(), $entity->getRevisionId());
    $this->assertNotEquals($loaded->getLoadedRevisionId(), $entity->getLoadedRevisionId());
    $this->assertEquals($entity->getRevisionId(), $entity->getLoadedRevisionId());
    $this->assertEquals($loaded->getRevisionId(), $loaded->getLoadedRevisionId());
  }

  /**
   * Tests the loaded revision ID after an entity re-save, clone and duplicate.
   */
  public function testLoadedRevisionIdWithNoNewRevision() {
    // Create a basic EntityTestMulRev entity and save it.
    $entity = EntityTestMulRev::create();
    $entity->save();

    // Load the created entity and create a new revision.
    $loaded = EntityTestMulRev::load($entity->id());
    $loaded->setNewRevision(TRUE);
    $loaded->save();

    // Make a change to the loaded entity.
    $loaded->set('name', 'dublin');

    // The revision id and loaded Revision id should still be the same.
    $this->assertEquals($loaded->getRevisionId(), $loaded->getLoadedRevisionId());

    $loaded->save();

    // After saving, the loaded Revision id set in entity_test_entity_update()
    // and returned from the entity should be the same as the entity's revision
    // id because a new revision wasn't created, the existing revision was
    // updated.
    $loadedRevisionId = \Drupal::state()->get('entity_test.loadedRevisionId');
    $this->assertEquals($loaded->getRevisionId(), $loadedRevisionId);
    $this->assertEquals($loaded->getRevisionId(), $loaded->getLoadedRevisionId());

    // Creating a clone should keep the loaded Revision ID.
    $clone = clone $loaded;
    $this->assertSame($loaded->getLoadedRevisionId(), $clone->getLoadedRevisionId());

    // Creating a duplicate should set a NULL loaded Revision ID.
    $duplicate = $loaded->createDuplicate();
    $this->assertSame(NULL, $duplicate->getLoadedRevisionId());
  }

  /**
   * Tests the loaded revision ID for translatable entities.
   */
  public function testTranslatedLoadedRevisionId() {
    ConfigurableLanguage::createFromLangcode('fr')->save();

    // Create a basic EntityTestMulRev entity and save it.
    $entity = EntityTestMulRev::create();
    $entity->save();

    // Load the created entity and create a new revision.
    $loaded = EntityTestMulRev::load($entity->id());
    $loaded->setNewRevision(TRUE);
    $loaded->save();

    // Check it all works with translations.
    $french = $loaded->addTranslation('fr');
    // Adding a revision should return the same for each language.
    $this->assertEquals($french->getRevisionId(), $french->getLoadedRevisionId());
    $this->assertEquals($loaded->getRevisionId(), $french->getLoadedRevisionId());
    $this->assertEquals($loaded->getLoadedRevisionId(), $french->getLoadedRevisionId());
    $french->save();
    // After saving nothing should change.
    $this->assertEquals($french->getRevisionId(), $french->getLoadedRevisionId());
    $this->assertEquals($loaded->getRevisionId(), $french->getLoadedRevisionId());
    $this->assertEquals($loaded->getLoadedRevisionId(), $french->getLoadedRevisionId());
    $first_revision_id = $french->getRevisionId();
    $french->setNewRevision();
    // Setting a new revision will reset the loaded Revision ID.
    $this->assertEquals($first_revision_id, $french->getLoadedRevisionId());
    $this->assertEquals($first_revision_id, $loaded->getLoadedRevisionId());
    $this->assertNotEquals($french->getRevisionId(), $french->getLoadedRevisionId());
    $this->assertGreaterThan($french->getRevisionId(), $french->getLoadedRevisionId());
    $this->assertNotEquals($loaded->getRevisionId(), $loaded->getLoadedRevisionId());
    $this->assertGreaterThan($loaded->getRevisionId(), $loaded->getLoadedRevisionId());
    $french->save();
    // Saving the new revision will reset the origin revision ID again.
    $this->assertEquals($french->getRevisionId(), $french->getLoadedRevisionId());
    $this->assertEquals($loaded->getRevisionId(), $loaded->getLoadedRevisionId());
  }

  /**
   * Tests re-saving the entity in entity_test_entity_insert().
   */
  public function testSaveInHookEntityInsert() {
    // Create an entity which will be saved again in entity_test_entity_insert().
    $entity = EntityTestMulRev::create(['name' => 'EntityLoadedRevisionTest']);
    $entity->save();
    $loadedRevisionId = \Drupal::state()->get('entity_test.loadedRevisionId');
    $this->assertEquals($entity->getLoadedRevisionId(), $loadedRevisionId);
    $this->assertEquals($entity->getRevisionId(), $entity->getLoadedRevisionId());
  }

  /**
   * Tests that latest revisions are working as expected.
   *
   * @covers ::isLatestRevision
   */
  public function testIsLatestRevision() {
    // Create a basic EntityTestMulRev entity and save it.
    $entity = EntityTestMulRev::create();
    $entity->save();
    $this->assertTrue($entity->isLatestRevision());

    // Load the created entity and create a new pending revision.
    $pending_revision = EntityTestMulRev::load($entity->id());
    $pending_revision->setNewRevision(TRUE);
    $pending_revision->isDefaultRevision(FALSE);

    // The pending revision should still be marked as the latest one before it
    // is saved.
    $this->assertTrue($pending_revision->isLatestRevision());
    $pending_revision->save();
    $this->assertTrue($pending_revision->isLatestRevision());

    // Load the default revision and check that it is not marked as the latest
    // revision.
    $default_revision = EntityTestMulRev::load($entity->id());
    $this->assertFalse($default_revision->isLatestRevision());
  }

  /**
   * Tests that latest affected revisions are working as expected.
   *
   * The latest revision affecting a particular translation behaves as the
   * latest revision for monolingual entities.
   *
   * @covers ::isLatestTranslationAffectedRevision
   * @covers \Drupal\Core\Entity\ContentEntityStorageBase::getLatestRevisionId
   * @covers \Drupal\Core\Entity\ContentEntityStorageBase::getLatestTranslationAffectedRevisionId
   */
  public function testIsLatestAffectedRevisionTranslation() {
    ConfigurableLanguage::createFromLangcode('it')->save();

    // Create a basic EntityTestMulRev entity and save it.
    $entity = EntityTestMulRev::create();
    $entity->setName($this->randomString());
    $entity->save();
    $this->assertTrue($entity->isLatestTranslationAffectedRevision());

    // Load the created entity and create a new pending revision.
    $pending_revision = EntityTestMulRev::load($entity->id());
    $pending_revision->setName($this->randomString());
    $pending_revision->setNewRevision(TRUE);
    $pending_revision->isDefaultRevision(FALSE);

    // Check that no revision affecting Italian is available, given that no
    // Italian translation has been created yet.
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $this->assertNull($storage->getLatestTranslationAffectedRevisionId($entity->id(), 'it'));
    $this->assertEquals($pending_revision->getLoadedRevisionId(), $storage->getLatestRevisionId($entity->id()));

    // The pending revision should still be marked as the latest affected one
    // before it is saved.
    $this->assertTrue($pending_revision->isLatestTranslationAffectedRevision());
    $pending_revision->save();
    $this->assertTrue($pending_revision->isLatestTranslationAffectedRevision());

    // Load the default revision and check that it is not marked as the latest
    // (translation-affected) revision.
    $default_revision = EntityTestMulRev::load($entity->id());
    $this->assertFalse($default_revision->isLatestRevision());
    $this->assertFalse($default_revision->isLatestTranslationAffectedRevision());

    // Add a translation in a new pending revision and verify that both the
    // English and Italian revision translations are the latest affected
    // revisions for their respective languages, while the English revision is
    // not the latest revision.
    /** @var \Drupal\entity_test\Entity\EntityTestMulRev $en_revision */
    $en_revision = clone $pending_revision;
    /** @var \Drupal\entity_test\Entity\EntityTestMulRev $it_revision */
    $it_revision = $pending_revision->addTranslation('it');
    $it_revision->setName($this->randomString());
    $it_revision->setNewRevision(TRUE);
    $it_revision->isDefaultRevision(FALSE);
    // @todo Remove this once the "original" property works with revisions. See
    //   https://www.drupal.org/project/drupal/issues/2859042.
    $it_revision->original = $storage->loadRevision($it_revision->getLoadedRevisionId());
    $it_revision->save();
    $this->assertTrue($it_revision->isLatestRevision());
    $this->assertTrue($it_revision->isLatestTranslationAffectedRevision());
    $this->assertFalse($en_revision->isLatestRevision());
    $this->assertTrue($en_revision->isLatestTranslationAffectedRevision());
  }

  /**
   * Tests the automatic handling of the "revision_default" flag.
   *
   * @covers \Drupal\Core\Entity\ContentEntityStorageBase::doSave
   */
  public function testDefaultRevisionFlag() {
    // Create a basic EntityTestMulRev entity and save it.
    $entity = EntityTestMulRev::create();
    $entity->save();
    $this->assertTrue($entity->wasDefaultRevision());

    // Create a new default revision.
    $entity->setNewRevision(TRUE);
    $entity->save();
    $this->assertTrue($entity->wasDefaultRevision());

    // Create a new non-default revision.
    $entity->setNewRevision(TRUE);
    $entity->isDefaultRevision(FALSE);
    $entity->save();
    $this->assertFalse($entity->wasDefaultRevision());

    // Turn the previous non-default revision into a default revision.
    $entity->isDefaultRevision(TRUE);
    $entity->save();
    $this->assertTrue($entity->wasDefaultRevision());
  }

}
