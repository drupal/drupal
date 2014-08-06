<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\TermFieldMultipleVocabularyTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests a taxonomy term reference field that allows multiple vocabularies.
 *
 * @group taxonomy
 */
class TermFieldMultipleVocabularyTest extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test');

  protected $vocabulary1;
  protected $vocabulary2;

  function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(array('view test entity', 'administer entity_test content', 'administer taxonomy'));
    $this->drupalLogin($web_user);
    $this->vocabulary1 = $this->createVocabulary();
    $this->vocabulary2 = $this->createVocabulary();

    // Set up a field and instance.
    $this->field_name = drupal_strtolower($this->randomMachineName());
    entity_create('field_storage_config', array(
      'name' => $this->field_name,
      'entity_type' => 'entity_test',
      'type' => 'taxonomy_term_reference',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
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
    ))->save();
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
   * Tests term reference field and widget with multiple vocabularies.
   */
  function testTaxonomyTermFieldMultipleVocabularies() {
    // Create a term in each vocabulary.
    $term1 = $this->createTerm($this->vocabulary1);
    $term2 = $this->createTerm($this->vocabulary2);

    // Submit an entity with both terms.
    $this->drupalGet('entity_test/add');
    // Just check if the widget for the select is displayed, the NULL value is
    // used to ignore the value check.
    $this->assertFieldByName("{$this->field_name}[]", NULL, 'Widget is displayed.');
    $edit = array(
      'user_id' => mt_rand(0, 10),
      'name' => $this->randomMachineName(),
      "{$this->field_name}[]" => array($term1->id(), $term2->id()),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)), 'Entity was created.');

    // Render the entity.
    $entity = entity_load('entity_test', $id);
    $display = entity_get_display($entity->getEntityTypeId(), $entity->bundle(), 'full');
    $content = $display->build($entity);
    $this->drupalSetContent(drupal_render($content));
    $this->assertText($term1->getName(), 'Term 1 name is displayed.');
    $this->assertText($term2->getName(), 'Term 2 name is displayed.');

    // Delete vocabulary 2.
    $this->vocabulary2->delete();

    // Re-render the content.
    $entity = entity_load('entity_test', $id);
    $display = entity_get_display($entity->getEntityTypeId(), $entity->bundle(), 'full');
    $content = $display->build($entity);
    $this->drupalSetContent(drupal_render($content));

    // Term 1 should still be displayed; term 2 should not be.
    $this->assertText($term1->getName(), 'Term 1 name is displayed.');
    $this->assertNoText($term2->getName(), 'Term 2 name is not displayed.');

    // Verify that field storage and instance settings are correct.
    $field_storage = FieldStorageConfig::loadByName('entity_test', $this->field_name);
    $this->assertEqual(count($field_storage->getSetting('allowed_values')), 1, 'Only one vocabulary is allowed for the field.');

    // The widget should still be displayed.
    $this->drupalGet('entity_test/add');
    // Just check if the widget for the select is displayed, the NULL value is
    // used to ignore the value check.
    $this->assertFieldByName("{$this->field_name}[]", NULL, 'Widget is still displayed.');

    // Term 1 should still pass validation.
    $edit = array(
      'user_id' => mt_rand(0, 10),
      'name' => $this->randomMachineName(),
      "{$this->field_name}[]" => array($term1->id()),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
  }
}
