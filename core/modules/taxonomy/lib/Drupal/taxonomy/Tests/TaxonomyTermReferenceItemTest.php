<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\TaxonomyTermReferenceItemTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\Core\Language\Language;
use Drupal\Core\Entity\Field\FieldInterface;
use Drupal\Core\Entity\Field\FieldItemInterface;
use Drupal\field\Tests\FieldUnitTestBase;

/**
 * Tests the new entity API for the taxonomy term reference field type.
 */
class TaxonomyTermReferenceItemTest extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy', 'options');

  public static function getInfo() {
    return array(
      'name' => 'Taxonomy reference field item',
      'description' => 'Tests using entity fields of the taxonomy term reference field type.',
      'group' => 'Taxonomy',
    );
  }

  public function setUp() {
    parent::setUp();
    $this->installSchema('taxonomy', 'taxonomy_term_data');
    $this->installSchema('taxonomy', 'taxonomy_term_hierarchy');

    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => $this->randomName(),
      'vid' => drupal_strtolower($this->randomName()),
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
    ));
    $vocabulary->save();

    entity_create('field_entity', array(
      'name' => 'field_test_taxonomy',
      'entity_type' => 'entity_test',
      'type' => 'taxonomy_term_reference',
      'cardinality' => FIELD_CARDINALITY_UNLIMITED,
      'settings' => array(
        'allowed_values' => array(
          array(
            'vocabulary' => $vocabulary->id(),
            'parent' => 0,
          ),
        ),
      ),
    ))->save();
    entity_create('field_instance', array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_test_taxonomy',
      'bundle' => 'entity_test',
    ))->save();
    $this->term = entity_create('taxonomy_term', array(
      'name' => $this->randomName(),
      'vid' => $vocabulary->id(),
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
    ));
    $this->term->save();
  }

  /**
   * Tests using entity fields of the taxonomy term reference field type.
   */
  public function testTaxonomyTermReferenceItem() {
    $tid = $this->term->id();
    // Just being able to create the entity like this verifies a lot of code.
    $entity = entity_create('entity_test', array());
    $entity->field_test_taxonomy->target_id = $this->term->id();
    $entity->name->value = $this->randomName();
    $entity->save();

    $entity = entity_load('entity_test', $entity->id());
    $this->assertTrue($entity->field_test_taxonomy instanceof FieldInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_test_taxonomy[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->field_test_taxonomy->target_id, $this->term->id());
    $this->assertEqual($entity->field_test_taxonomy->entity->name->value, $this->term->name->value);
    $this->assertEqual($entity->field_test_taxonomy->entity->id(), $tid);
    $this->assertEqual($entity->field_test_taxonomy->entity->uuid(), $this->term->uuid());

    // Change the name of the term via the reference.
    $new_name = $this->randomName();
    $entity->field_test_taxonomy->entity->name = $new_name;
    $entity->field_test_taxonomy->entity->save();
    // Verify it is the correct name.
    $term = entity_load('taxonomy_term', $tid);
    $this->assertEqual($term->name->value, $new_name);

    // Make sure the computed term reflects updates to the term id.
    $term2 = entity_create('taxonomy_term', array(
      'name' => $this->randomName(),
      'vid' => $this->term->vid->value,
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
    ));
    $term2->save();

    $entity->field_test_taxonomy->target_id = $term2->id();
    $this->assertEqual($entity->field_test_taxonomy->entity->id(), $term2->id());
    $this->assertEqual($entity->field_test_taxonomy->entity->name->value, $term2->name->value);
  }

}
