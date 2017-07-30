<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests proper revision propagation of entities.
 *
 * @group Entity
 */
class EntityRevisionTranslationTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Enable an additional language.
    ConfigurableLanguage::createFromLangcode('de')->save();

    $this->installEntitySchema('entity_test_mulrev');
  }

  /**
   * Tests if the translation object has the right revision id after new revision.
   */
  public function testNewRevisionAfterTranslation() {
    $user = $this->createUser();

    // Create a test entity.
    $entity = EntityTestMulRev::create([
      'name' => $this->randomString(),
      'user_id' => $user->id(),
      'language' => 'en',
    ]);
    $entity->save();
    $old_rev_id = $entity->getRevisionId();

    $translation = $entity->addTranslation('de');
    $translation->setNewRevision();
    $translation->save();

    $this->assertTrue($translation->getRevisionId() > $old_rev_id, 'The saved translation in new revision has a newer revision id.');
    $this->assertTrue($this->reloadEntity($entity)->getRevisionId() > $old_rev_id, 'The entity from the storage has a newer revision id.');
  }

  /**
   * Tests if the translation object has the right revision id after new revision.
   */
  public function testRevertRevisionAfterTranslation() {
    $user = $this->createUser();
    $storage = $this->entityManager->getStorage('entity_test_mulrev');

    // Create a test entity.
    $entity = EntityTestMulRev::create([
      'name' => $this->randomString(),
      'user_id' => $user->id(),
      'language' => 'en',
    ]);
    $entity->save();
    $old_rev_id = $entity->getRevisionId();

    $translation = $entity->addTranslation('de');
    $translation->setNewRevision();
    $translation->save();

    $entity = $this->reloadEntity($entity);

    $this->assertTrue($entity->hasTranslation('de'));

    $entity = $storage->loadRevision($old_rev_id);

    $entity->setNewRevision();
    $entity->isDefaultRevision(TRUE);
    $entity->save();

    $entity = $this->reloadEntity($entity);

    $this->assertFalse($entity->hasTranslation('de'));
  }

  /**
   * Tests the translation values when saving a pending revision.
   */
  public function testTranslationValuesWhenSavingPendingRevisions() {
    $user = $this->createUser();
    $storage = $this->entityManager->getStorage('entity_test_mulrev');

    // Create a test entity and a translation for it.
    $entity = EntityTestMulRev::create([
      'name' => 'default revision - en',
      'user_id' => $user->id(),
      'language' => 'en',
    ]);
    $entity->addTranslation('de', ['name' => 'default revision - de']);
    $entity->save();

    // Create a pending revision for the entity and change a field value for
    // both languages.
    $pending_revision = $this->reloadEntity($entity);

    $pending_revision->setNewRevision();
    $pending_revision->isDefaultRevision(FALSE);

    $pending_revision->name = 'pending revision - en';
    $pending_revision->save();

    $pending_revision_translation = $pending_revision->getTranslation('de');
    $pending_revision_translation->name = 'pending revision - de';
    $pending_revision_translation->save();

    $pending_revision_id = $pending_revision->getRevisionId();
    $pending_revision = $storage->loadRevision($pending_revision_id);

    // Change the value of the field in the default language, save the pending
    // revision and check that the value of the field in the second language is
    // also taken from the pending revision, *not* from the default revision.
    $pending_revision->name = 'updated pending revision - en';
    $pending_revision->save();

    $pending_revision = $storage->loadRevision($pending_revision_id);

    $this->assertEquals($pending_revision->name->value, 'updated pending revision - en');
    $this->assertEquals($pending_revision->getTranslation('de')->name->value, 'pending revision - de');
  }

  /**
   * Tests changing the default revision flag is propagated to all translations.
   */
  public function testDefaultRevision() {
    // Create a test entity with a translation, which will internally trigger
    // entity cloning for the new translation and create references for some of
    // the entity properties.
    $entity = EntityTestMulRev::create([
      'name' => 'original',
      'language' => 'en',
    ]);
    $translation = $entity->addTranslation('de');
    $entity->save();

    // Assert that the entity is in the default revision.
    $this->assertTrue($entity->isDefaultRevision());
    $this->assertTrue($translation->isDefaultRevision());

    // Change the default revision flag on one of the entity translations and
    // assert that the change is propagated to all entity translation objects.
    $translation->isDefaultRevision(FALSE);
    $this->assertFalse($entity->isDefaultRevision());
    $this->assertFalse($translation->isDefaultRevision());
  }

}
