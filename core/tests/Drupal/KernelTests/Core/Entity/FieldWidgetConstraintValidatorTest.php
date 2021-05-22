<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormState;
use Drupal\entity_test\Entity\EntityTestCompositeConstraint;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests validation constraints for FieldWidgetConstraintValidatorTest.
 *
 * @group Entity
 */
class FieldWidgetConstraintValidatorTest extends KernelTestBase {

  protected static $modules = [
    'entity_test',
    'field',
    'field_test',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test_composite_constraint');
  }

  /**
   * Tests widget constraint validation.
   */
  public function testValidation() {
    $entity_type = 'entity_test_constraint_violation';
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create(['id' => 1, 'revision_id' => 1]);
    $display = \Drupal::service('entity_display.repository')
      ->getFormDisplay($entity_type, $entity_type);
    $form = [];
    $form_state = new FormState();
    $display->buildForm($entity, $form, $form_state);

    // Pretend the form has been built.
    $form_state->setFormObject(\Drupal::entityTypeManager()->getFormObject($entity_type, 'default'));
    \Drupal::formBuilder()->prepareForm('field_test_entity_form', $form, $form_state);
    \Drupal::formBuilder()->processForm('field_test_entity_form', $form, $form_state);

    // Validate the field constraint.
    $form_state->getFormObject()->setEntity($entity)->setFormDisplay($display, $form_state);
    $entity = $form_state->getFormObject()->buildEntity($form, $form_state);
    $display->validateFormValues($entity, $form, $form_state);

    $errors = $form_state->getErrors();
    $this->assertEquals('Widget constraint has failed.', $errors['name'], 'Constraint violation at the field items list level is generated correctly');
    $this->assertEquals('Widget constraint has failed.', $errors['test_field'], 'Constraint violation at the field items list level is generated correctly for an advanced widget');
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
    $display = \Drupal::service('entity_display.repository')
      ->getFormDisplay($entity_type_id, $entity_type_id);

    foreach ($hidden_fields as $hidden_field) {
      $display->removeComponent($hidden_field);
    }

    $form = [];
    $form_state = new FormState();
    $display->buildForm($entity, $form, $form_state);

    $form_state->setFormObject(\Drupal::entityTypeManager()->getFormObject($entity_type_id, 'default'));
    \Drupal::formBuilder()->prepareForm('field_test_entity_form', $form, $form_state);
    \Drupal::formBuilder()->processForm('field_test_entity_form', $form, $form_state);

    // Validate the field constraint.
    /** @var \Drupal\Core\Entity\ContentEntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    $form_object
      ->setEntity($entity)
      ->setFormDisplay($display, $form_state)
      ->validateForm($form, $form_state);

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
    $this->assertEquals(new FormattableMarkup('The validation failed because the value conflicts with the value in %field_name, which you cannot access.', ['%field_name' => 'name']), $errors['type']);
  }

  /**
   * Tests entity level constraint validation.
   */
  public function testEntityLevelConstraintValidation() {
    $entity = EntityTestCompositeConstraint::create([
      'name' => 'entity-level-violation',
    ]);
    $entity->save();

    $errors = $this->getErrorsForEntity($entity);
    $this->assertEquals('Entity level validation', $errors['']);

    $entity->name->value = 'entity-level-violation-with-path';
    $errors = $this->getErrorsForEntity($entity);
    $this->assertEquals('Entity level validation', $errors['test][form][element']);
  }

}
