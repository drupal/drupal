<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Entity\EntityTranslationTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\entity_test\Entity\EntityTestMulRev;
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
    $entity = entity_create($entity_type, array(
      'name' => 'test',
      'user_id' => $this->container->get('current_user')->id(),
    ));
    $this->assertEqual($entity->language()->getId(), $this->languageManager->getDefaultLanguage()->getId(), format_string('%entity_type: Entity created with API has default language.', array('%entity_type' => $entity_type)));
    $entity = entity_create($entity_type, array(
      'name' => 'test',
      'user_id' => \Drupal::currentUser()->id(),
      $langcode_key => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $this->assertEqual($entity->language()->getId(), LanguageInterface::LANGCODE_NOT_SPECIFIED, format_string('%entity_type: Entity language not specified.', array('%entity_type' => $entity_type)));
    $this->assertFalse($entity->getTranslationLanguages(FALSE), format_string('%entity_type: No translations are available', array('%entity_type' => $entity_type)));

    // Set the value in default language.
    $entity->set($this->fieldName, array(0 => array('value' => 'default value')));
    // Get the value.
    $field = $entity->getTranslation(LanguageInterface::LANGCODE_DEFAULT)->get($this->fieldName);
    $this->assertEqual($field->value, 'default value', format_string('%entity_type: Untranslated value retrieved.', array('%entity_type' => $entity_type)));
    $this->assertEqual($field->getLangcode(), LanguageInterface::LANGCODE_NOT_SPECIFIED, format_string('%entity_type: Field object has the expected langcode.', array('%entity_type' => $entity_type)));

    // Set the value in a certain language. As the entity is not
    // language-specific it should use the default language and so ignore the
    // specified language.
    $entity->getTranslation($this->langcodes[1])->set($this->fieldName, array(0 => array('value' => 'default value2')));
    $this->assertEqual($entity->get($this->fieldName)->value, 'default value2', format_string('%entity_type: Untranslated value updated.', array('%entity_type' => $entity_type)));
    $this->assertFalse($entity->getTranslationLanguages(FALSE), format_string('%entity_type: No translations are available', array('%entity_type' => $entity_type)));

    // Test getting a field value using a specific language for a not
    // language-specific entity.
    $field = $entity->getTranslation($this->langcodes[1])->get($this->fieldName);
    $this->assertEqual($field->value, 'default value2', format_string('%entity_type: Untranslated value retrieved.', array('%entity_type' => $entity_type)));
    $this->assertEqual($field->getLangcode(), LanguageInterface::LANGCODE_NOT_SPECIFIED, format_string('%entity_type: Field object has the expected langcode.', array('%entity_type' => $entity_type)));

    // Now, make the entity language-specific by assigning a language and test
    // translating it.
    $default_langcode = $this->langcodes[0];
    $entity->{$langcode_key}->value = $default_langcode;
    $entity->{$this->fieldName} = array();
    $this->assertEqual($entity->language(), \Drupal::languageManager()->getLanguage($this->langcodes[0]), format_string('%entity_type: Entity language retrieved.', array('%entity_type' => $entity_type)));
    $this->assertFalse($entity->getTranslationLanguages(FALSE), format_string('%entity_type: No translations are available', array('%entity_type' => $entity_type)));

    // Set the value in default language.
    $entity->set($this->fieldName, array(0 => array('value' => 'default value')));
    // Get the value.
    $field = $entity->get($this->fieldName);
    $this->assertEqual($field->value, 'default value', format_string('%entity_type: Untranslated value retrieved.', array('%entity_type' => $entity_type)));
    $this->assertEqual($field->getLangcode(), $default_langcode, format_string('%entity_type: Field object has the expected langcode.', array('%entity_type' => $entity_type)));

    // Set a translation.
    $entity->getTranslation($this->langcodes[1])->set($this->fieldName, array(0 => array('value' => 'translation 1')));
    $field = $entity->getTranslation($this->langcodes[1])->{$this->fieldName};
    $this->assertEqual($field->value, 'translation 1', format_string('%entity_type: Translated value set.', array('%entity_type' => $entity_type)));
    $this->assertEqual($field->getLangcode(), $this->langcodes[1], format_string('%entity_type: Field object has the expected langcode.', array('%entity_type' => $entity_type)));

    // Make sure the untranslated value stays.
    $field = $entity->get($this->fieldName);
    $this->assertEqual($field->value, 'default value', 'Untranslated value stays.');
    $this->assertEqual($field->getLangcode(), $default_langcode, 'Untranslated value has the expected langcode.');

    $translations[$this->langcodes[1]] = \Drupal::languageManager()->getLanguage($this->langcodes[1]);
    $this->assertEqual($entity->getTranslationLanguages(FALSE), $translations, 'Translations retrieved.');

    // Try to get a not available translation.
    $this->assertNull($entity->getTranslation($this->langcodes[2])->get($this->fieldName)->value, format_string('%entity_type: A translation that is not available is NULL.', array('%entity_type' => $entity_type)));

    // Try to get a value using an invalid language code.
    try {
      $entity->getTranslation('invalid')->get($this->fieldName)->value;
      $this->fail('Getting a translation for an invalid language is NULL.');
    }
    catch (\InvalidArgumentException $e) {
      $this->pass('A translation for an invalid language is NULL.');
    }

    // Try to set a value using an invalid language code.
    try {
      $entity->getTranslation('invalid')->set($this->fieldName, NULL);
      $this->fail(format_string('%entity_type: Setting a translation for an invalid language throws an exception.', array('%entity_type' => $entity_type)));
    }
    catch (\InvalidArgumentException $e) {
      $this->pass(format_string('%entity_type: Setting a translation for an invalid language throws an exception.', array('%entity_type' => $entity_type)));
    }

    // Set the value in default language.
    $field_name = 'field_test_text';
    $entity->getTranslation($this->langcodes[1])->set($field_name, array(0 => array('value' => 'default value2')));
    // Get the value.
    $field = $entity->get($field_name);
    $this->assertEqual($field->value, 'default value2', format_string('%entity_type: Untranslated value set into a translation in non-strict mode.', array('%entity_type' => $entity_type)));
    $this->assertEqual($field->getLangcode(), $default_langcode, format_string('%entity_type: Field object has the expected langcode.', array('%entity_type' => $entity_type)));
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
    $entity = entity_create($entity_type, array('name' => $name, 'user_id' => $uid, $langcode_key => LanguageInterface::LANGCODE_NOT_SPECIFIED));
    $entity->save();
    $entity = entity_load($entity_type, $entity->id());
    $default_langcode = $entity->language()->getId();
    $this->assertEqual($default_langcode, LanguageInterface::LANGCODE_NOT_SPECIFIED, format_string('%entity_type: Entity created as language neutral.', array('%entity_type' => $entity_type)));
    $field = $entity->getTranslation(LanguageInterface::LANGCODE_DEFAULT)->get('name');
    $this->assertEqual($name, $field->value, format_string('%entity_type: The entity name has been correctly stored as language neutral.', array('%entity_type' => $entity_type)));
    $this->assertEqual($default_langcode, $field->getLangcode(), format_string('%entity_type: The field object has the expect langcode.', array('%entity_type' => $entity_type)));
    $this->assertEqual($uid, $entity->getTranslation(LanguageInterface::LANGCODE_DEFAULT)->get('user_id')->target_id, format_string('%entity_type: The entity author has been correctly stored as language neutral.', array('%entity_type' => $entity_type)));
    $field = $entity->getTranslation($langcode)->get('name');
    $this->assertEqual($name, $field->value, format_string('%entity_type: The entity name defaults to neutral language.', array('%entity_type' => $entity_type)));
    $this->assertEqual($default_langcode, $field->getLangcode(), format_string('%entity_type: The field object has the expect langcode.', array('%entity_type' => $entity_type)));
    $this->assertEqual($uid, $entity->getTranslation($langcode)->get('user_id')->target_id, format_string('%entity_type: The entity author defaults to neutral language.', array('%entity_type' => $entity_type)));
    $field = $entity->get('name');
    $this->assertEqual($name, $field->value, format_string('%entity_type: The entity name can be retrieved without specifying a language.', array('%entity_type' => $entity_type)));
    $this->assertEqual($default_langcode, $field->getLangcode(), format_string('%entity_type: The field object has the expect langcode.', array('%entity_type' => $entity_type)));
    $this->assertEqual($uid, $entity->get('user_id')->target_id, format_string('%entity_type: The entity author can be retrieved without specifying a language.', array('%entity_type' => $entity_type)));

    // Create a language-aware entity and check that properties are stored
    // as language-aware.
    $entity = entity_create($entity_type, array('name' => $name, 'user_id' => $uid, $langcode_key => $langcode));
    $entity->save();
    $entity = entity_load($entity_type, $entity->id());
    $default_langcode = $entity->language()->getId();
    $this->assertEqual($default_langcode, $langcode, format_string('%entity_type: Entity created as language specific.', array('%entity_type' => $entity_type)));
    $field = $entity->getTranslation($langcode)->get('name');
    $this->assertEqual($name, $field->value, format_string('%entity_type: The entity name has been correctly stored as a language-aware property.', array('%entity_type' => $entity_type)));
    $this->assertEqual($default_langcode, $field->getLangcode(), format_string('%entity_type: The field object has the expect langcode.', array('%entity_type' => $entity_type)));
    $this->assertEqual($uid, $entity->getTranslation($langcode)->get('user_id')->target_id, format_string('%entity_type: The entity author has been correctly stored as a language-aware property.', array('%entity_type' => $entity_type)));
    // Translatable properties on a translatable entity should use default
    // language if LanguageInterface::LANGCODE_NOT_SPECIFIED is passed.
    $field = $entity->getTranslation(LanguageInterface::LANGCODE_NOT_SPECIFIED)->get('name');
    $this->assertEqual($name, $field->value, format_string('%entity_type: The entity name defaults to the default language.', array('%entity_type' => $entity_type)));
    $this->assertEqual($default_langcode, $field->getLangcode(), format_string('%entity_type: The field object has the expect langcode.', array('%entity_type' => $entity_type)));
    $this->assertEqual($uid, $entity->getTranslation(LanguageInterface::LANGCODE_NOT_SPECIFIED)->get('user_id')->target_id, format_string('%entity_type: The entity author defaults to the default language.', array('%entity_type' => $entity_type)));
    $field = $entity->get('name');
    $this->assertEqual($name, $field->value, format_string('%entity_type: The entity name can be retrieved without specifying a language.', array('%entity_type' => $entity_type)));
    $this->assertEqual($default_langcode, $field->getLangcode(), format_string('%entity_type: The field object has the expect langcode.', array('%entity_type' => $entity_type)));
    $this->assertEqual($uid, $entity->get('user_id')->target_id, format_string('%entity_type: The entity author can be retrieved without specifying a language.', array('%entity_type' => $entity_type)));

    // Create property translations.
    $properties = array();
    $default_langcode = $langcode;
    foreach ($this->langcodes as $langcode) {
      if ($langcode != $default_langcode) {
        $properties[$langcode] = array(
          'name' => array(0 => $this->randomMachineName()),
          'user_id' => array(0 => mt_rand(128, 256)),
        );
      }
      else {
        $properties[$langcode] = array(
          'name' => array(0 => $name),
          'user_id' => array(0 => $uid),
        );
      }
      $translation = $entity->getTranslation($langcode);
      foreach ($properties[$langcode] as $field_name => $values) {
        $translation->set($field_name, $values);
      }
    }
    $entity->save();

    // Check that property translation were correctly stored.
    $entity = entity_load($entity_type, $entity->id());
    foreach ($this->langcodes as $langcode) {
      $args = array(
        '%entity_type' => $entity_type,
        '%langcode' => $langcode,
      );
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
    entity_create($entity_type, array(
      'user_id' => $properties[$langcode]['user_id'],
      'name' => 'some name',
      $langcode_key => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ))->save();

    $entities = entity_load_multiple($entity_type);
    $this->assertEqual(count($entities), 3, format_string('%entity_type: Three entities were created.', array('%entity_type' => $entity_type)));
    $entities = entity_load_multiple($entity_type, array($translated_id));
    $this->assertEqual(count($entities), 1, format_string('%entity_type: One entity correctly loaded by id.', array('%entity_type' => $entity_type)));
    $entities = entity_load_multiple_by_properties($entity_type, array('name' => $name));
    $this->assertEqual(count($entities), 2, format_string('%entity_type: Two entities correctly loaded by name.', array('%entity_type' => $entity_type)));
    // @todo The default language condition should go away in favor of an
    // explicit parameter.
    $entities = entity_load_multiple_by_properties($entity_type, array('name' => $properties[$langcode]['name'][0], $default_langcode_key => 0));
    $this->assertEqual(count($entities), 1, format_string('%entity_type: One entity correctly loaded by name translation.', array('%entity_type' => $entity_type)));
    $entities = entity_load_multiple_by_properties($entity_type, array($langcode_key => $default_langcode, 'name' => $name));
    $this->assertEqual(count($entities), 1, format_string('%entity_type: One entity correctly loaded by name and language.', array('%entity_type' => $entity_type)));

    $entities = entity_load_multiple_by_properties($entity_type, array($langcode_key => $langcode, 'name' => $properties[$langcode]['name'][0]));
    $this->assertEqual(count($entities), 0, format_string('%entity_type: No entity loaded by name translation specifying the translation language.', array('%entity_type' => $entity_type)));
    $entities = entity_load_multiple_by_properties($entity_type, array($langcode_key => $langcode, 'name' => $properties[$langcode]['name'][0], $default_langcode_key => 0));
    $this->assertEqual(count($entities), 1, format_string('%entity_type: One entity loaded by name translation and language specifying to look for translations.', array('%entity_type' => $entity_type)));
    $entities = entity_load_multiple_by_properties($entity_type, array('user_id' => $properties[$langcode]['user_id'][0], $default_langcode_key => NULL));
    $this->assertEqual(count($entities), 2, format_string('%entity_type: Two entities loaded by uid without caring about property translatability.', array('%entity_type' => $entity_type)));

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
    $this->assertEqual(count($result), 1, format_string('%entity_type: One entity loaded by name and uid using different language meta conditions.', array('%entity_type' => $entity_type)));

    // Test mixed property and field conditions.
    $entity = entity_load($entity_type, reset($result), TRUE);
    $field_value = $this->randomString();
    $entity->getTranslation($langcode)->set($this->fieldName, array(array('value' => $field_value)));
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
    $this->assertEqual(count($result), 1, format_string('%entity_type: One entity loaded by name, uid and field value using different language meta conditions.', array('%entity_type' => $entity_type)));
  }

  /**
   * Tests the Entity Translation API behavior.
   */
  function testEntityTranslationAPI() {
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
      ->create(array('name' => $this->randomMachineName(), $langcode_key => LanguageInterface::LANGCODE_NOT_SPECIFIED));

    $entity->save();
    $hooks = $this->getHooksInfo();
    $this->assertFalse($hooks, 'No entity translation hooks are fired when creating an entity.');

    // Verify that we obtain the entity object itself when we attempt to
    // retrieve a translation referring to it.
    $translation = $entity->getTranslation($langcode);
    $this->assertIdentical($entity, $translation, 'The translation object corresponding to a non-default language is the entity object itself when the entity is language-neutral.');
    $entity->{$langcode_key}->value = $default_langcode;
    $translation = $entity->getTranslation($default_langcode);
    $this->assertIdentical($entity, $translation, 'The translation object corresponding to the default language (explicit) is the entity object itself.');
    $translation = $entity->getTranslation(LanguageInterface::LANGCODE_DEFAULT);
    $this->assertIdentical($entity, $translation, 'The translation object corresponding to the default language (implicit) is the entity object itself.');
    $this->assertTrue($entity->{$default_langcode_key}->value, 'The translation object is the default one.');

    // Create a translation and verify that the translation object and the
    // original object behave independently.
    $name = $default_langcode . '_' . $this->randomMachineName();
    $entity->name->value = $name;
    $name_translated = $langcode . '_' . $this->randomMachineName();
    $translation = $entity->addTranslation($langcode);
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
    $translation = $entity->getTranslation($langcode2);
    $value = $entity !== $translation && $translation->language()->getId() == $langcode2 && $entity->hasTranslation($langcode2);
    $this->assertTrue($value, 'A new translation object can be obtained also by specifying a valid language.');
    $this->assertEqual($entity->language()->getId(), $default_langcode, 'The original language has been preserved.');
    $translation->save();
    $hooks = $this->getHooksInfo();
    $this->assertEqual($hooks['entity_translation_insert'], $langcode2, 'The generic entity translation insertion hook has fired.');
    $this->assertEqual($hooks[$entity_type . '_translation_insert'], $langcode2, 'The entity-type-specific entity translation insertion hook has fired.');

    // Verify that trying to manipulate a translation object referring to a
    // removed translation results in exceptions being thrown.
    $entity = $this->reloadEntity($entity);
    $translation = $entity->getTranslation($langcode2);
    $entity->removeTranslation($langcode2);
    foreach (array('get', 'set', '__get', '__set', 'createDuplicate') as $method) {
      $message = format_string('The @method method raises an exception when trying to manipulate a removed translation.', array('@method' => $method));
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
    foreach (array($default_langcode, LanguageInterface::LANGCODE_DEFAULT, $this->randomMachineName()) as $invalid_langcode) {
      $message = format_string('Removing an invalid translation (@langcode) causes an exception to be thrown.', array('@langcode' => $invalid_langcode));
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
    $this->assertFalse($hooks, 'No hooks are run when adding and removing a translation without storing it.');

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
      ->create(array('name' => $this->randomMachineName(), 'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED));
    $translation = $entity->addTranslation($langcode2);
    $expected = array(
      array(
        'shape' => "shape:0:description_$langcode2",
        'color' => "color:0:description_$langcode2",
      ),
      array(
        'shape' => "shape:1:description_$langcode2",
        'color' => "color:1:description_$langcode2",
      ),
    );
    $this->assertEqual($translation->description->getValue(), $expected, 'Language-aware default values correctly populated.');
    $this->assertEqual($translation->description->getLangcode(), $langcode2, 'Field object has the expected langcode.');
  }

  /**
   * Tests language fallback applied to field and entity translations.
   */
  function testLanguageFallback() {
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
    $current_langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    $this->langcodes[] = $current_langcode;

    $values = array();
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
    $entity = $controller->create(array($langcode_key => $default_langcode) + $values[$default_langcode]);
    $entity->save();

    $entity->addTranslation($langcode, $values[$langcode]);
    $entity->save();

    // Check that retrieving the current translation works as expected.
    $entity = $this->reloadEntity($entity);
    $translation = $this->entityManager->getTranslationFromContext($entity, $langcode2);
    $this->assertEqual($translation->language()->getId(), $default_langcode, 'The current translation language matches the expected one.');

    // Check that language fallback respects language weight by default.
    $language = ConfigurableLanguage::load($languages[$langcode]->getId());
    $language->set('weight', -1);
    $language->save();
    $translation = $this->entityManager->getTranslationFromContext($entity, $langcode2);
    $this->assertEqual($translation->language()->getId(), $langcode, 'The current translation language matches the expected one.');

    // Check that the current translation is properly returned.
    $translation = $this->entityManager->getTranslationFromContext($entity);
    $this->assertEqual($langcode, $translation->language()->getId(), 'The current translation language matches the topmost language fallback candidate.');
    $entity->addTranslation($current_langcode, $values[$current_langcode]);
    $translation = $this->entityManager->getTranslationFromContext($entity);
    $this->assertEqual($current_langcode, $translation->language()->getId(), 'The current translation language matches the current language.');

    // Check that if the entity has no translation no fallback is applied.
    $entity2 = $controller->create(array($langcode_key => $default_langcode));
    // Get an view builder.
    $controller = $this->entityManager->getViewBuilder($entity_type);
    $entity2_build = $controller->view($entity2);
    $entity2_output = drupal_render($entity2_build);
    $translation = $this->entityManager->getTranslationFromContext($entity2, $default_langcode);
    $translation_build = $controller->view($translation);
    $translation_output = drupal_render($translation_build);
    $this->assertIdentical($entity2_output, $translation_output, 'When the entity has no translation no fallback is applied.');

    // Checks that entity translations are rendered properly.
    $controller = $this->entityManager->getViewBuilder($entity_type);
    $build = $controller->view($entity);
    drupal_render($build);
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
      drupal_render($build);
      $this->assertEqual($build['label']['#markup'], $values[$expected]['name'], 'The entity is rendered in the expected language.');
    }
  }

  /**
   * Check that field translatability is handled properly.
   */
  function testFieldDefinitions() {
    // Check that field translatability can be altered to be enabled or disabled
    // in field definitions.
    $entity_type = 'entity_test_mulrev';
    $this->state->set('entity_test.field_definitions.translatable', array('name' => FALSE));
    $this->entityManager->clearCachedFieldDefinitions();
    $definitions = $this->entityManager->getBaseFieldDefinitions($entity_type);
    $this->assertFalse($definitions['name']->isTranslatable(), 'Field translatability can be disabled programmatically.');

    $this->state->set('entity_test.field_definitions.translatable', array('name' => TRUE));
    $this->entityManager->clearCachedFieldDefinitions();
    $definitions = $this->entityManager->getBaseFieldDefinitions($entity_type);
    $this->assertTrue($definitions['name']->isTranslatable(), 'Field translatability can be enabled programmatically.');

    // Check that field translatability is disabled by default.
    $base_field_definitions = EntityTestMulRev::baseFieldDefinitions($this->entityManager->getDefinition($entity_type));
    $this->assertTrue(!isset($base_field_definitions['id']->translatable), 'Translatability for the <em>id</em> field is not defined.');
    $this->assertFalse($definitions['id']->isTranslatable(), 'Field translatability is disabled by default.');

    // Check that entity id keys have the expect translatability.
    $translatable_fields = array(
      'id' => TRUE,
      'uuid' => TRUE,
      'revision_id' => TRUE,
      'type' => TRUE,
      'langcode' => FALSE,
    );
    foreach ($translatable_fields as $name => $translatable) {
      $this->state->set('entity_test.field_definitions.translatable', array($name => $translatable));
      $this->entityManager->clearCachedFieldDefinitions();
      $message = format_string('Field %field cannot be translatable.', array('%field' => $name));

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
    $values = array(
      $langcode_key => $langcode,
      $this->fieldName => $this->randomMachineName(),
      $this->untranslatableFieldName => $this->randomMachineName(),
    );
    $entity = $controller->create($values);
    foreach (array($this->fieldName, $this->untranslatableFieldName) as $field_name) {
      $this->assertEqual($entity->get($field_name)->getLangcode(), $langcode, 'Field language works as expected.');
    }

    // Check that field languages keep matching entity language even after
    // changing it.
    $langcode = $this->langcodes[1];
    $entity->{$langcode_key}->value = $langcode;
    foreach (array($this->fieldName, $this->untranslatableFieldName) as $field_name) {
      $this->assertEqual($entity->get($field_name)->getLangcode(), $langcode, 'Field language works as expected after changing entity language.');
    }

    // Check that entity translation does not affect the language of original
    // field values and untranslatable ones.
    $langcode = $this->langcodes[0];
    $entity->addTranslation($this->langcodes[2], array($this->fieldName => $this->randomMachineName()));
    $entity->{$langcode_key}->value = $langcode;
    foreach (array($this->fieldName, $this->untranslatableFieldName) as $field_name) {
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
  function testEntityAdapter() {
    $entity_type = 'entity_test';
    $default_langcode = 'en';
    $values[$default_langcode] = array('name' => $this->randomString());
    $controller = $this->entityManager->getStorage($entity_type);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $controller->create($values[$default_langcode]);

    foreach ($this->langcodes as $langcode) {
      $values[$langcode] = array('name' => $this->randomString());
      $entity->addTranslation($langcode, $values[$langcode]);
    }

    $langcodes = array_merge(array($default_langcode), $this->langcodes);
    foreach ($langcodes as $langcode) {
      $adapter = $entity->getTranslation($langcode)->getTypedData();
      $name = $adapter->get('name')->value;
      $this->assertEqual($name, $values[$langcode]['name'], SafeMarkup::format('Name correctly retrieved from "@langcode" adapter', array('@langcode' => $langcode)));
    }
  }

}
