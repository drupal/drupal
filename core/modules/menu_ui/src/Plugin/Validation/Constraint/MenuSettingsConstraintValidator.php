<?php

namespace Drupal\menu_ui\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Constraint validator for changing the menu settings in pending revisions.
 */
class MenuSettingsConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (isset($entity) && !$entity->isNew() && !$entity->isDefaultRevision()) {
      $defaults = menu_ui_get_menu_link_defaults($entity);

      // If the menu UI entity builder is not present and the menu property has
      // not been set, do not attempt to validate the menu settings since they
      // are not being modified.
      if (!$values = $entity->menu) {
        return;
      }

      if (trim($values['title']) && !empty($values['menu_parent'])) {
        list($menu_name, $parent) = explode(':', $values['menu_parent'], 2);
        $values['menu_name'] = $menu_name;
        $values['parent'] = $parent;
      }

      // Handle the case when the menu link is deleted in a pending revision.
      if (empty($values['enabled']) && $defaults['entity_id']) {
        $this->context->buildViolation($constraint->messageRemove)
          ->atPath('menu')
          ->setInvalidValue($entity)
          ->addViolation();
      }
      // Handle all the other non-revisionable menu link changes in a pending
      // revision.
      elseif ($defaults['entity_id']) {
        if ($defaults['entity_id'] && ($values['menu_name'] != $defaults['menu_name'])) {
          $this->context->buildViolation($constraint->messageParent)
            ->atPath('menu.menu_parent')
            ->setInvalidValue($entity)
            ->addViolation();
        }
        elseif (isset($values['parent']) && ($values['parent'] != $defaults['parent'])) {
          $this->context->buildViolation($constraint->messageParent)
            ->atPath('menu.menu_parent')
            ->setInvalidValue($entity)
            ->addViolation();
        }
        elseif (($values['weight'] != $defaults['weight'])) {
          $this->context->buildViolation($constraint->messageWeight)
            ->atPath('menu.weight')
            ->setInvalidValue($entity)
            ->addViolation();
        }
      }
    }
  }

}
