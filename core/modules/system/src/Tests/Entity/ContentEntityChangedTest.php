<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\ContentEntityChangedTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests basic EntityChangedInterface functionality.
 *
 * @group Entity
 */
class ContentEntityChangedTest extends EntityUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['language', 'user', 'system', 'field', 'text', 'filter', 'entity_test'];

  /**
   * @inheritdoc
   */
  protected function setUp() {
    parent::setUp();

    // Enable an additional language.
    ConfigurableLanguage::createFromLangcode('de')->save();

    $this->installEntitySchema('entity_test_mul_changed');
    $this->installEntitySchema('entity_test_mulrev_changed');
  }

  /**
   * Tests basic EntityChangedInterface functionality.
   */
  public function testChanged() {
    $user1 = $this->createUser();
    $user2 = $this->createUser();

    // Create some test entities.
    $entity = entity_create('entity_test_mul_changed', array(
      'name' => $this->randomString(),
      'user_id' => $user1->id(),
      'language' => 'en',
    ));
    $entity->save();

    $this->assertTrue(
      $entity->getChangedTime() >= REQUEST_TIME,
      'Changed time of original language is valid.'
    );

    // We can't assert equality here because the created time is set to the
    // request time, while instances of ChangedTestItem use the current
    // timestamp every time. Therefor we check if the changed timestamp is
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
    $storage = $this->entityManager->getStorage('entity_test_mul_changed');

    $query = $storage->getQuery();
    $ids = $query->condition('changed', $changed_en)->execute();

    $this->assertEqual(
      reset($ids), $entity->id(),
      'Entity query can access changed time of original language.'
    );

    $query = $storage->getQuery();
    $ids = $query->condition('changed', $changed_en, '=', 'en')->execute();

    $this->assertEqual(
      reset($ids), $entity->id(),
      'Entity query can access changed time of original language by setting the original language as condition.'
    );

    $query = $storage->getQuery();
    $ids = $query->condition('changed', $changed_de, '=', 'en')->execute();

    $this->assertFalse(
      $ids,
      'There\'s no original entity stored having the changed time of the German translation.'
    );

    $query = $storage->getQuery();
    $ids = $query->condition('changed', $changed_en)->condition('default_langcode', '1')->execute();

    $this->assertEqual(
      reset($ids), $entity->id(),
      'Entity query can access changed time of default language.'
    );

    $query = $storage->getQuery();
    $ids = $query->condition('changed', $changed_de)->condition('default_langcode', '1')->execute();

    $this->assertFalse(
      $ids,
      'There\'s no entity stored using the default language having the changed time of the German translation.'
    );

    $query = $storage->getQuery();
    $ids = $query->condition('changed', $changed_de)->execute();

    $this->assertEqual(
      reset($ids), $entity->id(),
      'Entity query can access changed time of the German translation.'
    );

    $query = $storage->getQuery();
    $ids = $query->condition('changed', $changed_de, '=', 'de')->execute();

    $this->assertEqual(
      reset($ids), $entity->id(),
      'Entity query can access changed time of the German translation.'
    );

    $query = $storage->getQuery();
    $ids = $query->condition('changed', $changed_en, '=', 'de')->execute();

    $this->assertFalse(
      $ids,
      'There\'s no German translation stored having the changed time of the original language.'
    );

    $query = $storage->getQuery();
    $ids = $query->condition('changed', $changed_de, '>')->execute();

    $this->assertEqual(
      reset($ids), $entity->id(),
      'Entity query can access changed time regardless of translation.'
    );

    $query = $storage->getQuery();
    $ids = $query->condition('changed', $changed_en, '<')->execute();

    $this->assertEqual(
      reset($ids), $entity->id(),
      'Entity query can access changed time regardless of translation.'
    );

    $query = $storage->getQuery();
    $ids = $query->condition('changed', 0, '>')->execute();

    $this->assertEqual(
      reset($ids), $entity->id(),
      'Entity query can access changed time regardless of translation.'
    );

    $query = $storage->getQuery();
    $ids = $query->condition('changed', $changed_en, '>')->execute();

    $this->assertFalse(
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

    // Create some test entities.
    $entity = entity_create('entity_test_mulrev_changed', array(
      'name' => $this->randomString(),
      'user_id' => $user1->id(),
      'language' => 'en',
    ));
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

    $changed_en = $entity->getChangedTime();

    $entity->setNewRevision();
    // Save entity without any changes but create new revision.
    $entity->save();
    // A new revision without any changes should not set a new changed time.
    $this->assertEqual(
      $entity->getChangedTime(), $changed_en,
      'Changed time of original language did not change.'
    );

    $entity->setOwner($user2);
    $entity->setNewRevision();
    $entity->save();

    $this->assertTrue(
      $entity->getChangedTime() > $changed_en,
      'Changed time of original language has been updated by new revision.'
    );

    $changed_en = $entity->getChangedTime();

    $entity->addTranslation('de');

    $german = $entity->getTranslation('de');

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

    $german->setOwner($user2);
    $entity->setNewRevision();
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
  }

}
