<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests proper cloning of content entities.
 *
 * @group Entity
 */
class ContentEntityCloneTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Enable an additional language.
    ConfigurableLanguage::createFromLangcode('de')->save();

    $this->installEntitySchema('entity_test_mul');
    $this->installEntitySchema('entity_test_mulrev');
  }

  /**
   * Tests if entity references on fields are still correct after cloning.
   */
  public function testFieldEntityReferenceAfterClone() {
    $user = $this->createUser();

    // Create a test entity.
    $entity = EntityTestMul::create([
      'name' => $this->randomString(),
      'user_id' => $user->id(),
      'language' => 'en',
    ]);
    $translation = $entity->addTranslation('de');

    // Initialize the fields on the translation objects in order to check that
    // they are properly cloned and have a reference to the cloned entity
    // object and not to the original one.
    $entity->getFields();
    $translation->getFields();

    $clone = clone $translation;

    $this->assertEqual($entity->getTranslationLanguages(), $clone->getTranslationLanguages(), 'The entity and its clone have the same translation languages.');

    $default_langcode = $entity->getUntranslated()->language()->getId();
    foreach (array_keys($clone->getTranslationLanguages()) as $langcode) {
      $translation = $clone->getTranslation($langcode);
      foreach ($translation->getFields() as $field_name => $field) {
        if ($field->getFieldDefinition()->isTranslatable()) {
          $args = ['%field_name' => $field_name, '%langcode' => $langcode];
          $this->assertEqual($langcode, $field->getEntity()->language()->getId(), format_string('Translatable field %field_name on translation %langcode has correct entity reference in translation %langcode after cloning.', $args));
          $this->assertSame($translation, $field->getEntity(), new FormattableMarkup('Translatable field %field_name on translation %langcode has correct reference to the cloned entity object.', $args));
        }
        else {
          $args = ['%field_name' => $field_name, '%langcode' => $langcode, '%default_langcode' => $default_langcode];
          $this->assertEqual($default_langcode, $field->getEntity()->language()->getId(), format_string('Non translatable field %field_name on translation %langcode has correct entity reference in the default translation %default_langcode after cloning.', $args));
          $this->assertSame($translation->getUntranslated(), $field->getEntity(), new FormattableMarkup('Non translatable field %field_name on translation %langcode has correct reference to the cloned entity object in the default translation %default_langcode.', $args));
        }
      }
    }
  }

  /**
   * Tests that the flag for enforcing a new entity is not shared.
   */
  public function testEnforceIsNewOnClonedEntityTranslation() {
    // Create a test entity.
    $entity = EntityTestMul::create([
      'name' => $this->randomString(),
      'language' => 'en',
    ]);
    $entity->save();
    $entity_translation = $entity->addTranslation('de');
    $entity->save();

    // The entity is not new anymore.
    $this->assertFalse($entity_translation->isNew());

    // The clone should not be new either.
    $clone = clone $entity_translation;
    $this->assertFalse($clone->isNew());

    // After forcing the clone to be new only it should be flagged as new, but
    // the original entity should not.
    $clone->enforceIsNew();
    $this->assertTrue($clone->isNew());
    $this->assertFalse($entity_translation->isNew());
  }

  /**
   * Tests if the entity fields are properly cloned.
   */
  public function testClonedEntityFields() {
    $user = $this->createUser();

    // Create a test entity.
    $entity = EntityTestMul::create([
      'name' => $this->randomString(),
      'user_id' => $user->id(),
      'language' => 'en',
    ]);

    $entity->addTranslation('de');
    $entity->save();
    $fields = array_keys($entity->getFieldDefinitions());

    // Reload the entity, clone it and check that both entity objects reference
    // different field instances.
    $entity = $this->reloadEntity($entity);
    $clone = clone $entity;

    $different_references = TRUE;
    foreach ($fields as $field_name) {
      if ($entity->get($field_name) === $clone->get($field_name)) {
        $different_references = FALSE;
      }
    }
    $this->assertTrue($different_references, 'The entity object and the cloned entity object reference different field item list objects.');


    // Reload the entity, initialize one translation, clone it and check that
    // both entity objects reference different field instances.
    $entity = $this->reloadEntity($entity);
    $entity->getTranslation('de');
    $clone = clone $entity;

    $different_references = TRUE;
    foreach ($fields as $field_name) {
      if ($entity->get($field_name) === $clone->get($field_name)) {
        $different_references = FALSE;
      }
    }
    $this->assertTrue($different_references, 'The entity object and the cloned entity object reference different field item list objects if the entity is cloned after an entity translation has been initialized.');
  }

  /**
   * Tests that the flag for enforcing a new revision is not shared.
   */
  public function testNewRevisionOnCloneEntityTranslation() {
    // Create a test entity.
    $entity = EntityTestMulRev::create([
      'name' => $this->randomString(),
      'language' => 'en',
    ]);
    $entity->save();
    $entity->addTranslation('de');
    $entity->save();

    // Reload the entity as ContentEntityBase::postCreate() forces the entity to
    // be a new revision.
    $entity = EntityTestMulRev::load($entity->id());
    $entity_translation = $entity->getTranslation('de');

    // The entity is not set to be a new revision.
    $this->assertFalse($entity_translation->isNewRevision());

    // The clone should not be set to be a new revision either.
    $clone = clone $entity_translation;
    $this->assertFalse($clone->isNewRevision());

    // After forcing the clone to be a new revision only it should be flagged
    // as a new revision, but the original entity should not.
    $clone->setNewRevision();
    $this->assertTrue($clone->isNewRevision());
    $this->assertFalse($entity_translation->isNewRevision());
  }

}
