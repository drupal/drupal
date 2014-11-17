<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Tests\EntityReferenceFieldTest.
 */

namespace Drupal\entity_reference\Tests;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\system\Tests\Entity\EntityUnitTestBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Tests for the entity reference field.
 *
 * @group entity_reference
 */
class EntityReferenceFieldTest extends EntityUnitTestBase {
  use SchemaCheckTestTrait;

  /**
   * The entity type used in this test.
   *
   * @var string
   */
  protected $entityType = 'entity_test';

  /**
   * The entity type that is being referenced.
   *
   * @var string
   */
  protected $referencedEntityType = 'entity_test_rev';

  /**
   * The bundle used in this test.
   *
   * @var string
   */
  protected $bundle = 'entity_test';

  /**
   * The name of the field used in this test.
   *
   * @var string
   */
  protected $fieldName = 'field_test';

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('entity_reference');

  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_rev');

    // Create a field.
    entity_reference_create_field(
      $this->entityType,
      $this->bundle,
      $this->fieldName,
      'Field test',
      $this->referencedEntityType,
      'default',
      array('target_bundles' => array($this->bundle)),
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );

  }

  /**
   * Tests reference field validation.
   */
  public function testEntityReferenceFieldValidation() {
    // Test a valid reference.
    $referenced_entity = entity_create($this->referencedEntityType, array('type' => $this->bundle));
    $referenced_entity->save();

    $entity = entity_create($this->entityType, array('type' => $this->bundle));
    $entity->{$this->fieldName}->target_id = $referenced_entity->id();
    $violations = $entity->{$this->fieldName}->validate();
    $this->assertEqual($violations->count(), 0, 'Validation passes.');

    // Test an invalid reference.
    $entity->{$this->fieldName}->target_id = 9999;
    $violations = $entity->{$this->fieldName}->validate();
    $this->assertEqual($violations->count(), 1, 'Validation throws a violation.');
    $this->assertEqual($violations[0]->getMessage(), t('The referenced entity (%type: %id) does not exist.', array('%type' => $this->referencedEntityType, '%id' => 9999)));

    // @todo Implement a test case for invalid bundle references after
    // https://drupal.org/node/2064191 is fixed
  }

  /**
   * Tests the multiple target entities loader.
   */
  public function testReferencedEntitiesMultipleLoad() {
    // Create the parent entity.
    $entity = entity_create($this->entityType, array('type' => $this->bundle));

    // Create three target entities and attach them to parent field.
    $target_entities = array();
    $reference_field = array();
    for ($i = 0; $i < 3; $i++) {
      $target_entity = entity_create($this->referencedEntityType, array('type' => $this->bundle));
      $target_entity->save();
      $target_entities[] = $target_entity;
      $reference_field[]['target_id'] = $target_entity->id();
    }

    // Also attach a non-existent entity and a NULL target id.
    $reference_field[3]['target_id'] = 99999;
    $target_entities[3] = NULL;
    $reference_field[4]['target_id'] = NULL;
    $target_entities[4] = NULL;

    // Attach the first created target entity as the sixth item ($delta == 5) of
    // the parent entity field. We want to test the case when the same target
    // entity is referenced twice (or more times) in the same entity reference
    // field.
    $reference_field[5] = $reference_field[0];
    $target_entities[5] = $target_entities[0];

    // Create a new target entity that is not saved, thus testing the
    // "autocreate" feature.
    $target_entity_unsaved = entity_create($this->referencedEntityType, array('type' => $this->bundle, 'name' => $this->randomString()));
    $reference_field[6]['entity'] = $target_entity_unsaved;
    $target_entities[6] = $target_entity_unsaved;

    // Set the field value.
    $entity->{$this->fieldName}->setValue($reference_field);

    // Load the target entities using EntityReferenceField::referencedEntities().
    $entities = $entity->{$this->fieldName}->referencedEntities();

    // Test returned entities:
    // - Deltas must be preserved.
    // - Non-existent entities must not be retrieved in target entities result.
    foreach ($target_entities as $delta => $target_entity) {
      if (!empty($target_entity)) {
        if (!$target_entity->isNew()) {
          // There must be an entity in the loaded set having the same id for
          // the same delta.
          $this->assertEqual($target_entity->id(), $entities[$delta]->id());
        }
        else {
          // For entities that were not yet saved, there must an entity in the
          // loaded set having the same label for the same delta.
          $this->assertEqual($target_entity->label(), $entities[$delta]->label());
        }
      }
      else {
        // A non-existent or NULL entity target id must not return any item in
        // the target entities set.
        $this->assertFalse(isset($loaded_entities[$delta]));
      }
    }
  }

  /**
   * Tests referencing entities with string IDs.
   */
  public function testReferencedEntitiesStringId() {
    $field_name = 'entity_reference_string_id';
    $this->installEntitySchema('entity_test_string_id');
    entity_reference_create_field(
      $this->entityType,
      $this->bundle,
      $field_name,
      'Field test',
      'entity_test_string_id',
      'default',
      array('target_bundles' => array($this->bundle)),
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );
    // Create the parent entity.
    $entity = entity_create($this->entityType, array('type' => $this->bundle));

    // Create the default target entity.
    $target_entity = entity_create('entity_test_string_id', array('id' => $this->randomString(), 'type' => $this->bundle));
    $target_entity->save();

    // Set the field value.
    $entity->{$field_name}->setValue(array(array('target_id' => $target_entity->id())));

    // Load the target entities using EntityReferenceField::referencedEntities().
    $entities = $entity->{$field_name}->referencedEntities();
    $this->assertEqual($entities[0]->id(), $target_entity->id());

    // Test that a string ID works as a default value and the field's config
    // schema is correct.
    $field = FieldConfig::loadByName($this->entityType, $this->bundle, $field_name);
    $field->setDefaultValue($target_entity->id());
    $field->save();
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'field.field.' . $field->id(), $field->toArray());

    // Test that the default value works.
    $entity = entity_create($this->entityType, array('type' => $this->bundle));
    $entities = $entity->{$field_name}->referencedEntities();
    $this->assertEqual($entities[0]->id(), $target_entity->id());
  }

}
