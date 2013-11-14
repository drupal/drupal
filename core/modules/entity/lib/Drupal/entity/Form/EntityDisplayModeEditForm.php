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
    $entity_type = $this->entity->entityType();
    $form_state['redirect_route'] = array(
      'route_name' => 'entity.' . $entity_type . '_delete',
      'route_parameters' => array(
        $entity_type => $this->entity->id(),
      ),
    );
  }

}
