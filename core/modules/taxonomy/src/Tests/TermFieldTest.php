<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\TermFieldTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the creation of term fields.
 *
 * @group taxonomy
 */
class TermFieldTest extends TaxonomyTestBase {

  /**
   * Name of the taxonomy term reference field.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The field storage to test against.
   *
   * @var \Drupal\field\FieldStorageConfigInterface
   */
  protected $fieldStorage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test', 'field_ui');

  /**
   * Vocabulary for testing.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  protected function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(array(
      'view test entity',
      'administer entity_test content',
      'administer taxonomy',
      'administer entity_test fields',
    ));
    $this->drupalLogin($web_user);
    $this->vocabulary = $this->createVocabulary();

    // Setup a field.
    $this->fieldName = Unicode::strtolower($this->randomMachineName());
    $this->fieldStorage = entity_create('field_storage_config', array(
      'field_name' => $this->fieldName,
      'entity_type' => 'entity_test',
      'type' => 'taxonomy_term_reference',
      'settings' => array(
        'allowed_values' => array(
          array(
            'vocabulary' => $this->vocabulary->id(),
            'parent' => '0',
          ),
        ),
      )
    ));
    $this->fieldStorage->save();
    entity_create('field_config', array(
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
    ))->save();
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($this->fieldName, array(
        'type' => 'options_select',
      ))
      ->save();
    entity_get_display('entity_test', 'entity_test', 'full')
      ->setComponent($this->fieldName, array(
        'type' => 'taxonomy_term_reference_link',
      ))
      ->save();
  }

  /**
   * Test term field validation.
   */
  function testTaxonomyTermFieldValidation() {
    // Test validation with a valid value.
    $term = $this->createTerm($this->vocabulary);
    $entity = entity_create('entity_test');
    $entity->{$this->fieldName}->target_id = $term->id();
    $violations = $entity->{$this->fieldName}->validate();
    $this->assertEqual(count($violations) , 0, 'Correct term does not cause validation error.');

    // Test validation with an invalid valid value (wrong vocabulary).
    $bad_term = $this->createTerm($this->createVocabulary());
    $entity = entity_create('entity_test');
    $entity->{$this->fieldName}->target_id = $bad_term->id();
    $violations = $entity->{$this->fieldName}->validate();
    $this->assertEqual(count($violations) , 1, 'Wrong term causes validation error.');
  }

  /**
   * Test widgets.
   */
  function testTaxonomyTermFieldWidgets() {
    // Create a term in the vocabulary.
    $term = $this->createTerm($this->vocabulary);

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName($this->fieldName, NULL, 'Widget is displayed.');

    // Submit with some value.
    $edit = array(
      $this->fieldName => array($term->id()),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)));

    // Display the object.
    $entity = entity_load('entity_test', $id);
    $display = entity_get_display($entity->getEntityTypeId(), $entity->bundle(), 'full');
    $content = $display->build($entity);
    $this->drupalSetContent(drupal_render($content));
    $this->assertText($term->getName(), 'Term label is displayed.');

    // Delete the vocabulary and verify that the widget is gone.
    $this->vocabulary->delete();
    $this->drupalGet('entity_test/add');
    $this->assertNoFieldByName($this->fieldName, '', 'Widget is not displayed.');
  }

  /**
   * No php error message on the field setting page for autocomplete widget.
   */
  function testTaxonomyTermFieldSettingsAutocompleteWidget() {
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($this->fieldName, array(
        'type' => 'taxonomy_autocomplete',
        'weight' => 1,
      ))
      ->save();
    $this->drupalGet('entity_test/structure/entity_test/fields/entity_test.entity_test.' . $this->fieldName);
    $this->assertNoErrorsLogged();
  }

  /**
   * Tests that vocabulary machine name changes are mirrored in field definitions.
   */
  function testTaxonomyTermFieldChangeMachineName() {
    // Add several entries in the 'allowed_values' setting, to make sure that
    // they all get updated.
    $this->fieldStorage->settings['allowed_values'] = array(
      array(
        'vocabulary' => $this->vocabulary->id(),
        'parent' => '0',
      ),
      array(
        'vocabulary' => $this->vocabulary->id(),
        'parent' => '0',
      ),
      array(
        'vocabulary' => 'foo',
        'parent' => '0',
      ),
    );
    $this->fieldStorage->save();
    // Change the machine name.
    $new_name = Unicode::strtolower($this->randomMachineName());
    $this->vocabulary->set('vid', $new_name);
    $this->vocabulary->save();

    // Check that the field is still attached to the vocabulary.
    $field_storage = FieldStorageConfig::loadByName('entity_test', $this->fieldName);
    $allowed_values = $field_storage->getSetting('allowed_values');
    $this->assertEqual($allowed_values[0]['vocabulary'], $new_name, 'Index 0: Machine name was updated correctly.');
    $this->assertEqual($allowed_values[1]['vocabulary'], $new_name, 'Index 1: Machine name was updated correctly.');
    $this->assertEqual($allowed_values[2]['vocabulary'], 'foo', 'Index 2: Machine name was left untouched.');
  }
}
