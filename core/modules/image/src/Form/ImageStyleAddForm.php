<?php

namespace Drupal\image\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Controller for image style addition forms.
 *
 * @internal
 */
class ImageStyleAddForm extends ImageStyleFormBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    drupal_set_message($this->t('Style %name was created.', ['%name' => $this->entity->label()]));
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Create new style');

    return $actions;
  }

}
