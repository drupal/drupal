<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\TaxonomyTermReferenceItemTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\field\Tests\FieldUnitTestBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Tests the new entity API for the taxonomy term reference field type.
 *
 * @group taxonomy
 */
class TaxonomyTermReferenceItemTest extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy', 'entity_reference', 'text', 'filter');

  /**
   * The term entity.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term;

  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term');

    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => $this->randomMachineName(),
      'vid' => Unicode::strtolower($this->randomMachineName()),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $vocabulary->save();

    entity_create('field_storage_config', array(
      'field_name' => 'field_test_taxonomy',
      'entity_type' => 'entity_test',
      'type' => 'taxonomy_term_reference',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => array(
        'allowed_values' => array(
          array(
            'vocabulary' => $vocabulary->id(),
            'parent' => 0,
          ),
        ),
      ),
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_test_taxonomy',
      'bundle' => 'entity_test',
    ))->save();
    $this->term = entity_create('taxonomy_term', array(
      'name' => $this->randomMachineName(),
      'vid' => $vocabulary->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $this->term->save();
  }

  /**
   * Tests using entity fields of the taxonomy term reference field type.
   */
  public function testTaxonomyTermReferenceItem() {
    $tid = $this->term->id();
    // Just being able to create the entity like this verifies a lot of code.
    $entity = entity_create('entity_test');
    $entity->field_test_taxonomy->target_id = $this->term->id();
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    $entity = entity_load('entity_test', $entity->id());
    $this->assertTrue($entity->field_test_taxonomy instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_test_taxonomy[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->field_test_taxonomy->target_id, $this->term->id(), 'Field item contains the expected TID.');
    $this->assertEqual($entity->field_test_taxonomy->entity->getName(), $this->term->getName(), 'Field item entity contains the expected name.');
    $this->assertEqual($entity->field_test_taxonomy->entity->id(), $tid, 'Field item entity contains the expected ID.');
    $this->assertEqual($entity->field_test_taxonomy->entity->uuid(), $this->term->uuid(), 'Field item entity contains the expected UUID.');

    // Change the name of the term via the reference.
    $new_name = $this->randomMachineName();
    $entity->field_test_taxonomy->entity->setName($new_name);
    $entity->field_test_taxonomy->entity->save();
    // Verify it is the correct name.
    $term = Term::load($tid);
    $this->assertEqual($term->getName(), $new_name, 'The name of the term was changed.');

    // Make sure the computed term reflects updates to the term id.
    $term2 = entity_create('taxonomy_term', array(
      'name' => $this->randomMachineName(),
      'vid' => $this->term->getVocabularyId(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $term2->save();

    $entity->field_test_taxonomy->target_id = $term2->id();
    $this->assertEqual($entity->field_test_taxonomy->entity->id(), $term2->id(), 'Field item entity contains the new TID.');
    $this->assertEqual($entity->field_test_taxonomy->entity->getName(), $term2->getName(), 'Field item entity contains the new name.');

    // Test sample item generation.
    $entity = entity_create('entity_test');
    $entity->field_test_taxonomy->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

}
