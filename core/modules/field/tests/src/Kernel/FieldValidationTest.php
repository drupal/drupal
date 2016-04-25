<?php

namespace Drupal\Tests\field\Kernel;

/**
 * Tests field validation.
 *
 * @group field
 */
class FieldValidationTest extends FieldKernelTestBase {

  /**
   * @var string
   */
  private $entityType;

  /**
   * @var string
   */
  private $bundle;

  /**
   * @var \Drupal\Core\Entity\EntityInterface
   */
  private $entity;

  protected function setUp() {
    parent::setUp();

    // Create a field and storage of type 'test_field', on the 'entity_test'
    // entity type.
    $this->entityType = 'entity_test';
    $this->bundle = 'entity_test';
    $this->createFieldWithStorage('', $this->entityType, $this->bundle);

    // Create an 'entity_test' entity.
    $this->entity = entity_create($this->entityType, array(
      'type' => $this->bundle,
    ));
  }

  /**
   * Tests that the number of values is validated against the field cardinality.
   */
  function testCardinalityConstraint() {
    $cardinality = $this->fieldTestData->field_storage->getCardinality();
    $entity = $this->entity;

    for ($delta = 0; $delta < $cardinality + 1; $delta++) {
      $entity->{$this->fieldTestData->field_name}[] = array('value' => 1);
    }

    // Validate the field.
    $violations = $entity->{$this->fieldTestData->field_name}->validate();

    // Check that the expected constraint violations are reported.
    $this->assertEqual(count($violations), 1);
    $this->assertEqual($violations[0]->getPropertyPath(), '');
    $this->assertEqual($violations[0]->getMessage(), t('%name: this field cannot hold more than @count values.', array('%name' => $this->fieldTestData->field->getLabel(), '@count' => $cardinality)));
  }

  /**
   * Tests that constraints defined by the field type are validated.
   */
  function testFieldConstraints() {
    $cardinality = $this->fieldTestData->field_storage->getCardinality();
    $entity = $this->entity;

    // The test is only valid if the field cardinality is greater than 2.
    $this->assertTrue($cardinality >= 2);

    // Set up values for the field.
    $expected_violations = array();
    for ($delta = 0; $delta < $cardinality; $delta++) {
      // All deltas except '1' have incorrect values.
      if ($delta == 1) {
        $value = 1;
      }
      else {
        $value = -1;
        $expected_violations[$delta . '.value'][] = t('%name does not accept the value -1.', array('%name' => $this->fieldTestData->field->getLabel()));
      }
      $entity->{$this->fieldTestData->field_name}[] = $value;
    }

    // Validate the field.
    $violations = $entity->{$this->fieldTestData->field_name}->validate();

    // Check that the expected constraint violations are reported.
    $violations_by_path = array();
    foreach ($violations as $violation) {
      $violations_by_path[$violation->getPropertyPath()][] = $violation->getMessage();
    }
    $this->assertEqual($violations_by_path, $expected_violations);
  }

}
