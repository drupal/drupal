<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the loaded Revision of an entity.
 *
 * @group entity
 */
class EntityLoadedRevisionTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'entity_test',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_mulrev');
  }

  /**
   * Test getLoadedRevisionId() returns the correct ID throughout the process.
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

}
