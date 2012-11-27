<?php

/**
 * @file
 * Definition of Drupal\entity\Tests\EntityTranslationUITest.
 */

namespace Drupal\translation_entity\Tests;

use Drupal\Core\Entity\DatabaseStorageControllerNG;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityNG;
use Drupal\Core\Language\Language;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the Entity Translation UI.
 */
abstract class EntityTranslationUITest extends WebTestBase {

  /**
   * The enabled languages.
   *
   * @var array
   */
  protected $langcodes;

  /**
   * The entity type being tested.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The bundle being tested.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The name of the field used to test translation.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * Whether the behavior of the language selector should be tested.
   *
   * @var boolean
   */
  protected $testLanguageSelector = TRUE;


  /**
   * Overrides \Drupal\simpletest\WebTestBase::setUp().
   */
  function setUp() {
    parent::setUp();

    $this->setupLanguages();
    $this->setupBundle();
    $this->enableTranslation();
    $this->setupTranslator();
    $this->setupTestFields();
  }

  /**
   * Enables additional languages.
   */
  protected function setupLanguages() {
    $this->langcodes = array('it', 'fr');
    foreach ($this->langcodes as $langcode) {
      language_save(new Language(array('langcode' => $langcode)));
    }
    array_unshift($this->langcodes, language_default()->langcode);
  }

  /**
   * Creates or initializes the bundle date if needed.
   */
  protected function setupBundle() {
    if (empty($this->bundle)) {
      $this->bundle = $this->entityType;
    }
  }

  /**
   * Enables translation for the current entity type and bundle.
   */
  protected function enableTranslation() {
    // Enable translation for the current entity type and ensure the change is
    // picked up.
    translation_entity_set_config($this->entityType, $this->bundle, 'enabled', TRUE);
    drupal_static_reset();
    entity_info_cache_clear();
    menu_router_rebuild();
  }

  /**
   * Returns an array of permissions needed for the translator.
   */
  abstract function getTranslatorPermissions();

  /**
   * Creates and activates a translator user.
   */
  protected function setupTranslator() {
    $translator = $this->drupalCreateUser($this->getTranslatorPermissions());
    $this->drupalLogin($translator);
  }

  /**
   * Creates the test fields.
   */
  protected function setupTestFields() {
    $this->fieldName = 'field_test_et_ui_test';

    $field = array(
      'field_name' => $this->fieldName,
      'type' => 'text',
      'cardinality' => 1,
      'translatable' => TRUE,
    );
    field_create_field($field);

    $instance = array(
      'entity_type' => $this->entityType,
      'field_name' => $this->fieldName,
      'bundle' => $this->bundle,
      'label' => 'Test translatable text-field',
      'widget' => array(
        'type' => 'text_textfield',
        'weight' => 0,
      ),
    );
    field_create_instance($instance);
  }

  /**
   * Tests the basic translation UI.
   */
  function testTranslationUI() {
    // Create a new test entity with original values in the default language.
    $default_langcode = $this->langcodes[0];
    $values[$default_langcode] = $this->getNewEntityValues($default_langcode);
    $id = $this->createEntity($values[$default_langcode], $default_langcode);
    $entity = entity_load($this->entityType, $id, TRUE);
    $this->assertTrue($entity, t('Entity found in the database.'));

    $translation = $this->getTranslation($entity, $default_langcode);
    foreach ($values[$default_langcode] as $property => $value) {
      $stored_value = $this->getValue($translation, $property, $default_langcode);
      $value = is_array($value) ? $value[0]['value'] : $value;
      $message = format_string('@property correctly stored in the default language.', array('@property' => $property));
      $this->assertIdentical($stored_value, $value, $message);
    }

    // Add an entity translation.
    $langcode = 'it';
    $values[$langcode] = $this->getNewEntityValues($langcode);

    $controller = translation_entity_controller($this->entityType);
    $base_path = $controller->getBasePath($entity);
    $path = $langcode . '/' . $base_path . '/translations/add/' . $default_langcode . '/' . $langcode;
    $this->drupalPost($path, $this->getEditValues($values, $langcode), t('Save'));
    if ($this->testLanguageSelector) {
      $this->assertNoFieldByXPath('//select[@id="edit-langcode"]', NULL, 'Language selector correclty disabled on translations.');
    }
    $entity = entity_load($this->entityType, $entity->id(), TRUE);

    // Switch the source language.
    $langcode = 'fr';
    $source_langcode = 'it';
    $edit = array('source_langcode[source]' => $source_langcode);
    $path = $langcode . '/' . $base_path . '/translations/add/' . $default_langcode . '/' . $langcode;
    $this->drupalPost($path, $edit, t('Change'));
    $this->assertFieldByXPath("//input[@name=\"{$this->fieldName}[fr][0][value]\"]", $values[$source_langcode][$this->fieldName][0]['value'], 'Source language correctly switched.');

    // Add another translation and mark the other ones as outdated.
    $values[$langcode] = $this->getNewEntityValues($langcode);
    $edit = $this->getEditValues($values, $langcode) + array('translation[retranslate]' => TRUE);
    $this->drupalPost($path, $edit, t('Save'));
    $entity = entity_load($this->entityType, $entity->id(), TRUE);

    // Check that the entered values have been correctly stored.
    foreach ($values as $langcode => $property_values) {
      $translation = $this->getTranslation($entity, $langcode);
      foreach ($property_values as $property => $value) {
        $stored_value = $this->getValue($translation, $property, $langcode);
        $value = is_array($value) ? $value[0]['value'] : $value;
        $message = format_string('%property correctly stored with language %language.', array('%property' => $property, '%language' => $langcode));
        $this->assertEqual($stored_value, $value, $message);
      }
    }

    // Check that every translation has the correct "outdated" status.
    foreach ($this->langcodes as $enabled_langcode) {
      $prefix = $enabled_langcode != $default_langcode ? $enabled_langcode . '/' : '';
      $path = $prefix . $controller->getEditPath($entity);
      $this->drupalGet($path);
      if ($enabled_langcode == $langcode) {
        $this->assertFieldByXPath('//input[@name="translation[retranslate]"]', FALSE, 'The retranslate flag is not checked by default.');
      }
      else {
        $this->assertFieldByXPath('//input[@name="translation[translate]"]', TRUE, 'The translate flag is checked by default.');
        $edit = array('translation[translate]' => FALSE);
        $this->drupalPost($path, $edit, t('Save'));
        $this->drupalGet($path);
        $this->assertFieldByXPath('//input[@name="translation[retranslate]"]', FALSE, 'The retranslate flag is now shown.');
        $entity = entity_load($this->entityType, $entity->id(), TRUE);
        $this->assertFalse($entity->retranslate[$enabled_langcode], 'The "outdated" status has been correctly stored.');
      }
    }

    // Confirm and delete a translation.
    $this->drupalPost($path, array(), t('Delete translation'));
    $this->drupalPost(NULL, array(), t('Delete'));
    $entity = entity_load($this->entityType, $entity->id(), TRUE);
    $translations = $entity->getTranslationLanguages();
    $this->assertTrue(count($translations) == 2 && empty($translations[$enabled_langcode]), 'Translation successfully deleted.');
  }

  /**
   * Creates the entity to be translated.
   *
   * @param array $values
   *   An array of initial values for the entity.
   * @param string $langcode
   *   The initial language code of the entity.
   * @param string $bundle_name
   *   (optional) The entity bundle, if the entity uses bundles. Defaults to
   *   NULL. If left NULL, $this->bundle will be used.
   *
   * @return
   *   The entity id.
   */
  protected function createEntity($values, $langcode, $bundle_name = NULL) {
    $entity_values = $values;
    $entity_values['langcode'] = $langcode;
    $info = entity_get_info($this->entityType);
    if (!empty($info['entity_keys']['bundle'])) {
      $entity_values[$info['entity_keys']['bundle']] = $bundle_name ?: $this->bundle;
    }
    $controller = entity_get_controller($this->entityType);
    if (!($controller instanceof DatabaseStorageControllerNG)) {
      foreach ($values as $property => $value) {
        if (is_array($value)) {
          $entity_values[$property] = array($langcode => $value);
        }
      }
    }
    $entity = entity_create($this->entityType, $entity_values);
    $entity->save();
    return $entity->id();
  }

  /**
   * Returns an array of entity field values to be tested.
   */
  protected function getNewEntityValues($langcode) {
    return array($this->fieldName => array(array('value' => $this->randomName(16))));
  }

  /**
   * Returns an edit array containing the values to be posted.
   */
  protected function getEditValues($values, $langcode, $new = FALSE) {
    $edit = $values[$langcode];
    $langcode = $new ? LANGUAGE_NOT_SPECIFIED : $langcode;
    foreach ($values[$langcode] as $property => $value) {
      if (is_array($value)) {
        $edit["{$property}[$langcode][0][value]"] = $value[0]['value'];
        unset($edit[$property]);
      }
    }
    return $edit;
  }

  /**
   * Returns the translation object to use to retrieve the translated values.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being tested.
   * @param string $langcode
   *   The language code identifying the translation to be retrieved.
   *
   * @return \Drupal\Core\TypedData\TranslatableInterface
   *   The translation object to act on.
   */
  protected function getTranslation(EntityInterface $entity, $langcode) {
    return $entity instanceof EntityNG ? $entity->getTranslation($langcode) : $entity;
  }

  /**
   * Returns the value for the specified property in the given language.
   *
   * @param \Drupal\Core\TypedData\TranslatableInterface $translation
   *   The translation object the property value should be retrieved from.
   * @param string $property
   *   The property name.
   * @param string $langcode
   *   The property value.
   *
   * @return
   *   The property value.
   */
  protected function getValue(ComplexDataInterface $translation, $property, $langcode) {
    if (($translation instanceof EntityInterface) && !($translation instanceof EntityNG)) {
      return is_array($translation->$property) ? $translation->{$property}[$langcode][0]['value'] : $translation->$property;
    }
    else {
      return $translation->get($property)->value;
    }
  }

}
