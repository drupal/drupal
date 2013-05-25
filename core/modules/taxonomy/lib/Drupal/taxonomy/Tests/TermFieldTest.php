<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\TermFieldTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\Core\Language\Language;
use Drupal\field\FieldValidationException;

/**
 * Tests for taxonomy term field and formatter.
 */
class TermFieldTest extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_test');

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

    $web_user = $this->drupalCreateUser(array('access field_test content', 'administer field_test content', 'administer taxonomy'));
    $this->drupalLogin($web_user);
    $this->vocabulary = $this->createVocabulary();

    // Setup a field and instance.
    $this->field_name = drupal_strtolower($this->randomName());
    $this->field = array(
      'field_name' => $this->field_name,
      'type' => 'taxonomy_term_reference',
      'settings' => array(
        'allowed_values' => array(
          array(
            'vocabulary' => $this->vocabulary->id(),
            'parent' => '0',
          ),
        ),
      )
    );
    field_create_field($this->field);
    $this->instance = array(
      'field_name' => $this->field_name,
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
    );
    field_create_instance($this->instance);
    entity_get_form_display('test_entity', 'test_bundle', 'default')
      ->setComponent($this->field_name, array(
        'type' => 'options_select',
      ))
      ->save();
    entity_get_display('test_entity', 'test_bundle', 'full')
      ->setComponent($this->field_name, array(
        'type' => 'taxonomy_term_reference_link',
      ))
      ->save();
  }

  /**
   * Test term field validation.
   */
  function testTaxonomyTermFieldValidation() {
    // Test valid and invalid values with field_attach_validate().
    $langcode = Language::LANGCODE_NOT_SPECIFIED;
    $entity = field_test_create_entity();
    $term = $this->createTerm($this->vocabulary);
    $entity->{$this->field_name}[$langcode][0]['tid'] = $term->id();
    try {
      field_attach_validate($entity);
      $this->pass('Correct term does not cause validation error.');
    }
    catch (FieldValidationException $e) {
      $this->fail('Correct term does not cause validation error.');
    }

    $entity = field_test_create_entity();
    $bad_term = $this->createTerm($this->createVocabulary());
    $entity->{$this->field_name}[$langcode][0]['tid'] = $bad_term->id();
    try {
      field_attach_validate($entity);
      $this->fail('Wrong term causes validation error.');
    }
    catch (FieldValidationException $e) {
      $this->pass('Wrong term causes validation error.');
    }
  }

  /**
   * Test widgets.
   */
  function testTaxonomyTermFieldWidgets() {
    // Create a term in the vocabulary.
    $term = $this->createTerm($this->vocabulary);

    // Display creation form.
    $langcode = Language::LANGCODE_NOT_SPECIFIED;
    $this->drupalGet('test-entity/add/test_bundle');
    $this->assertFieldByName("{$this->field_name}[$langcode]", '', 'Widget is displayed.');

    // Submit with some value.
    $edit = array(
      "{$this->field_name}[$langcode]" => array($term->id()),
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    preg_match('|test-entity/manage/(\d+)/edit|', $this->url, $match);
    $id = $match[1];
    $this->assertRaw(t('test_entity @id has been created.', array('@id' => $id)), 'Entity was created.');

    // Display the object.
    $entity = field_test_entity_test_load($id);
    $entities = array($id => $entity);
    $display = entity_get_display($entity->entityType(), $entity->bundle(), 'full');
    field_attach_prepare_view('test_entity', $entities, array($entity->bundle() => $display));
    $entity->content = field_attach_view($entity, $display);
    $this->content = drupal_render($entity->content);
    $this->assertText($term->label(), 'Term label is displayed.');

    // Delete the vocabulary and verify that the widget is gone.
    $this->vocabulary->delete();
    $this->drupalGet('test-entity/add/test_bundle');
    $this->assertNoFieldByName("{$this->field_name}[$langcode]", '', 'Widget is not displayed');
  }

  /**
   * Tests that vocabulary machine name changes are mirrored in field definitions.
   */
  function testTaxonomyTermFieldChangeMachineName() {
    // Add several entries in the 'allowed_values' setting, to make sure that
    // they all get updated.
    $this->field['settings']['allowed_values'] = array(
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
    field_update_field($this->field);
    // Change the machine name.
    $new_name = drupal_strtolower($this->randomName());
    $this->vocabulary->vid = $new_name;
    $this->vocabulary->save();

    // Check that the field instance is still attached to the vocabulary.
    $field = field_info_field($this->field_name);
    $allowed_values = $field['settings']['allowed_values'];
    $this->assertEqual($allowed_values[0]['vocabulary'], $new_name, 'Index 0: Machine name was updated correctly.');
    $this->assertEqual($allowed_values[1]['vocabulary'], $new_name, 'Index 1: Machine name was updated correctly.');
    $this->assertEqual($allowed_values[2]['vocabulary'], 'foo', 'Index 2: Machine name was left untouched.');
  }
}
