<?php

/**
 * @file
 * Contains Drupal\system\Tests\Entity\FieldWidgetConstraintValidatorTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Core\Form\FormState;
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

    $this->installSchema('system', 'router');
    $this->container->get('router.builder')->rebuild();

    $this->installEntitySchema('user');
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

}
