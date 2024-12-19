<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

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
  protected static $modules = ['language', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable an additional language.
    ConfigurableLanguage::createFromLangcode('de')->save();

    $this->installEntitySchema('entity_test_mul');
    $this->installEntitySchema('entity_test_mulrev');
  }

  /**
   * Tests if entity references on fields are still correct after cloning.
   */
  public function testFieldEntityReferenceAfterClone(): void {
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

    $this->assertEquals($entity->getTranslationLanguages(), $clone->getTranslationLanguages(), 'The entity and its clone have the same translation languages.');

    $default_langcode = $entity->getUntranslated()->language()->getId();
    foreach (array_keys($clone->getTranslationLanguages()) as $langcode) {
      $translation = $clone->getTranslation($langcode);
      foreach ($translation->getFields() as $field_name => $field) {
        if ($field->getFieldDefinition()->isTranslatable()) {
          $this->assertEquals($langcode, $field->getEntity()->language()->getId(), "Translatable field $field_name on translation $langcode has correct entity reference in translation $langcode after cloning.");
          $this->assertSame($translation, $field->getEntity(), "Translatable field $field_name on translation $langcode has correct reference to the cloned entity object.");
        }
        else {
          $this->assertEquals($default_langcode, $field->getEntity()->language()->getId(), "Non translatable field $field_name on translation $langcode has correct entity reference in the default translation $default_langcode after cloning.");
          $this->assertSame($translation->getUntranslated(), $field->getEntity(), "Non translatable field $field_name on translation $langcode has correct reference to the cloned entity object in the default translation $default_langcode.");
        }
      }
    }
  }

  /**
   * Tests that the flag for enforcing a new entity is not shared.
   */
  public function testEnforceIsNewOnClonedEntityTranslation(): void {
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
  public function testClonedEntityFields(): void {
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
  public function testNewRevisionOnCloneEntityTranslation(): void {
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

  /**
   * Tests modifications on entity keys of a cloned entity object.
   */
  public function testEntityKeysModifications(): void {
    // Create a test entity with a translation, which will internally trigger
    // entity cloning for the new translation and create references for some of
    // the entity properties.
    $entity = EntityTestMulRev::create([
      'name' => 'original-name',
      'uuid' => 'original-uuid',
      'language' => 'en',
    ]);
    $entity->addTranslation('de');
    $entity->save();

    // Clone the entity.
    $clone = clone $entity;

    // Alter a non-translatable and a translatable entity key fields of the
    // cloned entity and assert that retrieving the value through the entity
    // keys local cache will be different for the cloned and the original
    // entity.
    // We first have to call the ::uuid() and ::label() method on the original
    // entity as it is going to cache the field values into the $entityKeys and
    // $translatableEntityKeys properties of the entity object and we want to
    // check that the cloned and the original entity aren't sharing the same
    // reference to those local cache properties.
    $uuid_field_name = $entity->getEntityType()->getKey('uuid');
    $this->assertFalse($entity->getFieldDefinition($uuid_field_name)->isTranslatable());
    $clone->$uuid_field_name->value = 'clone-uuid';
    $this->assertEquals('original-uuid', $entity->uuid());
    $this->assertEquals('clone-uuid', $clone->uuid());

    $label_field_name = $entity->getEntityType()->getKey('label');
    $this->assertTrue($entity->getFieldDefinition($label_field_name)->isTranslatable());
    $clone->$label_field_name->value = 'clone-name';
    $this->assertEquals('original-name', $entity->label());
    $this->assertEquals('clone-name', $clone->label());
  }

  /**
   * Tests the field values after serializing an entity and its clone.
   */
  public function testFieldValuesAfterSerialize(): void {
    // Create a test entity with a translation, which will internally trigger
    // entity cloning for the new translation and create references for some of
    // the entity properties.
    $entity = EntityTestMulRev::create([
      'name' => 'original',
      'language' => 'en',
    ]);
    $entity->addTranslation('de');
    $entity->save();

    // Clone the entity.
    $clone = clone $entity;

    // Alter the name field value of the cloned entity object.
    $clone->setName('clone');

    // Serialize the entity and the cloned object in order to destroy the field
    // objects and put the field values into the entity property $values, so
    // that on accessing a field again it will be newly created with the value
    // from the $values property.
    serialize($entity);
    serialize($clone);

    // Assert that the original and the cloned entity both have different names.
    $this->assertEquals('original', $entity->getName());
    $this->assertEquals('clone', $clone->getName());
  }

  /**
   * Tests changing the default revision flag.
   */
  public function testDefaultRevision(): void {
    // Create a test entity with a translation, which will internally trigger
    // entity cloning for the new translation and create references for some of
    // the entity properties.
    $entity = EntityTestMulRev::create([
      'name' => 'original',
      'language' => 'en',
    ]);
    $entity->addTranslation('de');
    $entity->save();

    // Assert that the entity is in the default revision.
    $this->assertTrue($entity->isDefaultRevision());

    // Clone the entity and modify its default revision flag.
    $clone = clone $entity;
    $clone->isDefaultRevision(FALSE);

    // Assert that the clone is not in default revision, but the original entity
    // is still in the default revision.
    $this->assertFalse($clone->isDefaultRevision());
    $this->assertTrue($entity->isDefaultRevision());
  }

  /**
   * Tests references of entity properties after entity cloning.
   */
  public function testEntityPropertiesModifications(): void {
    // Create a test entity with a translation, which will internally trigger
    // entity cloning for the new translation and create references for some of
    // the entity properties.
    $entity = EntityTestMulRev::create([
      'name' => 'original',
      'language' => 'en',
    ]);
    $translation = $entity->addTranslation('de');
    $entity->save();

    // Clone the entity.
    $clone = clone $entity;

    // Retrieve the entity properties.
    $reflection = new \ReflectionClass($entity);
    $properties = $reflection->getProperties(~\ReflectionProperty::IS_STATIC);
    $translation_unique_properties = ['activeLangcode', 'translationInitialize', 'fieldDefinitions', 'languages', 'langcodeKey', 'defaultLangcode', 'defaultLangcodeKey', 'revisionTranslationAffectedKey', 'validated', 'validationRequired', 'entityTypeId', 'typedData', 'cacheContexts', 'cacheTags', 'cacheMaxAge', '_serviceIds', '_entityStorages', 'enforceDefaultTranslation'];

    foreach ($properties as $property) {
      // The original property is typed and can not be set to a string.
      if ($property->getName() === 'originalEntity') {
        continue;
      }

      $property_name = $property->getName();

      // Modify each entity property on the clone and assert that the change is
      // not propagated to the original entity.
      $property->setValue($entity, 'default-value');
      $property->setValue($translation, 'default-value');
      $property->setValue($clone, 'test-entity-cloning');
      // Static properties remain the same across all instances of the class.
      if ($property->isStatic()) {
        $this->assertEquals('test-entity-cloning', $property->getValue($entity), "Entity property $property_name is not cloned properly.");
        $this->assertEquals('test-entity-cloning', $property->getValue($translation), "Entity property $property_name is not cloned properly.");
        $this->assertEquals('test-entity-cloning', $property->getValue($clone), "Entity property $property_name is not cloned properly.");
      }
      else {
        $this->assertEquals('default-value', $property->getValue($entity), "Entity property $property_name is not cloned properly.");
        $this->assertEquals('default-value', $property->getValue($translation), "Entity property $property_name is not cloned properly.");
        $this->assertEquals('test-entity-cloning', $property->getValue($clone), "Entity property $property_name is not cloned properly.");
      }

      // Modify each entity property on the translation entity object and assert
      // that the change is propagated to the default translation entity object
      // except for the properties that are unique for each entity translation
      // object.
      $property->setValue($translation, 'test-translation-cloning');
      // Using assertEquals or assertNotEquals here is dangerous as if the
      // assertion fails and the property for some reasons contains the entity
      // object e.g. the "typedData" property then the property will be
      // serialized, but this will cause exceptions because the entity is
      // modified in a non-consistent way and ContentEntityBase::__sleep() will
      // not be able to properly access all properties and this will cause
      // exceptions without a proper backtrace.
      if (in_array($property->getName(), $translation_unique_properties)) {
        $this->assertEquals('default-value', $property->getValue($entity), "Entity property $property_name is not cloned properly.");
        $this->assertEquals('test-translation-cloning', $property->getValue($translation), "Entity property $property_name is not cloned properly.");
      }
      else {
        $this->assertEquals('test-translation-cloning', $property->getValue($entity), "Entity property $property_name is not cloned properly.");
        $this->assertEquals('test-translation-cloning', $property->getValue($translation), "Entity property $property_name is not cloned properly.");
      }
    }
  }

}
