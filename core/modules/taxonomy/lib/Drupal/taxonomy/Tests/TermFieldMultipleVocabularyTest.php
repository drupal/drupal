<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\TermFieldMultipleVocabularyTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\Core\Language\Language;

/**
 * Tests a taxonomy term reference field that allows multiple vocabularies.
 */
class TermFieldMultipleVocabularyTest extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_test');

  protected $instance;
  protected $vocabulary1;
  protected $vocabulary2;

  public static function getInfo() {
    return array(
      'name' => 'Multiple vocabulary term reference field',
      'description' => 'Tests term reference fields that allow multiple vocabularies.',
      'group' => 'Taxonomy',
    );
  }

  function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(array('access field_test content', 'administer field_test content', 'administer taxonomy'));
    $this->drupalLogin($web_user);
    $this->vocabulary1 = $this->createVocabulary();
    $this->vocabulary2 = $this->createVocabulary();

    // Set up a field and instance.
    $this->field_name = drupal_strtolower($this->randomName());
    $this->field = array(
      'field_name' => $this->field_name,
      'type' => 'taxonomy_term_reference',
      'cardinality' => FIELD_CARDINALITY_UNLIMITED,
      'settings' => array(
        'allowed_values' => array(
          array(
            'vocabulary' => $this->vocabulary1->id(),
            'parent' => '0',
          ),
          array(
            'vocabulary' => $this->vocabulary2->id(),
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
   * Tests term reference field and widget with multiple vocabularies.
   */
  function testTaxonomyTermFieldMultipleVocabularies() {
    // Create a term in each vocabulary.
    $term1 = $this->createTerm($this->vocabulary1);
    $term2 = $this->createTerm($this->vocabulary2);

    // Submit an entity with both terms.
    $langcode = Language::LANGCODE_NOT_SPECIFIED;
    $this->drupalGet('test-entity/add/test_bundle');
    $this->assertFieldByName("{$this->field_name}[$langcode][]", '', 'Widget is displayed');
    $edit = array(
      "{$this->field_name}[$langcode][]" => array($term1->id(), $term2->id()),
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    preg_match('|test-entity/manage/(\d+)/edit|', $this->url, $match);
    $id = $match[1];
    $this->assertRaw(t('test_entity @id has been created.', array('@id' => $id)), 'Entity was created.');

    // Render the entity.
    $entity = field_test_entity_test_load($id);
    $entities = array($id => $entity);
    $display = entity_get_display($entity->entityType(), $entity->bundle(), 'full');
    field_attach_prepare_view('test_entity', $entities, array($entity->bundle() => $display));
    $entity->content = field_attach_view($entity, $display);
    $this->content = drupal_render($entity->content);
    $this->assertText($term1->label(), 'Term 1 name is displayed.');
    $this->assertText($term2->label(), 'Term 2 name is displayed.');

    // Delete vocabulary 2.
    $this->vocabulary2->delete();

    // Re-render the content.
    $entity = field_test_entity_test_load($id);
    $entities = array($id => $entity);
    $display = entity_get_display($entity->entityType(), $entity->bundle(), 'full');
    field_attach_prepare_view('test_entity', $entities, array($entity->bundle() => $display));
    $entity->content = field_attach_view($entity, $display);
    $this->plainTextContent = FALSE;
    $this->content = drupal_render($entity->content);

    // Term 1 should still be displayed; term 2 should not be.
    $this->assertText($term1->label(), 'Term 1 name is displayed.');
    $this->assertNoText($term2->label(), 'Term 2 name is not displayed.');

    // Verify that field and instance settings are correct.
    $field_info = field_info_field($this->field_name);
    $this->assertEqual(count($field_info['settings']['allowed_values']), 1, 'Only one vocabulary is allowed for the field.');

    // The widget should still be displayed.
    $this->drupalGet('test-entity/add/test_bundle');
    $this->assertFieldByName("{$this->field_name}[$langcode][]", '', 'Widget is still displayed');

    // Term 1 should still pass validation.
    $edit = array(
      "{$this->field_name}[$langcode][]" => array($term1->id()),
    );
    $this->drupalPost(NULL, $edit, t('Save'));
  }
}
