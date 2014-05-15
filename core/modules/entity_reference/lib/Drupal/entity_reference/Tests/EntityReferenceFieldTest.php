<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Tests\EntityReferenceFieldTest.
 */

namespace Drupal\entity_reference\Tests;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldInstanceConfig;
use Drupal\system\Tests\Entity\EntityUnitTestBase;

/**
 * Tests for the entity reference field.
 */
class EntityReferenceFieldTest extends EntityUnitTestBase {

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
   * A field array.
   *
   * @var array
   */
  protected $field;

  /**
   * An associative array of field instance data.
   *
   * @var array
   */
  protected $instance;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_reference');

  public static function getInfo() {
    return array(
      'name' => 'Entity Reference field',
      'description' => 'Tests the entity reference field.',
      'group' => 'Entity Reference',
    );
  }

  public function setUp() {
    parent::setUp();

    $this->installSchema('entity_test', array('entity_test_rev', 'entity_test_rev_revision'));

    // Setup a field and instance.
    entity_reference_create_instance(
      $this->entityType,
      $this->bundle,
      $this->fieldName,
      'Field test',
      $this->referencedEntityType,
      'default',
      array('target_bundles' => array($this->bundle))
    );

    $this->field = FieldConfig::loadByName($this->entityType, $this->fieldName);
    $this->instance = FieldInstanceConfig::loadByName($this->entityType, $this->bundle, $this->fieldName);
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

}
