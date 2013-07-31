<?php

/**
 * @file
 * Contains \Drupal\image\Form\ImageEffectEditForm.
 */

namespace Drupal\image\Form;

use Drupal\image\ImageStyleInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an edit form for image effects.
 */
class ImageEffectEditForm extends ImageEffectFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, Request $request = NULL, ImageStyleInterface $image_style = NULL, $image_effect = NULL) {
    $form = parent::buildForm($form, $form_state, $request, $image_style, $image_effect);

    drupal_set_title(t('Edit %label effect', array('%label' => $this->imageEffect->label())), PASS_THROUGH);
    $form['actions']['submit']['#value'] = t('Update effect');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareImageEffect($image_effect) {
    return $this->imageStyle->getEffect($image_effect);
  }

}
