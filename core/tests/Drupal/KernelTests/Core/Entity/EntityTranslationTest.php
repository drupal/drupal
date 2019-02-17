<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\TypedData\TranslationStatusInterface;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests entity translation functionality.
 *
 * @group Entity
 */
class EntityTranslationTest extends EntityLanguageTestBase {

  /**
   * Tests language related methods of the Entity class.
   */
  public function testEntityLanguageMethods() {
    // All entity variations have to have the same results.
    foreach (entity_test_entity_types() as $entity_type) {
      $this->doTestEntityLanguageMethods($entity_type);
    }
  }

  /**
   * Executes the entity language method tests for the given entity type.
   *
   * @param string $entity_type
   *   The entity type to run the tests with.
   */
  protected function doTestEntityLanguageMethods($entity_type) {
    $langcode_key = $this->entityManager->getDefinition($entity_type)->getKey('langcode');
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create([
        'name' => 'test',
        'user_id' => $this->container->get('current_user')->id(),
      ]);
    $this->assertEqual($entity->language()->getId(), $this->languageManager->getDefaultLanguage()->getId(), format_string('%entity_type: Entity created with API has default language.', ['%entity_type' => $entity_type]));
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create([
        'name' => 'test',
        'user_id' => \Drupal::currentUser()->id(),
        $langcode_key => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ]);

    $this->assertEqual($entity->language()->getId(), LanguageInterface::LANGCODE_NOT_SPECIFIED, format_string('%entity_type: Entity language not specified.', ['%entity_type' => $entity_type]));
    $this->assertFalse($entity->getTranslationLanguages(FALSE), format_string('%entity_type: No translations are available', ['%entity_type' => $entity_type]));

    // Set the value in default language.
    $entity->set($this->fieldName, [0 => ['value' => 'default value']]);
    // Get the value.
    $field = $entity->getTranslation(LanguageInterface::LANGCODE_DEFAULT)->get($this->fieldName);
    $this->assertEqual($field->value, 'default value', format_string('%entity_type: Untranslated value retrieved.', ['%entity_type' => $entity_type]));
    $this->assertEqual($field->getLangcode(), LanguageInterface::LANGCODE_NOT_SPECIFIED, format_string('%entity_type: Field object has the expected langcode.', ['%entity_type' => $entity_type]));

    // Try to get add a translation to language neutral entity.
    $message = 'Adding a translation to a language-neutral entity results in an error.';
    try {
      $entity->addTranslation($this->langcodes[1]);
      $this->fail($message);
    }
    catch (\InvalidArgumentException $e) {
      $this->pass($message);
    }

    // Now, make the entity language-specific by assigning a language and test
    // translating it.
    $default_langcode = $this->langcodes[0];
    $entity->{$langcode_key}->value = $default_langcode;
    $entity->{$this->fieldName} = [];
    $this->assertEqual($entity->language(), \Drupal::languageManager()->getLanguage($this->langcodes[0]), format_string('%entity_type: Entity language retrieved.', ['%entity_type' => $entity_type]));
    $this->assertFalse($entity->getTranslationLanguages(FALSE), format_string('%entity_type: No translations are available', ['%entity_type' => $entity_type]));

    // Set the value in default language.
    $entity->set($this->fieldName, [0 => ['value' => 'default value']]);
    // Get the value.
    $field = $entity->get($this->fieldName);
    $this->assertEqual($field->value, 'default value', format_string('%entity_type: Untranslated value retrieved.', ['%entity_type' => $entity_type]));
    $this->assertEqual($field->getLangcode(), $default_langcode, format_string('%entity_type: Field object has the expected langcode.', ['%entity_type' => $entity_type]));

    // Set a translation.
    $entity->addTranslation($this->langcodes[1])->set($this->fieldName, [0 => ['value' => 'translation 1']]);
    $field = $entity->getTranslation($this->langcodes[1])->{$this->fieldName};
    $this->assertEqual($field->value, 'translation 1', format_string('%entity_type: Translated value set.', ['%entity_type' => $entity_type]));
    $this->assertEqual($field->getLangcode(), $this->langcodes[1], format_string('%entity_type: Field object has the expected langcode.', ['%entity_type' => $entity_type]));

    // Make sure the untranslated value stays.
    $field = $entity->get($this->fieldName);
    $this->assertEqual($field->value, 'default value', 'Untranslated value stays.');
    $this->assertEqual($field->getLangcode(), $default_langcode, 'Untranslated value has the expected langcode.');

    $translations[$this->langcodes[1]] = \Drupal::languageManager()->getLanguage($this->langcodes[1]);
    $this->assertEqual($entity->getTranslationLanguages(FALSE), $translations, 'Translations retrieved.');

    // Try to get a value using a language code for a non-existing translation.
    $message = 'Getting a non existing translation results in an error.';
    try {
      $entity->getTranslation($this->langcodes[2])->get($this->fieldName)->value;
      $this->fail($message);
    }
    catch (\InvalidArgumentException $e) {
      $this->pass($message);
    }

    // Try to get a not available translation.
    $this->assertNull($entity->addTranslation($this->langcodes[2])->get($this->fieldName)->value, format_string('%entity_type: A translation that is not available is NULL.', ['%entity_type' => $entity_type]));

    // Try to get a value using an invalid language code.
    $message = 'Getting an invalid translation results in an error.';
    try {
      $entity->getTranslation('invalid')->get($this->fieldName)->value;
      $this->fail($message);
    }
    catch (\InvalidArgumentException $e) {
      $this->pass($message);
    }

    // Try to set a value using an invalid language code.
    try {
      $entity->getTranslation('invalid')->set($this->fieldName, NULL);
      $this->fail(format_string('%entity_type: Setting a translation for an invalid language throws an exception.', ['%entity_type' => $entity_type]));
    }
    catch (\InvalidArgumentException $e) {
      $this->pass(format_string('%entity_type: Setting a translation for an invalid language throws an exception.', ['%entity_type' => $entity_type]));
    }

    // Set the value in default language.
    $field_name = 'field_test_text';
    $entity->getTranslation($this->langcodes[1])->set($field_name, [0 => ['value' => 'default value2']]);
    // Get the value.
    $field = $entity->get($field_name);
    $this->assertEqual($field->value, 'default value2', format_string('%entity_type: Untranslated value set into a translation in non-strict mode.', ['%entity_type' => $entity_type]));
    $this->assertEqual($field->getLangcode(), $default_langcode, format_string('%entity_type: Field object has the expected langcode.', ['%entity_type' => $entity_type]));
  }

  /**
   * Tests multilingual properties.
   */
  public function testMultilingualProperties() {
    // Test all entity variations with data table support.
    foreach (entity_test_entity_types(ENTITY_TEST_TYPES_MULTILINGUAL) as $entity_type) {
      $this->doTestMultilingualProperties($entity_type);
    }
  }

  /**
   * Executes the multilingual property tests for the given entity type.
   *
   * @param string $entity_type
   *   The entity type to run the tests with.
   */
  protected function doTestMultilingualProperties($entity_type) {
    $langcode_key = $this->entityManager->getDefinition($entity_type)->getKey('langcode');
    $default_langcode_key = $this->entityManager->getDefinition($entity_type)->getKey('default_langcode');
    $name = $this->randomMachineName();
    $uid = mt_rand(0, 127);
    $langcode = $this->langcodes[0];

    // Create a language neutral entity and check that properties are stored
    // as language neutral.
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($entity_type);
    $entity = $storage->create(['name' => $name, 'user_id' => $uid, $langcode_key => LanguageInterface::LANGCODE_NOT_SPECIFIED]);
    $entity->save();
    $entity = $storage->load($entity->id());
    $default_langcode = $entity->language()->getId();
    $this->assertEqual($default_langcode, LanguageInterface::LANGCODE_NOT_SPECIFIED, format_string('%entity_type: Entity created as language neutral.', ['%entity_type' => $entity_type]));
    $field = $entity->getTranslation(LanguageInterface::LANGCODE_DEFAULT)->get('name');
    $this->assertEqual($name, $field->value, format_string('%entity_type: The entity name has been correctly stored as language neutral.', ['%entity_type' => $entity_type]));
    $this->assertEqual($default_langcode, $field->getLangcode(), format_string('%entity_type: The field object has the expect langcode.', ['%entity_type' => $entity_type]));
    $this->assertEqual($uid, $entity->getTranslation(LanguageInterface::LANGCODE_DEFAULT)->get('user_id')->target_id, format_string('%entity_type: The entity author has been correctly stored as language neutral.', ['%entity_type' => $entity_type]));

    $translation = $entity->getTranslation(LanguageInterface::LANGCODE_DEFAULT);
    $field = $translation->get('name');
    $this->assertEqual($name, $field->value, format_string('%entity_type: The entity name defaults to neutral language.', ['%entity_type' => $entity_type]));
    $this->assertEqual($default_langcode, $field->getLangcode(), format_string('%entity_type: The field object has the expect langcode.', ['%entity_type' => $entity_type]));
    $this->assertEqual($uid, $translation->get('user_id')->target_id, format_string('%entity_type: The entity author defaults to neutral language.', ['%entity_type' => $entity_type]));
    $field = $entity->get('name');
    $this->assertEqual($name, $field->value, format_string('%entity_type: The entity name can be retrieved without specifying a language.', ['%entity_type' => $entity_type]));
    $this->assertEqual($default_langcode, $field->getLangcode(), format_string('%entity_type: The field object has the expect langcode.', ['%entity_type' => $entity_type]));
    $this->assertEqual($uid, $entity->get('user_id')->target_id, format_string('%entity_type: The entity author can be retrieved without specifying a language.', ['%entity_type' => $entity_type]));

    // Create a language-aware entity and check that properties are stored
    // as language-aware.
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create(['name' => $name, 'user_id' => $uid, $langcode_key => $langcode]);
    $entity->save();
    $entity = $storage->load($entity->id());
    $default_langcode = $entity->language()->getId();
    $this->assertEqual($default_langcode, $langcode, format_string('%entity_type: Entity created as language specific.', ['%entity_type' => $entity_type]));
    $field = $entity->getTranslation($langcode)->get('name');
    $this->assertEqual($name, $field->value, format_string('%entity_type: The entity name has been correctly stored as a language-aware property.', ['%entity_type' => $entity_type]));
    $this->assertEqual($default_langcode, $field->getLangcode(), format_string('%entity_type: The field object has the expect langcode.', ['%entity_type' => $entity_type]));
    $this->assertEqual($uid, $entity->getTranslation($langcode)->get('user_id')->target_id, format_string('%entity_type: The entity author has been correctly stored as a language-aware property.', ['%entity_type' => $entity_type]));

    // Create property translations.
    $properties = [];
    $default_langcode = $langcode;
    foreach ($this->langcodes as $langcode) {
      if ($langcode != $default_langcode) {
        $properties[$langcode] = [
          'name' => [0 => $this->randomMachineName()],
          'user_id' => [0 => mt_rand(128, 256)],
        ];
      }
      else {
        $properties[$langcode] = [
          'name' => [0 => $name],
          'user_id' => [0 => $uid],
        ];
      }
      $translation = $entity->hasTranslation($langcode) ? $entity->getTranslation($langcode) : $entity->addTranslation($langcode);
      foreach ($properties[$langcode] as $field_name => $values) {
        $translation->set($field_name, $values);
      }
    }
    $entity->save();

    // Check that property translation were correctly stored.
    $entity = $storage->load($entity->id());
    foreach ($this->langcodes as $langcode) {
      $args = [
        '%entity_type' => $entity_type,
        '%langcode' => $langcode,
      ];
      $field = $entity->getTranslation($langcode)->get('name');
      $this->assertEqual($properties[$langcode]['name'][0], $field->value, format_string('%entity_type: The entity name has been correctly stored for language %langcode.', $args));
      $field_langcode = ($langcode == $entity->language()->getId()) ? $default_langcode : $langcode;
      $this->assertEqual($field_langcode, $field->getLangcode(), format_string('%entity_type: The field object has the expected langcode  %langcode.', $args));
      $this->assertEqual($properties[$langcode]['user_id'][0], $entity->getTranslation($langcode)->get('user_id')->target_id, format_string('%entity_type: The entity author has been correctly stored for language %langcode.', $args));
    }

    // Test query conditions (cache is reset at each call).
    $translated_id = $entity->id();
    // Create an additional entity with only the uid set. The uid for the
    // original language is the same of one used for a translation.
    $langcode = $this->langcodes[1];
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($entity_type);
    $storage->create([
        'user_id' => $properties[$langcode]['user_id'],
        'name' => 'some name',
        $langcode_key => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ])
      ->save();

    $entities = $storage->loadMultiple();
    $this->assertEqual(count($entities), 3, format_string('%entity_type: Three entities were created.', ['%entity_type' => $entity_type]));
    $entities = $storage->loadMultiple([$translated_id]);
    $this->assertEqual(count($entities), 1, format_string('%entity_type: One entity correctly loaded by id.', ['%entity_type' => $entity_type]));
    $entities = $storage->loadByProperties(['name' => $name]);
    $this->assertEqual(count($entities), 2, format_string('%entity_type: Two entities correctly loaded by name.', ['%entity_type' => $entity_type]));
    // @todo The default language condition should go away in favor of an
    // explicit parameter.
    $entities = $storage->loadByProperties(['name' => $properties[$langcode]['name'][0], $default_langcode_key => 0]);
    $this->assertEqual(count($entities), 1, format_string('%entity_type: One entity correctly loaded by name translation.', ['%entity_type' => $entity_type]));
    $entities = $storage->loadByProperties([$langcode_key => $default_langcode, 'name' => $name]);
    $this->assertEqual(count($entities), 1, format_string('%entity_type: One entity correctly loaded by name and language.', ['%entity_type' => $entity_type]));

    $entities = $storage->loadByProperties([$langcode_key => $langcode, 'name' => $properties[$langcode]['name'][0]]);
    $this->assertEqual(count($entities), 0, format_string('%entity_type: No entity loaded by name translation specifying the translation language.', ['%entity_type' => $entity_type]));
    $entities = $storage->loadByProperties([$langcode_key => $langcode, 'name' => $properties[$langcode]['name'][0], $default_langcode_key => 0]);
    $this->assertEqual(count($entities), 1, format_string('%entity_type: One entity loaded by name translation and language specifying to look for translations.', ['%entity_type' => $entity_type]));
    $entities = $storage->loadByProperties(['user_id' => $properties[$langcode]['user_id'][0], $default_langcode_key => NULL]);
    $this->assertEqual(count($entities), 2, format_string('%entity_type: Two entities loaded by uid without caring about property translatability.', ['%entity_type' => $entity_type]));

    // Test property conditions and orders with multiple languages in the same
    // query.
    $query = \Drupal::entityQuery($entity_type);
    $group = $query->andConditionGroup()
      ->condition('user_id', $properties[$default_langcode]['user_id'][0], '=', $default_langcode)
      ->condition('name', $properties[$default_langcode]['name'][0], '=', $default_langcode);
    $result = $query
      ->condition($group)
      ->condition('name', $properties[$langcode]['name'][0], '=', $langcode)
      ->execute();
    $this->assertEqual(count($result), 1, format_string('%entity_type: One entity loaded by name and uid using different language meta conditions.', ['%entity_type' => $entity_type]));

    // Test mixed property and field conditions.
    $storage->resetCache($result);
    $entity = $storage->load(reset($result));
    $field_value = $this->randomString();
    $entity->getTranslation($langcode)->set($this->fieldName, [['value' => $field_value]]);
    $entity->save();
    $query = \Drupal::entityQuery($entity_type);
    $default_langcode_group = $query->andConditionGroup()
      ->condition('user_id', $properties[$default_langcode]['user_id'][0], '=', $default_langcode)
      ->condition('name', $properties[$default_langcode]['name'][0], '=', $default_langcode);
    $langcode_group = $query->andConditionGroup()
      ->condition('name', $properties[$langcode]['name'][0], '=', $langcode)
      ->condition("$this->fieldName.value", $field_value, '=', $langcode);
    $result = $query
      ->condition($langcode_key, $default_langcode)
      ->condition($default_langcode_group)
      ->condition($langcode_group)
      ->execute();
    $this->assertEqual(count($result), 1, format_string('%entity_type: One entity loaded by name, uid and field value using different language meta conditions.', ['%entity_type' => $entity_type]));
  }

  /**
   * Tests the Entity Translation API behavior.
   */
  public function testEntityTranslationAPI() {
    // Test all entity variations with data table support.
    foreach (entity_test_entity_types(ENTITY_TEST_TYPES_MULTILINGUAL) as $entity_type) {
      $this->doTestEntityTranslationAPI($entity_type);
    }
  }

  /**
   * Executes the Entity Translation API tests for the given entity type.
   *
   * @param string $entity_type
   *   The entity type to run the tests with.
   */
  protected function doTestEntityTranslationAPI($entity_type) {
    $default_langcode = $this->langcodes[0];
    $langcode = $this->langcodes[1];
    $langcode_key = $this->entityManager->getDefinition($entity_type)->getKey('langcode');
    $default_langcode_key = $this->entityManager->getDefinition($entity_type)->getKey('default_langcode');

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->entityManager
      ->getStorage($entity_type)
      ->create(['name' => $this->randomMachineName(), $langcode_key => LanguageInterface::LANGCODE_NOT_SPECIFIED]);

    $entity->save();
    $hooks = $this->getHooksInfo();
    $this->assertFalse($hooks, 'No entity translation hooks are fired when creating an entity.');

    // Verify that we obtain the entity object itself when we attempt to
    // retrieve a translation referring to it.
    $translation = $entity->getTranslation(LanguageInterface::LANGCODE_NOT_SPECIFIED);
    $this->assertFalse($translation->isNewTranslation(), 'Existing translations are not marked as new.');
    $this->assertSame($entity, $translation, 'The translation object corresponding to a non-default language is the entity object itself when the entity is language-neutral.');
    $entity->{$langcode_key}->value = $default_langcode;
    $translation = $entity->getTranslation($default_langcode);
    $this->assertSame($entity, $translation, 'The translation object corresponding to the default language (explicit) is the entity object itself.');
    $translation = $entity->getTranslation(LanguageInterface::LANGCODE_DEFAULT);
    $this->assertSame($entity, $translation, 'The translation object corresponding to the default language (implicit) is the entity object itself.');
    $this->assertTrue($entity->{$default_langcode_key}->value, 'The translation object is the default one.');

    // Verify that trying to retrieve a translation for a locked language when
    // the entity is language-aware causes an exception to be thrown.
    $message = 'A language-neutral translation cannot be retrieved.';
    try {
      $entity->getTranslation(LanguageInterface::LANGCODE_NOT_SPECIFIED);
      $this->fail($message);
    }
    catch (\LogicException $e) {
      $this->pass($message);
    }

    // Create a translation and verify that the translation object and the
    // original object behave independently.
    $name = $default_langcode . '_' . $this->randomMachineName();
    $entity->name->value = $name;
    $name_translated = $langcode . '_' . $this->randomMachineName();
    $translation = $entity->addTranslation($langcode);
    $this->assertTrue($translation->isNewTranslation(), 'Newly added translations are marked as new.');
    $this->assertNotIdentical($entity, $translation, 'The entity and the translation object differ from one another.');
    $this->assertTrue($entity->hasTranslation($langcode), 'The new translation exists.');
    $this->assertEqual($translation->language()->getId(), $langcode, 'The translation language matches the specified one.');
    $this->assertEqual($translation->{$langcode_key}->value, $langcode, 'The translation field language value matches the specified one.');
    $this->assertFalse($translation->{$default_langcode_key}->value, 'The translation object is not the default one.');
    $this->assertEqual($translation->getUntranslated()->language()->getId(), $default_langcode, 'The original language can still be retrieved.');
    $translation->name->value = $name_translated;
    $this->assertEqual($entity->name->value, $name, 'The original name is retained after setting a translated value.');
    $entity->name->value = $name;
    $this->assertEqual($translation->name->value, $name_translated, 'The translated name is retained after setting the original value.');

    // Save the translation and check that the expected hooks are fired.
    $translation->save();
    $hooks = $this->getHooksInfo();

    $this->assertEqual($hooks['entity_translation_create'], $langcode, 'The generic entity translation creation hook has fired.');
    $this->assertEqual($hooks[$entity_type . '_translation_create'], $langcode, 'The entity-type-specific entity translation creation hook has fired.');

    $this->assertEqual($hooks['entity_translation_insert'], $langcode, 'The generic entity translation insertion hook has fired.');
    $this->assertEqual($hooks[$entity_type . '_translation_insert'], $langcode, 'The entity-type-specific entity translation insertion hook has fired.');

    // Verify that changing translation language causes an exception to be
    // thrown.
    $message = 'The translation language cannot be changed.';
    try {
      $translation->{$langcode_key}->value = $this->langcodes[2];
      $this->fail($message);
    }
    catch (\LogicException $e) {
      $this->pass($message);
    }

    // Verify that reassigning the same translation language is allowed.
    $message = 'The translation language can be reassigned the same value.';
    try {
      $translation->{$langcode_key}->value = $langcode;
      $this->pass($message);
    }
    catch (\LogicException $e) {
      $this->fail($message);
    }

    // Verify that changing the default translation flag causes an exception to
    // be thrown.
    foreach ($entity->getTranslationLanguages() as $t_langcode => $language) {
      $translation = $entity->getTranslation($t_langcode);
      $default = $translation->isDefaultTranslation();

      $message = 'The default translation flag can be reassigned the same value.';
      try {
        $translation->{$default_langcode_key}->value = $default;
        $this->pass($message);
      }
      catch (\LogicException $e) {
        $this->fail($message);
      }

      $message = 'The default translation flag cannot be changed.';
      try {
        $translation->{$default_langcode_key}->value = !$default;
        $this->fail($message);
      }
      catch (\LogicException $e) {
        $this->pass($message);
      }

      $this->assertEqual($translation->{$default_langcode_key}->value, $default);
    }

    // Check that after loading an entity the language is the default one.
    $entity = $this->reloadEntity($entity);
    $this->assertEqual($entity->language()->getId(), $default_langcode, 'The loaded entity is the original one.');

    // Add another translation and check that everything works as expected. A
    // new translation object can be obtained also by just specifying a valid
    // language.
    $langcode2 = $this->langcodes[2];
    $translation = $entity->addTranslation($langcode2);
    $value = $entity !== $translation && $translation->language()->getId() == $langcode2 && $entity->hasTranslation($langcode2);
    $this->assertTrue($value, 'A new translation object can be obtained also by specifying a valid language.');
    $this->assertEqual($entity->language()->getId(), $default_langcode, 'The original language has been preserved.');
    $translation->save();
    $hooks = $this->getHooksInfo();

    $this->assertEqual($hooks['entity_translation_create'], $langcode2, 'The generic entity translation creation hook has fired.');
    $this->assertEqual($hooks[$entity_type . '_translation_create'], $langcode2, 'The entity-type-specific entity translation creation hook has fired.');

    $this->assertEqual($hooks['entity_translation_insert'], $langcode2, 'The generic entity translation insertion hook has fired.');
    $this->assertEqual($hooks[$entity_type . '_translation_insert'], $langcode2, 'The entity-type-specific entity translation insertion hook has fired.');

    // Verify that trying to manipulate a translation object referring to a
    // removed translation results in exceptions being thrown.
    $entity = $this->reloadEntity($entity);
    $translation = $entity->getTranslation($langcode2);
    $entity->removeTranslation($langcode2);
    foreach (['get', 'set', '__get', '__set', 'createDuplicate'] as $method) {
      $message = format_string('The @method method raises an exception when trying to manipulate a removed translation.', ['@method' => $method]);
      try {
        $translation->{$method}('name', $this->randomMachineName());
        $this->fail($message);
      }
      catch (\Exception $e) {
        $this->pass($message);
      }
    }

    // Verify that deletion hooks are fired when saving an entity with a removed
    // translation.
    $entity->save();
    $hooks = $this->getHooksInfo();
    $this->assertEqual($hooks['entity_translation_delete'], $langcode2, 'The generic entity translation deletion hook has fired.');
    $this->assertEqual($hooks[$entity_type . '_translation_delete'], $langcode2, 'The entity-type-specific entity translation deletion hook has fired.');
    $entity = $this->reloadEntity($entity);
    $this->assertFalse($entity->hasTranslation($langcode2), 'The translation does not appear among available translations after saving the entity.');

    // Check that removing an invalid translation causes an exception to be
    // thrown.
    foreach ([$default_langcode, LanguageInterface::LANGCODE_DEFAULT, $this->randomMachineName()] as $invalid_langcode) {
      $message = format_string('Removing an invalid translation (@langcode) causes an exception to be thrown.', ['@langcode' => $invalid_langcode]);
      try {
        $entity->removeTranslation($invalid_langcode);
        $this->fail($message);
      }
      catch (\Exception $e) {
        $this->pass($message);
      }
    }

    // Check that hooks are fired only when actually storing data.
    $entity = $this->reloadEntity($entity);
    $entity->addTranslation($langcode2);
    $entity->removeTranslation($langcode2);
    $entity->save();
    $hooks = $this->getHooksInfo();

    $this->assertTrue(isset($hooks['entity_translation_create']), 'The generic entity translation creation hook is run when adding and removing a translation without storing it.');
    unset($hooks['entity_translation_create']);
    $this->assertTrue(isset($hooks[$entity_type . '_translation_create']), 'The entity-type-specific entity translation creation hook is run when adding and removing a translation without storing it.');
    unset($hooks[$entity_type . '_translation_create']);

    $this->assertFalse($hooks, 'No other hooks beyond the entity translation creation hooks are run when adding and removing a translation without storing it.');

    // Check that hooks are fired only when actually storing data.
    $entity = $this->reloadEntity($entity);
    $entity->addTranslation($langcode2);
    $entity->save();
    $entity = $this->reloadEntity($entity);
    $this->assertTrue($entity->hasTranslation($langcode2), 'Entity has translation after adding one and saving.');
    $entity->removeTranslation($langcode2);
    $entity->save();
    $entity = $this->reloadEntity($entity);
    $this->assertFalse($entity->hasTranslation($langcode2), 'Entity does not have translation after removing it and saving.');
    // Reset hook firing information.
    $this->getHooksInfo();

    // Verify that entity serialization does not cause stale references to be
    // left around.
    $entity = $this->reloadEntity($entity);
    $translation = $entity->getTranslation($langcode);
    $entity = unserialize(serialize($entity));
    $entity->name->value = $this->randomMachineName();
    $name = $default_langcode . '_' . $this->randomMachineName();
    $entity->getTranslation($default_langcode)->name->value = $name;
    $this->assertEqual($entity->name->value, $name, 'No stale reference for the translation object corresponding to the original language.');
    $translation2 = $entity->getTranslation($langcode);
    $translation2->name->value .= $this->randomMachineName();
    $this->assertNotEqual($translation->name->value, $translation2->name->value, 'No stale reference for the actual translation object.');
    $this->assertEqual($entity, $translation2->getUntranslated(), 'No stale reference in the actual translation object.');

    // Verify that deep-cloning is still available when we are not instantiating
    // a translation object, which instead relies on shallow cloning.
    $entity = $this->reloadEntity($entity);
    $entity->getTranslation($langcode);
    $cloned = clone $entity;
    $translation = $cloned->getTranslation($langcode);
    $this->assertNotIdentical($entity, $translation->getUntranslated(), 'A cloned entity object has no reference to the original one.');
    $entity->removeTranslation($langcode);
    $this->assertFalse($entity->hasTranslation($langcode));
    $this->assertTrue($cloned->hasTranslation($langcode));

    // Check that untranslatable field references keep working after serializing
    // and cloning the entity.
    $entity = $this->reloadEntity($entity);
    $type = $this->randomMachineName();
    $entity->getTranslation($langcode)->type->value = $type;
    $entity = unserialize(serialize($entity));
    $cloned = clone $entity;
    $translation = $cloned->getTranslation($langcode);
    $translation->type->value = strrev($type);
    $this->assertEqual($cloned->type->value, $translation->type->value, 'Untranslatable field references keep working after serializing and cloning the entity.');

    // Check that per-language defaults are properly populated. The
    // 'entity_test_mul_default_value' entity type is translatable and uses
    // entity_test_field_default_value() as a "default value callback" for its
    // 'description' field.
    $entity = $this->entityManager
      ->getStorage('entity_test_mul_default_value')
      ->create(['name' => $this->randomMachineName(), 'langcode' => $langcode]);
    $translation = $entity->addTranslation($langcode2);
    $expected = [
      [
        'shape' => "shape:0:description_$langcode2",
        'color' => "color:0:description_$langcode2",
      ],
      [
        'shape' => "shape:1:description_$langcode2",
        'color' => "color:1:description_$langcode2",
      ],
    ];
    $this->assertEqual($translation->description->getValue(), $expected, 'Language-aware default values correctly populated.');
    $this->assertEqual($translation->description->getLangcode(), $langcode2, 'Field object has the expected langcode.');

    // Reset hook firing information.
    $this->getHooksInfo();
  }

  /**
   * Tests language fallback applied to field and entity translations.
   */
  public function testLanguageFallback() {
    // Test all entity variations with data table support.
    foreach (entity_test_entity_types(ENTITY_TEST_TYPES_MULTILINGUAL) as $entity_type) {
      $this->doTestLanguageFallback($entity_type);
    }
  }

  /**
   * Executes the language fallback test for the given entity type.
   *
   * @param string $entity_type
   *   The entity type to run the tests with.
   */
  protected function doTestLanguageFallback($entity_type) {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    $current_langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    $this->langcodes[] = $current_langcode;

    $values = [];
    foreach ($this->langcodes as $langcode) {
      $values[$langcode]['name'] = $this->randomMachineName();
      $values[$langcode]['user_id'] = mt_rand(0, 127);
    }

    $default_langcode = $this->langcodes[0];
    $langcode = $this->langcodes[1];
    $langcode2 = $this->langcodes[2];
    $langcode_key = $this->entityManager->getDefinition($entity_type)->getKey('langcode');
    $languages = $this->languageManager->getLanguages();
    $language = ConfigurableLanguage::load($languages[$langcode]->getId());
    $language->set('weight', 1);
    $language->save();
    $this->languageManager->reset();

    $controller = $this->entityManager->getStorage($entity_type);
    $entity = $controller->create([$langcode_key => $default_langcode] + $values[$default_langcode]);
    $entity->save();

    $entity->addTranslation($langcode, $values[$langcode]);
    $entity->save();

    // Check that retrieving the current translation works as expected.
    $entity = $this->reloadEntity($entity);
    $translation = \Drupal::service('entity.repository')->getTranslationFromContext($entity, $langcode2);
    $this->assertEqual($translation->language()->getId(), $default_langcode, 'The current translation language matches the expected one.');

    // Check that language fallback respects language weight by default.
    $language = ConfigurableLanguage::load($languages[$langcode]->getId());
    $language->set('weight', -1);
    $language->save();
    $translation = \Drupal::service('entity.repository')->getTranslationFromContext($entity, $langcode2);
    $this->assertEqual($translation->language()->getId(), $langcode, 'The current translation language matches the expected one.');

    // Check that the current translation is properly returned.
    $translation = \Drupal::service('entity.repository')->getTranslationFromContext($entity);
    $this->assertEqual($langcode, $translation->language()->getId(), 'The current translation language matches the topmost language fallback candidate.');
    $entity->addTranslation($current_langcode, $values[$current_langcode]);
    $translation = \Drupal::service('entity.repository')->getTranslationFromContext($entity);
    $this->assertEqual($current_langcode, $translation->language()->getId(), 'The current translation language matches the current language.');

    // Check that if the entity has no translation no fallback is applied.
    $entity2 = $controller->create([$langcode_key => $default_langcode]);
    // Get an view builder.
    $controller = $this->entityManager->getViewBuilder($entity_type);
    $entity2_build = $controller->view($entity2);
    $entity2_output = (string) $renderer->renderRoot($entity2_build);
    $translation = \Drupal::service('entity.repository')->getTranslationFromContext($entity2, $default_langcode);
    $translation_build = $controller->view($translation);
    $translation_output = (string) $renderer->renderRoot($translation_build);
    $this->assertSame($entity2_output, $translation_output, 'When the entity has no translation no fallback is applied.');

    // Checks that entity translations are rendered properly.
    $controller = $this->entityManager->getViewBuilder($entity_type);
    $build = $controller->view($entity);
    $renderer->renderRoot($build);
    $this->assertEqual($build['label']['#markup'], $values[$current_langcode]['name'], 'By default the entity is rendered in the current language.');

    $langcodes = array_combine($this->langcodes, $this->langcodes);
    // We have no translation for the $langcode2 language, hence the expected
    // result is the topmost existing translation, that is $langcode.
    $langcodes[$langcode2] = $langcode;
    foreach ($langcodes as $desired => $expected) {
      $build = $controller->view($entity, 'full', $desired);
      // Unset the #cache key so that a fresh render is produced with each pass,
      // making the renderable array keys available to compare.
      unset($build['#cache']);
      $renderer->renderRoot($build);
      $this->assertEqual($build['label']['#markup'], $values[$expected]['name'], 'The entity is rendered in the expected language.');
    }
  }

  /**
   * Check that field translatability is handled properly.
   */
  public function testFieldDefinitions() {
    // Check that field translatability can be altered to be enabled or disabled
    // in field definitions.
    $entity_type = 'entity_test_mulrev';
    $this->state->set('entity_test.field_definitions.translatable', ['name' => FALSE]);
    $this->entityManager->clearCachedFieldDefinitions();
    $definitions = $this->entityManager->getBaseFieldDefinitions($entity_type);
    $this->assertFalse($definitions['name']->isTranslatable(), 'Field translatability can be disabled programmatically.');

    $this->state->set('entity_test.field_definitions.translatable', ['name' => TRUE]);
    $this->entityManager->clearCachedFieldDefinitions();
    $definitions = $this->entityManager->getBaseFieldDefinitions($entity_type);
    $this->assertTrue($definitions['name']->isTranslatable(), 'Field translatability can be enabled programmatically.');

    // Check that field translatability is disabled by default.
    $base_field_definitions = EntityTestMulRev::baseFieldDefinitions($this->entityManager->getDefinition($entity_type));
    $this->assertTrue(!isset($base_field_definitions['id']->translatable), 'Translatability for the <em>id</em> field is not defined.');
    $this->assertFalse($definitions['id']->isTranslatable(), 'Field translatability is disabled by default.');

    // Check that entity id keys have the expect translatability.
    $translatable_fields = [
      'id' => TRUE,
      'uuid' => TRUE,
      'revision_id' => TRUE,
      'type' => TRUE,
      'langcode' => FALSE,
    ];
    foreach ($translatable_fields as $name => $translatable) {
      $this->state->set('entity_test.field_definitions.translatable', [$name => $translatable]);
      $this->entityManager->clearCachedFieldDefinitions();
      $message = format_string('Field %field cannot be translatable.', ['%field' => $name]);

      try {
        $this->entityManager->getBaseFieldDefinitions($entity_type);
        $this->fail($message);
      }
      catch (\LogicException $e) {
        $this->pass($message);
      }
    }
  }

  /**
   * Tests that changing entity language does not break field language.
   */
  public function testLanguageChange() {
    // Test all entity variations with data table support.
    foreach (entity_test_entity_types(ENTITY_TEST_TYPES_MULTILINGUAL) as $entity_type) {
      $this->doTestLanguageChange($entity_type);
    }
  }

  /**
   * Executes the entity language change test for the given entity type.
   *
   * @param string $entity_type
   *   The entity type to run the tests with.
   */
  protected function doTestLanguageChange($entity_type) {
    $langcode_key = $this->entityManager->getDefinition($entity_type)->getKey('langcode');
    $controller = $this->entityManager->getStorage($entity_type);
    $langcode = $this->langcodes[0];

    // check that field languages match entity language regardless of field
    // translatability.
    $values = [
      $langcode_key => $langcode,
      $this->fieldName => $this->randomMachineName(),
      $this->untranslatableFieldName => $this->randomMachineName(),
    ];
    $entity = $controller->create($values);
    foreach ([$this->fieldName, $this->untranslatableFieldName] as $field_name) {
      $this->assertEqual($entity->get($field_name)->getLangcode(), $langcode, 'Field language works as expected.');
    }

    // Check that field languages keep matching entity language even after
    // changing it.
    $langcode = $this->langcodes[1];
    $entity->{$langcode_key}->value = $langcode;
    foreach ([$this->fieldName, $this->untranslatableFieldName] as $field_name) {
      $this->assertEqual($entity->get($field_name)->getLangcode(), $langcode, 'Field language works as expected after changing entity language.');
    }

    // Check that entity translation does not affect the language of original
    // field values and untranslatable ones.
    $langcode = $this->langcodes[0];
    $entity->addTranslation($this->langcodes[2], [$this->fieldName => $this->randomMachineName()]);
    $entity->{$langcode_key}->value = $langcode;
    foreach ([$this->fieldName, $this->untranslatableFieldName] as $field_name) {
      $this->assertEqual($entity->get($field_name)->getLangcode(), $langcode, 'Field language works as expected after translating the entity and changing language.');
    }

    // Check that setting the default language to an existing translation
    // language causes an exception to be thrown.
    $message = 'An exception is thrown when setting the default language to an existing translation language';
    try {
      $entity->{$langcode_key}->value = $this->langcodes[2];
      $this->fail($message);
    }
    catch (\InvalidArgumentException $e) {
      $this->pass($message);
    }
  }

  /**
   * Tests how entity adapters work with translations.
   */
  public function testEntityAdapter() {
    $entity_type = 'entity_test';
    $default_langcode = 'en';
    $values[$default_langcode] = ['name' => $this->randomString()];
    $controller = $this->entityManager->getStorage($entity_type);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $controller->create($values[$default_langcode]);

    foreach ($this->langcodes as $langcode) {
      $values[$langcode] = ['name' => $this->randomString()];
      $entity->addTranslation($langcode, $values[$langcode]);
    }

    $langcodes = array_merge([$default_langcode], $this->langcodes);
    foreach ($langcodes as $langcode) {
      $adapter = $entity->getTranslation($langcode)->getTypedData();
      $name = $adapter->get('name')->value;
      $this->assertEqual($name, $values[$langcode]['name'], new FormattableMarkup('Name correctly retrieved from "@langcode" adapter', ['@langcode' => $langcode]));
    }
  }

  /**
   * Tests if entity references are correct after adding a new translation.
   */
  public function testFieldEntityReference() {
    $entity_type = 'entity_test_mul';
    $controller = $this->entityManager->getStorage($entity_type);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $controller->create();

    foreach ($this->langcodes as $langcode) {
      $entity->addTranslation($langcode);
    }

    $default_langcode = $entity->getUntranslated()->language()->getId();
    foreach (array_keys($entity->getTranslationLanguages()) as $langcode) {
      $translation = $entity->getTranslation($langcode);
      foreach ($translation->getFields() as $field_name => $field) {
        if ($field->getFieldDefinition()->isTranslatable()) {
          $args = ['%field_name' => $field_name, '%langcode' => $langcode];
          $this->assertEqual($langcode, $field->getEntity()->language()->getId(), format_string('Translatable field %field_name on translation %langcode has correct entity reference in translation %langcode.', $args));
        }
        else {
          $args = ['%field_name' => $field_name, '%langcode' => $langcode, '%default_langcode' => $default_langcode];
          $this->assertEqual($default_langcode, $field->getEntity()->language()->getId(), format_string('Non translatable field %field_name on translation %langcode has correct entity reference in the default translation %default_langcode.', $args));
        }
      }
    }
  }

  /**
   * Tests if entity translation statuses are correct after removing two
   * translation.
   */
  public function testDeleteEntityTranslation() {
    $entity_type = 'entity_test_mul';
    $controller = $this->entityManager->getStorage($entity_type);

    // Create a translatable test field.
    $field_storage = FieldStorageConfig::create([
      'entity_type' => $entity_type,
      'field_name' => 'translatable_test_field',
      'type' => 'field_test',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'label' => $this->randomMachineName(),
      'bundle' => $entity_type,
    ]);
    $field->save();

    // Create an untranslatable test field.
    $field_storage = FieldStorageConfig::create([
      'entity_type' => $entity_type,
      'field_name' => 'untranslatable_test_field',
      'type' => 'field_test',
      'translatable' => FALSE,
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'label' => $this->randomMachineName(),
      'bundle' => $entity_type,
    ]);
    $field->save();

    // Create an entity with both translatable and untranslatable test fields.
    $values = [
      'name' => $this->randomString(),
      'translatable_test_field' => $this->randomString(),
      'untranslatable_test_field' => $this->randomString(),
    ];

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $controller->create($values);

    foreach ($this->langcodes as $langcode) {
      $entity->addTranslation($langcode, $values);
    }
    $entity->save();

    // Assert there are no deleted languages in the lists yet.
    $this->assertNull(\Drupal::state()->get('entity_test.delete.translatable_test_field'));
    $this->assertNull(\Drupal::state()->get('entity_test.delete.untranslatable_test_field'));

    // Remove the second and third langcodes from the entity.
    $entity->removeTranslation('l1');
    $entity->removeTranslation('l2');
    $entity->save();

    // Ensure that for the translatable test field the second and third
    // langcodes are in the deleted languages list.
    $actual = \Drupal::state()->get('entity_test.delete.translatable_test_field');
    $expected_translatable = ['l1', 'l2'];
    sort($actual);
    sort($expected_translatable);
    $this->assertEqual($actual, $expected_translatable);
    // Ensure that the untranslatable test field is untouched.
    $this->assertNull(\Drupal::state()->get('entity_test.delete.untranslatable_test_field'));

    // Delete the entity, which removes all remaining translations.
    $entity->delete();

    // All languages have been deleted now.
    $actual = \Drupal::state()->get('entity_test.delete.translatable_test_field');
    $expected_translatable[] = 'en';
    $expected_translatable[] = 'l0';
    sort($actual);
    sort($expected_translatable);
    $this->assertEqual($actual, $expected_translatable);

    // The untranslatable field is shared and only deleted once, for the
    // default langcode.
    $actual = \Drupal::state()->get('entity_test.delete.untranslatable_test_field');
    $expected_untranslatable = ['en'];
    sort($actual);
    sort($expected_untranslatable);
    $this->assertEqual($actual, $expected_untranslatable);
  }

  /**
   * Tests the getTranslationStatus method.
   */
  public function testTranslationStatus() {
    $entity_type = 'entity_test_mul';
    $storage = $this->entityManager->getStorage($entity_type);

    // Create an entity with both translatable and untranslatable test fields.
    $values = [
      'name' => $this->randomString(),
      'translatable_test_field' => $this->randomString(),
      'untranslatable_test_field' => $this->randomString(),
    ];

    /** @var \Drupal\Core\Entity\ContentEntityInterface|\Drupal\Core\TypedData\TranslationStatusInterface $entity */
    // Test that newly created entity has the translation status
    // TRANSLATION_CREATED.
    $entity = $storage->create($values);
    $this->assertEquals(TranslationStatusInterface::TRANSLATION_CREATED, $entity->getTranslationStatus($entity->language()->getId()));

    // Test that after saving a newly created entity it has the translation
    // status TRANSLATION_EXISTING.
    $entity->save();
    $this->assertEquals(TranslationStatusInterface::TRANSLATION_EXISTING, $entity->getTranslationStatus($entity->language()->getId()));

    // Test that after loading an existing entity it has the translation status
    // TRANSLATION_EXISTING.
    $storage->resetCache();
    $entity = $storage->load($entity->id());
    $this->assertEquals(TranslationStatusInterface::TRANSLATION_EXISTING, $entity->getTranslationStatus($entity->language()->getId()));

    foreach ($this->langcodes as $key => $langcode) {
      // Test that after adding a new translation it has the translation status
      // TRANSLATION_CREATED.
      $entity->addTranslation($langcode, $values);
      $this->assertEquals(TranslationStatusInterface::TRANSLATION_CREATED, $entity->getTranslationStatus($langcode));

      // Test that after removing a newly added and not yet saved translation
      // it does not have any translation status for the removed translation.
      $entity->removeTranslation($langcode);
      $this->assertEquals(NULL, $entity->getTranslationStatus($langcode));

      // Test that after adding a new translation and saving the entity it has
      // the translation status TRANSLATION_EXISTING.
      $entity->addTranslation($langcode, $values)
        ->save();
      $this->assertEquals(TranslationStatusInterface::TRANSLATION_EXISTING, $entity->getTranslationStatus($langcode));

      // Test that after removing an existing translation its translation
      // status has changed to TRANSLATION_REMOVED.
      $entity->removeTranslation($langcode);
      $this->assertEquals(TranslationStatusInterface::TRANSLATION_REMOVED, $entity->getTranslationStatus($langcode));

      // Test that after removing an existing translation and adding it again
      // its translation status has changed back to TRANSLATION_EXISTING.
      $entity->addTranslation($langcode, $values);
      $this->assertEquals(TranslationStatusInterface::TRANSLATION_EXISTING, $entity->getTranslationStatus($langcode));

      // Test that after removing an existing translation and saving the entity
      // it does not have any translation status for the removed translation.
      $entity->removeTranslation($langcode);
      $entity->save();
      $this->assertEquals(NULL, $entity->getTranslationStatus($langcode));

      // Tests that after removing an existing translation, saving the entity,
      // adding the translation again, the translation status of this
      // translation is TRANSLATION_CREATED.
      $entity->addTranslation($langcode, $values);
      $this->assertEquals(TranslationStatusInterface::TRANSLATION_CREATED, $entity->getTranslationStatus($langcode));
      $entity->save();
    }

    // Test that after loading an existing entity it has the translation status
    // TRANSLATION_EXISTING for all of its translations.
    $storage->resetCache();
    $entity = $storage->load($entity->id());
    foreach (array_keys($entity->getTranslationLanguages()) as $langcode) {
      $this->assertEquals(TranslationStatusInterface::TRANSLATION_EXISTING, $entity->getTranslationStatus($langcode));
    }
  }

  /**
   * Tests the translation object cache.
   */
  public function testTranslationObjectCache() {
    $default_langcode = $this->langcodes[1];
    $translation_langcode = $this->langcodes[2];

    $entity = EntityTestMul::create([
      'name' => 'test',
      'langcode' => $default_langcode,
    ]);
    $entity->save();
    $entity->addTranslation($translation_langcode)->save();

    // Test that the default translation object is put into the translation
    // object cache when a new translation object is initialized.
    $entity = \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId())->loadUnchanged($entity->id());
    $default_translation_spl_object_hash = spl_object_hash($entity);
    $this->assertEquals($default_translation_spl_object_hash, spl_object_hash($entity->getTranslation($translation_langcode)->getTranslation($default_langcode)));

    // Test that non-default translations are always served from the translation
    // object cache.
    $entity = \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId())->loadUnchanged($entity->id());
    $this->assertEquals(spl_object_hash($entity->getTranslation($translation_langcode)), spl_object_hash($entity->getTranslation($translation_langcode)));
    $this->assertEquals(spl_object_hash($entity->getTranslation($translation_langcode)), spl_object_hash($entity->getTranslation($translation_langcode)->getTranslation($default_langcode)->getTranslation($translation_langcode)));
  }

}
