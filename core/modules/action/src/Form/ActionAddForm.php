<?php

namespace Drupal\action\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for action add forms.
 *
 * @internal
 */
class ActionAddForm extends ActionFormBase {

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $action_id
   *   The action ID.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $action_id = NULL) {
    $this->entity->setPlugin($action_id);

    // Derive the label and type from the action definition.
    $definition = $this->entity->getPluginDefinition();
    $this->entity->set('label', $definition['label']);
    $this->entity->set('type', $definition['type']);

    return parent::buildForm($form, $form_state);
  }

}
