<?php

/**
 * @file
 * Contains \Drupal\field_test\Form\FieldTestForm.
 */

namespace Drupal\field_test\Form;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a form for field_test routes.
 */
class FieldTestForm {

  /**
   * @todo Remove field_test_entity_nested_form().
   */
  public function testEntityNestedForm(EntityInterface $entity_1, EntityInterface $entity_2) {
    module_load_include('entity.inc', 'field_test');
    return drupal_get_form('field_test_entity_nested_form', $entity_1, $entity_2);
  }

}
