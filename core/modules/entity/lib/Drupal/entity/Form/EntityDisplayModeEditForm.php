<?php

/**
 * @file
 * Contains \Drupal\entity\Form\EntityDisplayModeEditForm.
 */

namespace Drupal\entity\Form;

/**
 * Provides the edit form for entity display modes.
 */
class EntityDisplayModeEditForm extends EntityDisplayModeFormBase {

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, array &$form_state) {
    $short_type = str_replace('_mode', '', $this->entity->entityType());
    $form_state['redirect'] = "admin/structure/display-modes/$short_type/manage/" . $this->entity->id() . '/delete';
  }

}
