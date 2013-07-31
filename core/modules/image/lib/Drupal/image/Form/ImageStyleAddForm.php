<?php

/**
 * @file
 * Contains \Drupal\image\Form\ImageStyleAddForm.
 */

namespace Drupal\image\Form;

use Drupal\image\Form\ImageStyleFormBase;

/**
 * Controller for image style addition forms.
 */
class ImageStyleAddForm extends ImageStyleFormBase {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    parent::save($form, $form_state);
    drupal_set_message($this->translator->translate('Style %name was created.', array('%name' => $this->entity->label())));
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->translator->translate('Create new style');

    return $actions;
  }

}
