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
      $values = $entity->menu;
      $violation_path = NULL;

      if (trim($values['title']) && !empty($values['menu_parent'])) {
        list($menu_name, $parent) = explode(':', $values['menu_parent'], 2);
        $values['menu_name'] = $menu_name;
        $values['parent'] = $parent;
      }

      // Handle the case when a menu link is added to a pending revision.
      if (!$defaults['entity_id'] && $values['enabled']) {
        $violation_path = 'menu';
      }
      // Handle the case when the menu link is deleted in a pending revision.
      elseif (empty($values['enabled']) && $defaults['entity_id']) {
        $violation_path = 'menu';
      }
      // Handle all the other menu link changes in a pending revision.
      elseif ($defaults['entity_id']) {
        if (($values['title'] != $defaults['title'])) {
          $violation_path = 'menu.title';
        }
        elseif (($values['description'] != $defaults['description'])) {
          $violation_path = 'menu.description';
        }
        elseif ($defaults['entity_id'] && ($values['menu_name'] != $defaults['menu_name'])) {
          $violation_path = 'menu.menu_parent';
        }
        elseif (isset($values['parent']) && ($values['parent'] != $defaults['parent'])) {
          $violation_path = 'menu.menu_parent';
        }
        elseif (($values['weight'] != $defaults['weight'])) {
          $violation_path = 'menu.weight';
        }
      }

      if ($violation_path) {
        $this->context->buildViolation($constraint->message)
          ->atPath($violation_path)
          ->setInvalidValue($entity)
          ->addViolation();
      }
    }
  }

}
