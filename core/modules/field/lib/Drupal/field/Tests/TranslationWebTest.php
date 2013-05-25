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
  public static $modules = array('language', 'field_test');

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

    $this->entity_type = 'test_entity';

    $field = array(
      'field_name' => $this->field_name,
      'type' => 'test_field',
      'cardinality' => 4,
      'translatable' => TRUE,
    );
    field_create_field($field);
    $this->field = field_read_field($this->field_name);

    $instance = array(
      'field_name' => $this->field_name,
      'entity_type' => $this->entity_type,
      'bundle' => 'test_bundle',
    );
    field_create_instance($instance);
    $this->instance = field_read_instance('test_entity', $this->field_name, 'test_bundle');

    entity_get_form_display($this->entity_type, 'test_bundle', 'default')
      ->setComponent($this->field_name)
      ->save();

    for ($i = 0; $i < 3; ++$i) {
      $language = new Language(array(
        'langcode' => 'l' . $i,
        'name' => $this->randomString(),
      ));
      language_save($language);
    }
  }

  /**
   * Tests field translations when creating a new revision.
   */
  function testFieldFormTranslationRevisions() {
    $web_user = $this->drupalCreateUser(array('access field_test content', 'administer field_test content'));
    $this->drupalLogin($web_user);

    // Prepare the field translations.
    field_test_entity_info_translatable($this->entity_type, TRUE);
    $eid = 1;
    $entity = field_test_create_entity($eid, $eid, $this->instance['bundle']);
    $available_langcodes = array_flip(field_available_languages($this->entity_type, $this->field));
    unset($available_langcodes[Language::LANGCODE_NOT_SPECIFIED]);
    $field_name = $this->field['field_name'];

    // Store the field translations.
    $entity->enforceIsNew();
    foreach ($available_langcodes as $langcode => $value) {
      $entity->{$field_name}[$langcode][0]['value'] = $value + 1;
    }
    field_test_entity_save($entity);

    // Create a new revision.
    $langcode = field_valid_language(NULL);
    $edit = array("{$field_name}[$langcode][0][value]" => $entity->{$field_name}[$langcode][0]['value'], 'revision' => TRUE);
    $this->drupalPost('test-entity/manage/' . $eid . '/edit', $edit, t('Save'));

    // Check translation revisions.
    $this->checkTranslationRevisions($eid, $eid, $available_langcodes);
    $this->checkTranslationRevisions($eid, $eid + 1, $available_langcodes);
  }

  /**
   * Check if the field translation attached to the entity revision identified
   * by the passed arguments were correctly stored.
   */
  private function checkTranslationRevisions($eid, $evid, $available_langcodes) {
    $field_name = $this->field['field_name'];
    $entity = field_test_entity_test_load($eid, $evid);
    foreach ($available_langcodes as $langcode => $value) {
      $passed = isset($entity->{$field_name}[$langcode]) && $entity->{$field_name}[$langcode][0]['value'] == $value + 1;
      $this->assertTrue($passed, format_string('The @language translation for revision @revision was correctly stored', array('@language' => $langcode, '@revision' => $entity->ftvid)));
    }
  }
}
