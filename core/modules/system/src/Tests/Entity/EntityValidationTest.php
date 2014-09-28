<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityValidationTest.
 */

namespace Drupal\system\Tests\Entity;

/**
 * Tests the Entity Validation API.
 *
 * @group Entity
 */
class EntityValidationTest extends EntityUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('filter', 'text');

  /**
   * @var string
   */
  protected $entity_name;

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $entity_user;

  /**
   * @var string
   */
  protected $entity_field_text;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_rev');
    $this->installEntitySchema('entity_test_mul');
    $this->installEntitySchema('entity_test_mulrev');

    // Create the test field.
    entity_test_install();

    // Install required default configuration for filter module.
    $this->installConfig(array('system', 'filter'));
  }

  /**
   * Creates a test entity.
   *
   * @param string $entity_type
   *   An entity type.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created test entity.
   */
  protected function createTestEntity($entity_type) {
    $this->entity_name = $this->randomMachineName();
    $this->entity_user = $this->createUser();
    $this->entity_field_text = $this->randomMachineName();

    // Pass in the value of the name field when creating. With the user
    // field we test setting a field after creation.
    $entity = entity_create($entity_type);
    $entity->user_id->target_id = $this->entity_user->id();
    $entity->name->value = $this->entity_name;

    // Set a value for the test field.
    $entity->field_test_text->value = $this->entity_field_text;

    return $entity;
  }

  /**
   * Tests validating test entity types.
   */
  public function testValidation() {
    // All entity variations have to have the same results.
    foreach (entity_test_entity_types() as $entity_type) {
      $this->checkValidation($entity_type);
    }
  }

  /**
   * Executes the validation test set for a defined entity type.
   *
   * @param string $entity_type
   *   The entity type to run the tests with.
   */
  protected function checkValidation($entity_type) {
    $entity = $this->createTestEntity($entity_type);
    $violations = $entity->validate();
    $this->assertEqual($violations->count(), 0, 'Validation passes.');

    // Test triggering a fail for each of the constraints specified.
    $test_entity = clone $entity;
    $test_entity->id->value = -1;
    $violations = $test_entity->validate();
    $this->assertEqual($violations->count(), 1, 'Validation failed.');
    $this->assertEqual($violations[0]->getMessage(), t('%name: The integer must be larger or equal to %min.', array('%name' => 'ID', '%min' => 0)));

    $test_entity = clone $entity;
    $test_entity->uuid->value = $this->randomString(129);
    $violations = $test_entity->validate();
    $this->assertEqual($violations->count(), 1, 'Validation failed.');
    $this->assertEqual($violations[0]->getMessage(), t('%name: may not be longer than @max characters.', array('%name' => 'UUID', '@max' => 128)));

    $test_entity = clone $entity;
    $test_entity->langcode->value = $this->randomString(13);
    $violations = $test_entity->validate();
    $this->assertEqual($violations->count(), 1, 'Validation failed.');
    $this->assertEqual($violations[0]->getMessage(), t('This value is too long. It should have %limit characters or less.', array('%limit' => '12')));

    $test_entity = clone $entity;
    $test_entity->type->value = NULL;
    $violations = $test_entity->validate();
    $this->assertEqual($violations->count(), 1, 'Validation failed.');
    $this->assertEqual($violations[0]->getMessage(), t('This value should not be null.'));

    $test_entity = clone $entity;
    $test_entity->name->value = $this->randomString(33);
    $violations = $test_entity->validate();
    $this->assertEqual($violations->count(), 1, 'Validation failed.');
    $this->assertEqual($violations[0]->getMessage(), t('%name: may not be longer than @max characters.', array('%name' => 'Name', '@max' => 32)));

    // Make sure the information provided by a violation is correct.
    $violation = $violations[0];
    $this->assertEqual($violation->getRoot()->getValue(), $test_entity, 'Violation root is entity.');
    $this->assertEqual($violation->getPropertyPath(), 'name.0.value', 'Violation property path is correct.');
    $this->assertEqual($violation->getInvalidValue(), $test_entity->name->value, 'Violation contains invalid value.');

    $test_entity = clone $entity;
    $test_entity->set('user_id', 9999);
    $violations = $test_entity->validate();
    $this->assertEqual($violations->count(), 1, 'Validation failed.');
    $this->assertEqual($violations[0]->getMessage(), t('The referenced entity (%type: %id) does not exist.', array('%type' => 'user', '%id' => 9999)));

    $test_entity = clone $entity;
    $test_entity->field_test_text->format = $this->randomString(33);
    $violations = $test_entity->validate();
    $this->assertEqual($violations->count(), 1, 'Validation failed.');
    $this->assertEqual($violations[0]->getMessage(), t('The value you selected is not a valid choice.'));

    // Make sure the information provided by a violation is correct.
    $violation = $violations[0];
    $this->assertEqual($violation->getRoot()->getValue(), $test_entity, 'Violation root is entity.');
    $this->assertEqual($violation->getPropertyPath(), 'field_test_text.0.format', 'Violation property path is correct.');
    $this->assertEqual($violation->getInvalidValue(), $test_entity->field_test_text->format, 'Violation contains invalid value.');
  }

}
