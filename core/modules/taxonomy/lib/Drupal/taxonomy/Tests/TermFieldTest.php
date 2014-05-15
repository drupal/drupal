<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\TermFieldTest.
 */

namespace Drupal\taxonomy\Tests;
use Drupal\field\Entity\FieldConfig;

/**
 * Tests for taxonomy term field and formatter.
 */
class TermFieldTest extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test', 'field_ui');

  protected $instance;
  protected $vocabulary;

  public static function getInfo() {
    return array(
      'name' => 'Taxonomy term reference field',
      'description' => 'Test the creation of term fields.',
      'group' => 'Taxonomy',
    );
  }

  function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(array(
      'view test entity',
      'administer entity_test content',
      'administer taxonomy',
      'administer entity_test fields',
    ));
    $this->drupalLogin($web_user);
    $this->vocabulary = $this->createVocabulary();

    // Setup a field and instance.
    $this->field_name = drupal_strtolower($this->randomName());
    $this->field = entity_create('field_config', array(
      'name' => $this->field_name,
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
    $this->field->save();
    entity_create('field_instance_config', array(
      'field_name' => $this->field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ))->save();
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($this->field_name, array(
        'type' => 'options_select',
      ))
      ->save();
    entity_get_display('entity_test', 'entity_test', 'full')
      ->setComponent($this->field_name, array(
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
    $entity->{$this->field_name}->target_id = $term->id();
    $violations = $entity->{$this->field_name}->validate();
    $this->assertEqual(count($violations) , 0, 'Correct term does not cause validation error.');

    // Test validation with an invalid valid value (wrong vocabulary).
    $bad_term = $this->createTerm($this->createVocabulary());
    $entity = entity_create('entity_test');
    $entity->{$this->field_name}->target_id = $bad_term->id();
    $violations = $entity->{$this->field_name}->validate();
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
    $this->assertFieldByName($this->field_name, NULL, 'Widget is displayed.');

    // Submit with some value.
    $edit = array(
      'user_id' => 1,
      'name' => $this->randomName(),
      $this->field_name => array($term->id()),
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
    $this->assertNoFieldByName($this->field_name, '', 'Widget is not displayed.');
  }

  /**
   * No php error message on the field setting page for autocomplete widget.
   */
  function testTaxonomyTermFieldInstanceSettingsAutocompleteWidget() {
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($this->field_name, array(
        'type' => 'taxonomy_autocomplete',
        'weight' => 1,
      ))
      ->save();
    $this->drupalGet('entity_test/structure/entity_test/fields/entity_test.entity_test.' . $this->field_name);
    $this->assertNoErrorsLogged();
  }

  /**
   * Tests that vocabulary machine name changes are mirrored in field definitions.
   */
  function testTaxonomyTermFieldChangeMachineName() {
    // Add several entries in the 'allowed_values' setting, to make sure that
    // they all get updated.
    $this->field->settings['allowed_values'] = array(
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
    $this->field->save();
    // Change the machine name.
    $new_name = drupal_strtolower($this->randomName());
    $this->vocabulary->vid = $new_name;
    $this->vocabulary->save();

    // Check that the field instance is still attached to the vocabulary.
    $field = FieldConfig::loadByName('entity_test', $this->field_name);
    $allowed_values = $field->getSetting('allowed_values');
    $this->assertEqual($allowed_values[0]['vocabulary'], $new_name, 'Index 0: Machine name was updated correctly.');
    $this->assertEqual($allowed_values[1]['vocabulary'], $new_name, 'Index 1: Machine name was updated correctly.');
    $this->assertEqual($allowed_values[2]['vocabulary'], 'foo', 'Index 2: Machine name was left untouched.');
  }
}
