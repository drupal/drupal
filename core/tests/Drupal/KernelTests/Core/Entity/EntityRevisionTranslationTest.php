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
   * Tests the translation values when saving a forward revision.
   */
  public function testTranslationValuesWhenSavingForwardRevisions() {
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

    // Create a forward revision for the entity and change a field value for
    // both languages.
    $forward_revision = $this->reloadEntity($entity);

    $forward_revision->setNewRevision();
    $forward_revision->isDefaultRevision(FALSE);

    $forward_revision->name = 'forward revision - en';
    $forward_revision->save();

    $forward_revision_translation = $forward_revision->getTranslation('de');
    $forward_revision_translation->name = 'forward revision - de';
    $forward_revision_translation->save();

    $forward_revision_id = $forward_revision->getRevisionId();
    $forward_revision = $storage->loadRevision($forward_revision_id);

    // Change the value of the field in the default language, save the forward
    // revision and check that the value of the field in the second language is
    // also taken from the forward revision, *not* from the default revision.
    $forward_revision->name = 'updated forward revision - en';
    $forward_revision->save();

    $forward_revision = $storage->loadRevision($forward_revision_id);

    $this->assertEquals($forward_revision->name->value, 'updated forward revision - en');
    $this->assertEquals($forward_revision->getTranslation('de')->name->value, 'forward revision - de');
  }

}
