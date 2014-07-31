<?php

/**
 * @file
 * Contains \Drupal\image\Form\ImageEffectEditForm.
 */

namespace Drupal\image\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\image\ImageStyleInterface;

/**
 * Provides an edit form for image effects.
 */
class ImageEffectEditForm extends ImageEffectFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ImageStyleInterface $image_style = NULL, $image_effect = NULL) {
    $form = parent::buildForm($form, $form_state, $image_style, $image_effect);

    $form['#title'] = $this->t('Edit %label effect', array('%label' => $this->imageEffect->label()));
    $form['actions']['submit']['#value'] = $this->t('Update effect');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareImageEffect($image_effect) {
    return $this->imageStyle->getEffect($image_effect);
  }

}
