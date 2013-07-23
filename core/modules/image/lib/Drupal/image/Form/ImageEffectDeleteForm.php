<?php

/**
 * @file
 * Contains \Drupal\image\Form\ImageEffectDeleteForm.
 */

namespace Drupal\image\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\image\ImageStyleInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Form for deleting an image effect.
 */
class ImageEffectDeleteForm extends ConfirmFormBase {

  /**
   * The image style containing the image effect to be deleted.
   *
   * @var \Drupal\image\ImageStyleInterface
   */
  protected $imageStyle;

  /**
   * The image effect to be deleted.
   *
   * @var \Drupal\image\ImageEffectInterface
   */
  protected $imageEffect;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the @effect effect from the %style style?', array('%style' => $this->imageStyle->label(), '@effect' => $this->imageEffect->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelPath() {
    return 'admin/config/media/image-styles/manage/' . $this->imageStyle->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'image_effect_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, ImageStyleInterface $image_style = NULL, $image_effect = NULL, Request $request = NULL) {
    $this->imageStyle = $image_style;
    $this->imageEffect = $this->imageStyle->getEffect($image_effect);

    return parent::buildForm($form, $form_state, $request);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->imageStyle->deleteImageEffect($this->imageEffect);
    drupal_set_message(t('The image effect %name has been deleted.', array('%name' => $this->imageEffect->label())));
    $form_state['redirect'] = 'admin/config/media/image-styles/manage/' . $this->imageStyle->id();
  }

}
