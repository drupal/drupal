<?php

/**
 * @file
 * Definition of Drupal\field\Tests\TranslationWebTest.
 */

namespace Drupal\field\Tests;

use Drupal\Core\Language\Language;

/**
 * Web test class for the multilanguage fields logic.
 */
class TranslationWebTest extends FieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'field_test', 'entity_test');

  /**
   * The name of the field to use in this test.
   *
   * @var string
   */
  protected $field_name;

  /**
   * The name of the entity type to use in this test.
   *
   * @var string
   */
  protected $entity_type = 'entity_test_rev';

  /**
   * The field to use in this test.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * The field instance to use in this test.
   *
   * @var \Drupal\field\Entity\FieldInstanceConfig
   */
  protected $instance;

  public static function getInfo() {
    return array(
      'name' => 'Field translations web tests',
      'description' => 'Test multilanguage fields logic that require a full environment.',
      'group' => 'Field API',
    );
  }

  function setUp() {
    parent::setUp();

    $this->field_name = drupal_strtolower($this->randomName() . '_field_name');

    $field = array(
      'name' => $this->field_name,
      'entity_type' => $this->entity_type,
      'type' => 'test_field',
      'cardinality' => 4,
      'translatable' => TRUE,
    );
    entity_create('field_config', $field)->save();
    $this->field = entity_load('field_config', $this->entity_type . '.' . $this->field_name);

    $instance = array(
      'field_name' => $this->field_name,
      'entity_type' => $this->entity_type,
      'bundle' => $this->entity_type,
    );
    entity_create('field_instance_config', $instance)->save();
    $this->instance = entity_load('field_instance_config', 'entity_test.' . $instance['bundle'] . '.' . $this->field_name);

    entity_get_form_display($this->entity_type, $this->entity_type, 'default')
      ->setComponent($this->field_name)
      ->save();

    for ($i = 0; $i < 3; ++$i) {
      $language = new Language(array(
        'id' => 'l' . $i,
        'name' => $this->randomString(),
      ));
      language_save($language);
    }
  }

  /**
   * Tests field translations when creating a new revision.
   */
  function testFieldFormTranslationRevisions() {
    $web_user = $this->drupalCreateUser(array('view test entity', 'administer entity_test content'));
    $this->drupalLogin($web_user);

    // Prepare the field translations.
    field_test_entity_info_translatable($this->entity_type, TRUE);
    $entity = entity_create($this->entity_type);
    $available_langcodes = array_flip(array_keys(language_list()));
    $field_name = $this->field->getName();

    // Store the field translations.
    ksort($available_langcodes);
    $entity->langcode->value = key($available_langcodes);
    foreach ($available_langcodes as $langcode => $value) {
      $entity->getTranslation($langcode)->{$field_name}->value = $value + 1;
    }
    $entity->save();

    // Create a new revision.
    $edit = array(
      'user_id' => 1,
      'name' => $this->randomName(),
      "{$field_name}[0][value]" => $entity->{$field_name}->value,
      'revision' => TRUE,
    );
    $this->drupalPostForm($this->entity_type . '/manage/' . $entity->id(), $edit, t('Save'));

    // Check translation revisions.
    $this->checkTranslationRevisions($entity->id(), $entity->getRevisionId(), $available_langcodes);
    $this->checkTranslationRevisions($entity->id(), $entity->getRevisionId() + 1, $available_langcodes);
  }

  /**
   * Check if the field translation attached to the entity revision identified
   * by the passed arguments were correctly stored.
   */
  private function checkTranslationRevisions($id, $revision_id, $available_langcodes) {
    $field_name = $this->field->getName();
    $entity = entity_revision_load($this->entity_type, $revision_id);
    foreach ($available_langcodes as $langcode => $value) {
      $passed = $entity->getTranslation($langcode)->{$field_name}->value == $value + 1;
      $this->assertTrue($passed, format_string('The @language translation for revision @revision was correctly stored', array('@language' => $langcode, '@revision' => $entity->getRevisionId())));
    }
  }
}
