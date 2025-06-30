<?php

declare(strict_types=1);

namespace Drupal\user_form_test\Hook;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for user_form_test.
 */
class UserFormTestHooks {

  /**
   * Implements hook_form_FORM_ID_alter() for user_cancel_form().
   */
  #[Hook('form_user_cancel_form_alter')]
  public function formUserCancelFormAlter(&$form, &$form_state) : void {
    $form['user_cancel_confirm']['#default_value'] = FALSE;
    $form['access']['#value'] = \Drupal::currentUser()->hasPermission('cancel other accounts');
  }

  /**
   * Implements hook_entity_base_field_info_alter().
   */
  #[Hook('entity_base_field_info_alter')]
  public function entityBaseFieldInfoAlter(&$fields, EntityTypeInterface $entity_type): void {
    if ($entity_type->id() === 'user' && \Drupal::keyvalue('user_form_test')->get('user_form_test_constraint_roles_edit')) {
      $fields['roles']->addConstraint('FieldWidgetConstraint');
    }
  }

}
