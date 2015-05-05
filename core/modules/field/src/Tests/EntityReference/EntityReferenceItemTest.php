<?php

/**
 * @file
 * Contains \Drupal\field\Tests\EntityReference\EntityReferenceItemTest.
 */

namespace Drupal\field\Tests\EntityReference;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\entity_reference\Tests\EntityReferenceTestTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Tests\FieldUnitTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;


/**
 * Tests the new entity API for the entity reference field type.
 *
 * @group entity_reference
 */
class EntityReferenceItemTest extends FieldUnitTestBase {

  use EntityReferenceTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('entity_reference', 'taxonomy', 'text', 'filter', 'views');

  /**
   * The taxonomy vocabulary to test with.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * The taxonomy term to test with.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term;

  /**
   * Sets up the test.
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('taxonomy_term');

    $this->vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => $this->randomMachineName(),
      'vid' => Unicode::strtolower($this->randomMachineName()),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $this->vocabulary->save();

    $this->term = entity_create('taxonomy_term', array(
      'name' => $this->randomMachineName(),
      'vid' => $this->vocabulary->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $this->term->save();

    // Use the util to create an instance.
    $this->createEntityReferenceField('entity_test', 'entity_test', 'field_test_taxonomy_term', 'Test content entity reference', 'taxonomy_term');
    $this->createEntityReferenceField('entity_test', 'entity_test', 'field_test_taxonomy_vocabulary', 'Test config entity reference', 'taxonomy_vocabulary');
  }

  /**
   * Tests the entity reference field type for referencing content entities.
   */
  public function testContentEntityReferenceItem() {
    $tid = $this->term->id();

    // Just being able to create the entity like this verifies a lot of code.
    $entity = entity_create('entity_test');
    $entity->field_test_taxonomy_term->target_id = $tid;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    $entity = entity_load('entity_test', $entity->id());
    $this->assertTrue($entity->field_test_taxonomy_term instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_test_taxonomy_term[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->field_test_taxonomy_term->target_id, $tid);
    $this->assertEqual($entity->field_test_taxonomy_term->entity->getName(), $this->term->getName());
    $this->assertEqual($entity->field_test_taxonomy_term->entity->id(), $tid);
    $this->assertEqual($entity->field_test_taxonomy_term->entity->uuid(), $this->term->uuid());

    // Change the name of the term via the reference.
    $new_name = $this->randomMachineName();
    $entity->field_test_taxonomy_term->entity->setName($new_name);
    $entity->field_test_taxonomy_term->entity->save();
    // Verify it is the correct name.
    $term = Term::load($tid);
    $this->assertEqual($term->getName(), $new_name);

    // Make sure the computed term reflects updates to the term id.
    $term2 = entity_create('taxonomy_term', array(
      'name' => $this->randomMachineName(),
      'vid' => $this->term->bundle(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $term2->save();

    // Test all the possible ways of assigning a value.
    $entity->field_test_taxonomy_term->target_id = $term->id();
    $this->assertEqual($entity->field_test_taxonomy_term->entity->id(), $term->id());
    $this->assertEqual($entity->field_test_taxonomy_term->entity->getName(), $term->getName());

    $entity->field_test_taxonomy_term = [['target_id' => $term2->id()]];
    $this->assertEqual($entity->field_test_taxonomy_term->entity->id(), $term2->id());
    $this->assertEqual($entity->field_test_taxonomy_term->entity->getName(), $term2->getName());

    // Test value assignment via the computed 'entity' property.
    $entity->field_test_taxonomy_term->entity = $term;
    $this->assertEqual($entity->field_test_taxonomy_term->target_id, $term->id());
    $this->assertEqual($entity->field_test_taxonomy_term->entity->getName(), $term->getName());

    $entity->field_test_taxonomy_term = [['entity' => $term2]];
    $this->assertEqual($entity->field_test_taxonomy_term->target_id, $term2->id());
    $this->assertEqual($entity->field_test_taxonomy_term->entity->getName(), $term2->getName());

    // Test assigning an invalid item throws an exception.
    try {
      $entity->field_test_taxonomy_term = ['target_id' => 'invalid', 'entity' => $term2];
      $this->fail('Assigning an invalid item throws an exception.');
    }
    catch (\InvalidArgumentException $e) {
      $this->pass('Assigning an invalid item throws an exception.');
    }

    // Delete terms so we have nothing to reference and try again
    $term->delete();
    $term2->delete();
    $entity = entity_create('entity_test', array('name' => $this->randomMachineName()));
    $entity->save();

    // Test the generateSampleValue() method.
    $entity = entity_create('entity_test');
    $entity->field_test_taxonomy_term->generateSampleItems();
    $entity->field_test_taxonomy_vocabulary->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

  /**
   * Tests the entity reference field type for referencing config entities.
   */
  public function testConfigEntityReferenceItem() {
    $referenced_entity_id = $this->vocabulary->id();

    // Just being able to create the entity like this verifies a lot of code.
    $entity = entity_create('entity_test');
    $entity->field_test_taxonomy_vocabulary->target_id = $referenced_entity_id;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    $entity = entity_load('entity_test', $entity->id());
    $this->assertTrue($entity->field_test_taxonomy_vocabulary instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_test_taxonomy_vocabulary[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->field_test_taxonomy_vocabulary->target_id, $referenced_entity_id);
    $this->assertEqual($entity->field_test_taxonomy_vocabulary->entity->label(), $this->vocabulary->label());
    $this->assertEqual($entity->field_test_taxonomy_vocabulary->entity->id(), $referenced_entity_id);
    $this->assertEqual($entity->field_test_taxonomy_vocabulary->entity->uuid(), $this->vocabulary->uuid());

    // Change the name of the term via the reference.
    $new_name = $this->randomMachineName();
    $entity->field_test_taxonomy_vocabulary->entity->set('name', $new_name);
    $entity->field_test_taxonomy_vocabulary->entity->save();
    // Verify it is the correct name.
    $vocabulary = Vocabulary::load($referenced_entity_id);
    $this->assertEqual($vocabulary->label(), $new_name);

    // Make sure the computed term reflects updates to the term id.
    $vocabulary2 = entity_create('taxonomy_vocabulary', array(
      'name' => $this->randomMachineName(),
      'vid' => Unicode::strtolower($this->randomMachineName()),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $vocabulary2->save();

    $entity->field_test_taxonomy_vocabulary->target_id = $vocabulary2->id();
    $this->assertEqual($entity->field_test_taxonomy_vocabulary->entity->id(), $vocabulary2->id());
    $this->assertEqual($entity->field_test_taxonomy_vocabulary->entity->label(), $vocabulary2->label());

    // Delete terms so we have nothing to reference and try again
    $this->vocabulary->delete();
    $vocabulary2->delete();
    $entity = entity_create('entity_test', array('name' => $this->randomMachineName()));
    $entity->save();
  }

  /**
   * Test saving order sequence doesn't matter.
   */
  public function testEntitySaveOrder() {
    // The term entity is unsaved here.
    $term = entity_create('taxonomy_term', array(
      'name' => $this->randomMachineName(),
      'vid' => $this->term->bundle(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $entity = entity_create('entity_test');
    // Now assign the unsaved term to the field.
    $entity->field_test_taxonomy_term->entity = $term;
    $entity->name->value = $this->randomMachineName();
    // Now save the term.
    $term->save();
    // And then the entity.
    $entity->save();
    $this->assertEqual($entity->field_test_taxonomy_term->entity->id(), $term->id());
  }

  /**
   * Tests that the 'handler' field setting stores the proper plugin ID.
   */
  public function testSelectionHandlerSettings() {
    $field_name = Unicode::strtolower($this->randomMachineName());
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'entity_reference',
      'settings' => array(
        'target_type' => 'entity_test'
      ),
    ));
    $field_storage->save();

    // Do not specify any value for the 'handler' setting in order to verify
    // that the default value is properly used.
    $field = FieldConfig::create(array(
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ));
    $field->save();

    $field = FieldConfig::load($field->id());
    $this->assertTrue($field->getSetting('handler') == 'default:entity_test');

    $field->setSetting('handler', 'views');
    $field->save();
    $field = FieldConfig::load($field->id());
    $this->assertTrue($field->getSetting('handler') == 'views');
  }

}
