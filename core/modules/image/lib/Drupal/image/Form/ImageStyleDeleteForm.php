<?php

/**
 * @file
 * Contains \Drupal\image\Form\ImageStyleDeleteForm.
 */

namespace Drupal\image\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\image\Plugin\Core\Entity\ImageStyle;

/**
 * Creates a form to delete an image style.
 */
class ImageStyleDeleteForm extends ConfirmFormBase {

  /**
   * The image style to be deleted.
   *
   * @var \Drupal\image\Plugin\Core\Entity\ImageStyle $imageStyle
   */
  protected $imageStyle;

  /**
   * {@inheritdoc}
   */
  protected function getQuestion() {
    return t('Optionally select a style before deleting %style', array('%style' => $this->imageStyle->label()));
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  protected function getCancelPath() {
    return 'admin/config/media/image-styles';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'image_style_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDescription() {
    return t('If this style is in use on the site, you may select another style to replace it. All images that have been generated for this style will be permanently deleted.');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, ImageStyle $image_style = NULL) {

    $this->imageStyle = $image_style;

    $replacement_styles = array_diff_key(image_style_options(), array($this->imageStyle->id() => ''));
    $form['replacement'] = array(
      '#title' => t('Replacement style'),
      '#type' => 'select',
      '#options' => $replacement_styles,
      '#empty_option' => t('No replacement, just delete'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->imageStyle->set('replacementID', $form_state['values']['replacement']);
    $this->imageStyle->delete();
    drupal_set_message(t('Style %name was deleted.', array('%name' => $this->imageStyle->label())));
    $form_state['redirect'] = 'admin/config/media/image-styles';
  }

}
