<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\ValidReferenceConstraintValidatorTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\system\Tests\TypedData;

/**
 * Tests validation constraints for ValidReferenceConstraintValidator.
 *
 * @group Validation
 */
class ValidReferenceConstraintValidatorTest extends EntityUnitTestBase {

  /**
   * The typed data manager to use.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedData;

  /**
   * {@inheritdoc}
   */
  public static $modules = array('field', 'user');

  /**
   * @inheritdoc
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('user', array('users_data'));
    $this->typedData = $this->container->get('typed_data_manager');
  }

  /**
   * Tests the ValidReferenceConstraintValidator.
   */
  public function testValidation() {
    // Create a test entity to be referenced.
    $entity = $this->createUser();
    // By default entity references already have the ValidReference constraint.
    $definition = BaseFieldDefinition::create('entity_reference')
      ->setSettings(array('target_type' => 'user'));

    $typed_data = $this->typedData->create($definition, array('target_id' => $entity->id()));
    $violations = $typed_data->validate();
    $this->assertFalse($violations->count(), 'Validation passed for correct value.');

    // NULL is also considered a valid reference.
    $typed_data = $this->typedData->create($definition, array('target_id' => NULL));
    $violations = $typed_data->validate();
    $this->assertFalse($violations->count(), 'Validation passed for correct value.');

    $typed_data = $this->typedData->create($definition, array('target_id' => $entity->id()));
    // Delete the referenced entity.
    $entity->delete();
    $violations = $typed_data->validate();
    $this->assertTrue($violations->count(), 'Validation failed for incorrect value.');

    // Make sure the information provided by a violation is correct.
    $violation = $violations[0];
    $this->assertEqual($violation->getMessage(), t('The referenced entity (%type: %id) does not exist.', array(
      '%type' => 'user',
      '%id' => $entity->id(),
    )), 'The message for invalid value is correct.');
    $this->assertEqual($violation->getRoot(), $typed_data, 'Violation root is correct.');
  }

}
