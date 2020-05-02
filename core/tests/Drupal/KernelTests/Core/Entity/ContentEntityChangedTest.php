<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\entity_test\Entity\EntityTestMulChanged;
use Drupal\entity_test\Entity\EntityTestMulRevChanged;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests basic EntityChangedInterface functionality.
 *
 * @group Entity
 */
class ContentEntityChangedTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'language',
    'user',
    'system',
    'field',
    'text',
    'filter',
    'entity_test',
  ];

  /**
   * The EntityTestMulChanged entity type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mulChangedStorage;

  /**
   * The EntityTestMulRevChanged entity type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mulRevChangedStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Enable an additional language.
    ConfigurableLanguage::createFromLangcode('de')->save();
    ConfigurableLanguage::createFromLangcode('fr')->save();

    $this->installEntitySchema('entity_test_mul_changed');
    $this->installEntitySchema('entity_test_mulrev_changed');

    $this->mulChangedStorage = $this->entityTypeManager->getStorage('entity_test_mul_changed');
    $this->mulRevChangedStorage = $this->entityTypeManager->getStorage('entity_test_mulrev_changed');
  }

  /**
   * Tests basic EntityChangedInterface functionality.
   */
  public function testChanged() {
    $user1 = $this->createUser();
    $user2 = $this->createUser();

    // Create a test entity.
    $entity = EntityTestMulChanged::create([
      'name' => $this->randomString(),
      'not_translatable' => $this->randomString(),
      'user_id' => $user1->id(),
      'language' => 'en',
    ]);
    $entity->save();

    $this->assertTrue(
      $entity->getChangedTime() >= REQUEST_TIME,
      'Changed time of original language is valid.'
    );

    // We can't assert equality here because the created time is set to the
    // request time, while instances of ChangedTestItem use the current
    // timestamp every time. Therefore we check if the changed timestamp is
    // between the created time and now.
    $this->assertTrue(
      ($entity->getChangedTime() >= $entity->get('created')->value) &&
      (($entity->getChangedTime() - $entity->get('created')->value) <= time() - REQUEST_TIME),
      'Changed and created time of original language can be assumed to be identical.'
    );

    $this->assertEqual(
      $entity->getChangedTime(), $entity->getChangedTimeAcrossTranslations(),
      'Changed time of original language is the same as changed time across all translations.'
    );

    $changed_en = $entity->getChangedTime();

    /** @var \Drupal\entity_test\Entity\EntityTestMulRevChanged $german */
    $german = $entity->addTranslation('de');

    $entity->save();

    $this->assertEqual(
      $entity->getChangedTime(), $changed_en,
      'Changed time of original language did not change.'
    );

    $this->assertTrue(
      $german->getChangedTime() > $entity->getChangedTime(),
      'Changed time of the German translation is newer then the original language.'
    );

    $this->assertEqual(
      $german->getChangedTime(), $entity->getChangedTimeAcrossTranslations(),
      'Changed time of the German translation is the newest time across all translations.'
    );

    $changed_de = $german->getChangedTime();

    $entity->save();

    $this->assertEqual(
      $entity->getChangedTime(), $changed_en,
      'Changed time of original language did not change.'
    );

    $this->assertEqual(
      $german->getChangedTime(), $changed_de,
      'Changed time of the German translation did not change.'
    );

    // Update a non-translatable field to make sure that the changed timestamp
    // is updated for all translations.
    $entity->set('not_translatable', $this->randomString())->save();

    $this->assertTrue(
      $entity->getChangedTime() > $changed_en,
      'Changed time of original language did change.'
    );

    $this->assertTrue(
      $german->getChangedTime() > $changed_de,
      'Changed time of the German translation did change.'
    );

    $this->assertEquals($entity->getChangedTime(), $german->getChangedTime(), 'When editing a non-translatable field the updated changed time is equal across all translations.');

    $changed_en = $entity->getChangedTime();
    $changed_de = $german->getChangedTime();

    $entity->setOwner($user2);

    $entity->save();

    $this->assertTrue(
      $entity->getChangedTime() > $changed_en,
      'Changed time of original language did change.'
    );

    $this->assertEqual(
      $german->getChangedTime(), $changed_de,
      'Changed time of the German translation did not change.'
    );

    $this->assertTrue(
      $entity->getChangedTime() > $german->getChangedTime(),
      'Changed time of original language is newer then the German translation.'
    );

    $this->assertEqual(
      $entity->getChangedTime(), $entity->getChangedTimeAcrossTranslations(),
      'Changed time of the original language is the newest time across all translations.'
    );

    $changed_en = $entity->getChangedTime();

    // Save entity without any changes.
    $entity->save();

    $this->assertEqual(
      $entity->getChangedTime(), $changed_en,
      'Changed time of original language did not change.'
    );

    $this->assertEqual(
      $german->getChangedTime(), $changed_de,
      'Changed time of the German translation did not change.'
    );

    // At this point the changed time of the original language (en) is newer
    // than the changed time of the German translation. Now test that entity
    // queries work as expected.
    $query = $this->mulChangedStorage->getQuery();
    $ids = $query->condition('changed', $changed_en)->execute();

    $this->assertEqual(
      reset($ids), $entity->id(),
      'Entity query can access changed time of original language.'
    );

    $query = $this->mulChangedStorage->getQuery();
    $ids = $query->condition('changed', $changed_en, '=', 'en')->execute();

    $this->assertEqual(
      reset($ids), $entity->id(),
      'Entity query can access changed time of original language by setting the original language as condition.'
    );

    $query = $this->mulChangedStorage->getQuery();
    $ids = $query->condition('changed', $changed_de, '=', 'en')->execute();

    $this->assertEmpty(
      $ids,
      'There\'s no original entity stored having the changed time of the German translation.'
    );

    $query = $this->mulChangedStorage->getQuery();
    $ids = $query->condition('changed', $changed_en)->condition('default_langcode', '1')->execute();

    $this->assertEqual(
      reset($ids), $entity->id(),
      'Entity query can access changed time of default language.'
    );

    $query = $this->mulChangedStorage->getQuery();
    $ids = $query->condition('changed', $changed_de)->condition('default_langcode', '1')->execute();

    $this->assertEmpty(
      $ids,
      'There\'s no entity stored using the default language having the changed time of the German translation.'
    );

    $query = $this->mulChangedStorage->getQuery();
    $ids = $query->condition('changed', $changed_de)->execute();

    $this->assertEqual(
      reset($ids), $entity->id(),
      'Entity query can access changed time of the German translation.'
    );

    $query = $this->mulChangedStorage->getQuery();
    $ids = $query->condition('changed', $changed_de, '=', 'de')->execute();

    $this->assertEqual(
      reset($ids), $entity->id(),
      'Entity query can access changed time of the German translation.'
    );

    $query = $this->mulChangedStorage->getQuery();
    $ids = $query->condition('changed', $changed_en, '=', 'de')->execute();

    $this->assertEmpty(
      $ids,
      'There\'s no German translation stored having the changed time of the original language.'
    );

    $query = $this->mulChangedStorage->getQuery();
    $ids = $query->condition('changed', $changed_de, '>')->execute();

    $this->assertEqual(
      reset($ids), $entity->id(),
      'Entity query can access changed time regardless of translation.'
    );

    $query = $this->mulChangedStorage->getQuery();
    $ids = $query->condition('changed', $changed_en, '<')->execute();

    $this->assertEqual(
      reset($ids), $entity->id(),
      'Entity query can access changed time regardless of translation.'
    );

    $query = $this->mulChangedStorage->getQuery();
    $ids = $query->condition('changed', 0, '>')->execute();

    $this->assertEqual(
      reset($ids), $entity->id(),
      'Entity query can access changed time regardless of translation.'
    );

    $query = $this->mulChangedStorage->getQuery();
    $ids = $query->condition('changed', $changed_en, '>')->execute();

    $this->assertEmpty(
      $ids,
      'Entity query can access changed time regardless of translation.'
    );
  }

  /**
   * Tests revisionable EntityChangedInterface functionality.
   */
  public function testRevisionChanged() {
    $user1 = $this->createUser();
    $user2 = $this->createUser();

    // Create a test entity.
    $entity = EntityTestMulRevChanged::create([
      'name' => $this->randomString(),
      'user_id' => $user1->id(),
      'language' => 'en',
    ]);
    $entity->save();

    $this->assertTrue(
      $entity->getChangedTime() >= REQUEST_TIME,
      'Changed time of original language is valid.'
    );

    // We can't assert equality here because the created time is set to the
    // request time while instances of ChangedTestItem use the current
    // timestamp every time.
    $this->assertTrue(
      ($entity->getChangedTime() >= $entity->get('created')->value) &&
      (($entity->getChangedTime() - $entity->get('created')->value) <= time() - REQUEST_TIME),
      'Changed and created time of original language can be assumed to be identical.'
    );

    $this->assertEqual(
      $entity->getChangedTime(), $entity->getChangedTimeAcrossTranslations(),
      'Changed time of original language is the same as changed time across all translations.'
    );

    $this->assertTrue(
      $this->getRevisionTranslationAffectedFlag($entity),
      'Changed flag of original language is set for a new entity.'
    );

    $changed_en = $entity->getChangedTime();

    $entity->setNewRevision();
    // Save entity without any changes but create new revision.
    $entity->save();
    // A new revision without any changes should not set a new changed time.
    $this->assertEqual(
      $entity->getChangedTime(), $changed_en,
      'Changed time of original language did not change.'
    );

    $this->assertFalse(
      $this->getRevisionTranslationAffectedFlag($entity),
      'Changed flag of original language is not set for new revision without changes.'
    );

    $entity->setNewRevision();
    $entity->setOwner($user2);
    $entity->save();

    $this->assertTrue(
      $entity->getChangedTime() > $changed_en,
      'Changed time of original language has been updated by new revision.'
    );

    $this->assertTrue(
      $this->getRevisionTranslationAffectedFlag($entity),
      'Changed flag of original language is set for new revision with changes.'
    );

    $changed_en = $entity->getChangedTime();

    /** @var \Drupal\entity_test\Entity\EntityTestMulRevChanged $german */
    $german = $entity->addTranslation('de');

    $entity->save();

    $this->assertEqual(
      $entity->getChangedTime(), $changed_en,
      'Changed time of original language did not change.'
    );

    $this->assertTrue(
      $german->getChangedTime() > $entity->getChangedTime(),
      'Changed time of the German translation is newer then the original language.'
    );

    $this->assertEqual(
      $german->getChangedTime(), $entity->getChangedTimeAcrossTranslations(),
      'Changed time of the German translation is the newest time across all translations.'
    );

    $this->assertTrue(
      $this->getRevisionTranslationAffectedFlag($entity),
      'Changed flag of original language is not reset by adding a new translation.'
    );

    $this->assertTrue(
      $this->getRevisionTranslationAffectedFlag($german),
      'Changed flag of German translation is set when adding the translation.'
    );

    $changed_de = $german->getChangedTime();

    $entity->setNewRevision();
    // Save entity without any changes but create new revision.
    $entity->save();

    $this->assertEqual(
      $entity->getChangedTime(), $changed_en,
      'Changed time of original language did not change.'
    );

    $this->assertEqual(
      $german->getChangedTime(), $changed_de,
      'Changed time of the German translation did not change.'
    );

    $this->assertFalse(
      $this->getRevisionTranslationAffectedFlag($entity),
      'Changed flag of original language is not set for new revision without changes.'
    );

    $this->assertFalse(
      $this->getRevisionTranslationAffectedFlag($german),
      'Changed flag of the German translation is not set for new revision without changes.'
    );

    $entity->setNewRevision();
    $german->setOwner($user2);
    $entity->save();

    $this->assertEqual(
      $entity->getChangedTime(), $changed_en,
      'Changed time of original language did not change.'
    );

    $this->assertTrue(
      $german->getChangedTime() > $changed_de,
      'Changed time of the German translation did change.'
    );

    $this->assertEqual(
      $german->getChangedTime(), $entity->getChangedTimeAcrossTranslations(),
      'Changed time of the German translation is the newest time across all translations.'
    );

    $this->assertFalse(
      $this->getRevisionTranslationAffectedFlag($entity),
      'Changed flag of original language is not set when changing the German Translation.'
    );

    $this->assertTrue(
      $this->getRevisionTranslationAffectedFlag($german),
      'Changed flag of German translation is set when changing the German translation.'
    );

    $french = $entity->addTranslation('fr');

    $entity->setNewRevision();
    $entity->save();

    $this->assertEqual(
      $entity->getChangedTime(), $changed_en,
      'Changed time of original language did not change.'
    );

    $this->assertTrue(
      $french->getChangedTime() > $entity->getChangedTime(),
      'Changed time of the French translation is newer then the original language.'
    );

    $this->assertTrue(
      $french->getChangedTime() > $entity->getChangedTime(),
      'Changed time of the French translation is newer then the German translation.'
    );

    $this->assertEqual(
      $french->getChangedTime(), $entity->getChangedTimeAcrossTranslations(),
      'Changed time of the French translation is the newest time across all translations.'
    );

    $this->assertFalse(
      $this->getRevisionTranslationAffectedFlag($entity),
      'Changed flag of original language is reset by adding a new translation and a new revision.'
    );

    $this->assertFalse(
      $this->getRevisionTranslationAffectedFlag($german),
      'Changed flag of German translation is reset by adding a new translation and a new revision.'
    );

    $this->assertTrue(
      $this->getRevisionTranslationAffectedFlag($french),
      'Changed flag of French translation is set when adding the translation and a new revision.'
    );

    $entity->removeTranslation('fr');

    $entity->setNewRevision();
    $entity->save();

    // This block simulates exactly the flow of a node form submission of a new
    // translation and a new revision.
    $form_entity_builder_entity = EntityTestMulRevChanged::load($entity->id());
    // ContentTranslationController::prepareTranslation().
    $form_entity_builder_entity = $form_entity_builder_entity->addTranslation('fr', $form_entity_builder_entity->toArray());
    // EntityForm::buildEntity() during form submit.
    $form_entity_builder_clone = clone $form_entity_builder_entity;
    // NodeForm::submitForm().
    $form_entity_builder_clone->setNewRevision();
    // EntityForm::save().
    $form_entity_builder_clone->save();

    // The assertion fails unless https://www.drupal.org/node/2513094 is
    // committed.
    $this->assertFalse(
      $this->getRevisionTranslationAffectedFlag($entity),
      'Changed flag of original language is reset by adding a new translation and a new revision.'
    );

    $this->assertFalse(
      $this->getRevisionTranslationAffectedFlag($german),
      'Changed flag of German translation is reset by adding a new translation and a new revision.'
    );

    $this->assertTrue(
      $this->getRevisionTranslationAffectedFlag($french),
      'Changed flag of French translation is set when adding the translation and a new revision.'
    );

    // Since above a clone of the entity was saved and then this entity is saved
    // again, we have to update the revision ID to the current one.
    $german->set('revision_id', $form_entity_builder_clone->getRevisionId());
    $german->updateLoadedRevisionId();
    $german->setOwner($user1);
    $german->setRevisionTranslationAffected(FALSE);
    $entity->save();

    $this->assertFalse(
      $this->getRevisionTranslationAffectedFlag($german),
      'German translation changed but the changed flag is reset manually.'
    );

    $entity->setNewRevision();
    $german->setRevisionTranslationAffected(TRUE);
    $entity->save();

    $this->assertTrue(
      $this->getRevisionTranslationAffectedFlag($german),
      'German translation is not changed and a new revision is created but the changed flag is set manually.'
    );

    $german->setOwner($user2);
    $entity->setNewRevision();
    $german->setRevisionTranslationAffected(FALSE);
    $entity->save();

    $this->assertFalse(
      $this->getRevisionTranslationAffectedFlag($german),
      'German translation changed and a new revision is created but the changed flag is reset manually.'
    );

  }

  /**
   * Retrieves the revision translation affected flag value.
   *
   * @param \Drupal\entity_test\Entity\EntityTestMulRevChanged $entity
   *   The entity object to be checked.
   *
   * @return bool
   *   The flag value.
   */
  protected function getRevisionTranslationAffectedFlag(EntityTestMulRevChanged $entity) {
    $query = $this->mulRevChangedStorage->getQuery();
    $ids = $query->condition('revision_translation_affected', 1, '=', $entity->language()->getId())->execute();
    $id = reset($ids);
    return (bool) ($id == $entity->id());
  }

}
