<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\FieldWidgetConstraintValidatorTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormState;
use Drupal\entity_test\Entity\EntityTestCompositeConstraint;
use Drupal\simpletest\KernelTestBase;
use Drupal\system\Tests\TypedData;

/**
 * Tests validation constraints for FieldWidgetConstraintValidatorTest.
 *
 * @group Entity
 */
class FieldWidgetConstraintValidatorTest extends KernelTestBase {

  public static $modules = array('entity_test', 'field', 'user', 'system');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['router', 'key_value']);
    $this->container->get('router.builder')->rebuild();

    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test_composite_constraint');
  }

  /**
   * Tests widget constraint validation.
   */
  public function testValidation() {
    $entity_type = 'entity_test_constraint_violation';
    $entity = entity_create($entity_type, array('id' => 1, 'revision_id' => 1));
    $display = entity_get_form_display($entity_type, $entity_type, 'default');
    $form = array();
    $form_state = new FormState();
    $display->buildForm($entity, $form, $form_state);

    // Pretend the form has been built.
    $form_state->setFormObject(\Drupal::entityManager()->getFormObject($entity_type, 'default'));
    \Drupal::formBuilder()->prepareForm('field_test_entity_form', $form, $form_state);
    \Drupal::formBuilder()->processForm('field_test_entity_form', $form, $form_state);

    // Validate the field constraint.
    $form_state->getFormObject()->setEntity($entity)->setFormDisplay($display, $form_state);
    $entity = $form_state->getFormObject()->buildEntity($form, $form_state);
    $display->validateFormValues($entity, $form, $form_state);

    $errors = $form_state->getErrors();
    $this->assertEqual($errors['name'], 'Widget constraint has failed.', 'Constraint violation is generated correctly');
  }

  /**
   * Gets the form errors for a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity
   * @param array $hidden_fields
   *   (optional) A list of hidden fields.
   *
   * @return array
   *   The form errors.
   */
  protected function getErrorsForEntity(EntityInterface $entity, $hidden_fields = []) {
    $entity_type_id = 'entity_test_composite_constraint';
    $display = entity_get_form_display($entity_type_id, $entity_type_id, 'default');

    foreach ($hidden_fields as $hidden_field) {
      $display->removeComponent($hidden_field);
    }

    $form = [];
    $form_state = new FormState();
    $display->buildForm($entity, $form, $form_state);

    $form_state->setFormObject(\Drupal::entityManager()->getFormObject($entity_type_id, 'default'));
    \Drupal::formBuilder()->prepareForm('field_test_entity_form', $form, $form_state);
    \Drupal::formBuilder()->processForm('field_test_entity_form', $form, $form_state);

    // Validate the field constraint.
    $form_state->getFormObject()->setEntity($entity)->setFormDisplay($display, $form_state);
    $form_state->getFormObject()->validate($form, $form_state);

    return $form_state->getErrors();
  }

  /**
   * Tests widget constraint validation with composite constraints.
   */
  public function testValidationWithCompositeConstraint() {
    // First provide a valid value, this should cause no validation.
    $entity = EntityTestCompositeConstraint::create([
      'name' => 'valid-value',
    ]);
    $entity->save();

    $errors = $this->getErrorsForEntity($entity);
    $this->assertFalse(isset($errors['name']));
    $this->assertFalse(isset($errors['type']));

    // Provide an invalid value for the name field.
    $entity = EntityTestCompositeConstraint::create([
      'name' => 'failure-field-name',
    ]);
    $errors = $this->getErrorsForEntity($entity);
    $this->assertTrue(isset($errors['name']));
    $this->assertFalse(isset($errors['type']));

    // Hide the second field (type) and ensure the validation still happens. The
    // error message appears on the first field (name).
    $entity = EntityTestCompositeConstraint::create([
      'name' => 'failure-field-name',
    ]);
    $errors = $this->getErrorsForEntity($entity, ['type']);
    $this->assertTrue(isset($errors['name']));
    $this->assertFalse(isset($errors['type']));

    // Provide a violation again, but this time hide the first field (name).
    // Ensure that the validation still happens and the error message is moved
    // from the field to the second field and have a custom error message.
    $entity = EntityTestCompositeConstraint::create([
      'name' => 'failure-field-name',
    ]);
    $errors = $this->getErrorsForEntity($entity, ['name']);
    $this->assertFalse(isset($errors['name']));
    $this->assertTrue(isset($errors['type']));
    $this->assertEqual($errors['type'], SafeMarkup::format('The validation failed because the value conflicts with the value in %field_name, which you cannot access.', ['%field_name' => 'name']));
  }

  /**
   * Tests entity level constraint validation.
   */
  public function testEntityLevelConstraintValidation() {
    $entity = EntityTestCompositeConstraint::create([
      'name' => 'entity-level-violation'
    ]);
    $entity->save();

    $errors = $this->getErrorsForEntity($entity);
    $this->assertEqual($errors[''], 'Entity level validation');
  }

}
