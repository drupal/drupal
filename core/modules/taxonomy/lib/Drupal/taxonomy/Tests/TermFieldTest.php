<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\TermFieldTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\field\FieldValidationException;

/**
 * Tests for taxonomy term field and formatter.
 */
class TermFieldTest extends TaxonomyTestBase {

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
    parent::setUp('field_test');

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
            'vocabulary' => $this->vocabulary->machine_name,
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
      'widget' => array(
        'type' => 'options_select',
      ),
      'display' => array(
        'full' => array(
          'type' => 'taxonomy_term_reference_link',
        ),
      ),
    );
    field_create_instance($this->instance);
  }

  /**
   * Test term field validation.
   */
  function testTaxonomyTermFieldValidation() {
    // Test valid and invalid values with field_attach_validate().
    $langcode = LANGUAGE_NOT_SPECIFIED;
    $entity = field_test_create_stub_entity();
    $term = $this->createTerm($this->vocabulary);
    $entity->{$this->field_name}[$langcode][0]['tid'] = $term->tid;
    try {
      field_attach_validate('test_entity', $entity);
      $this->pass('Correct term does not cause validation error.');
    }
    catch (FieldValidationException $e) {
      $this->fail('Correct term does not cause validation error.');
    }

    $entity = field_test_create_stub_entity();
    $bad_term = $this->createTerm($this->createVocabulary());
    $entity->{$this->field_name}[$langcode][0]['tid'] = $bad_term->tid;
    try {
      field_attach_validate('test_entity', $entity);
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
    $langcode = LANGUAGE_NOT_SPECIFIED;
    $this->drupalGet('test-entity/add/test_bundle');
    $this->assertFieldByName("{$this->field_name}[$langcode]", '', 'Widget is displayed.');

    // Submit with some value.
    $edit = array(
      "{$this->field_name}[$langcode]" => array($term->tid),
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    preg_match('|test-entity/manage/(\d+)/edit|', $this->url, $match);
    $id = $match[1];
    $this->assertRaw(t('test_entity @id has been created.', array('@id' => $id)), 'Entity was created.');

    // Display the object.
    $entity = field_test_entity_test_load($id);
    $entities = array($id => $entity);
    field_attach_prepare_view('test_entity', $entities, 'full');
    $entity->content = field_attach_view('test_entity', $entity, 'full');
    $this->content = drupal_render($entity->content);
    $this->assertText($term->name, 'Term name is displayed.');

    // Delete the vocabulary and verify that the widget is gone.
    taxonomy_vocabulary_delete($this->vocabulary->vid);
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
        'vocabulary' => $this->vocabulary->machine_name,
        'parent' => '0',
      ),
      array(
        'vocabulary' => $this->vocabulary->machine_name,
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
    $this->vocabulary->machine_name = $new_name;
    taxonomy_vocabulary_save($this->vocabulary);

    // Check that the field instance is still attached to the vocabulary.
    $field = field_info_field($this->field_name);
    $allowed_values = $field['settings']['allowed_values'];
    $this->assertEqual($allowed_values[0]['vocabulary'], $new_name, 'Index 0: Machine name was updated correctly.');
    $this->assertEqual($allowed_values[1]['vocabulary'], $new_name, 'Index 1: Machine name was updated correctly.');
    $this->assertEqual($allowed_values[2]['vocabulary'], 'foo', 'Index 2: Machine name was left untouched.');
  }
}
