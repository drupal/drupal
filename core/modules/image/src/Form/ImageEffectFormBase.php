<?php

/**
 * @file
 * Contains \Drupal\image\Form\ImageEffectFormBase.
 */

namespace Drupal\image\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\ConfigurableImageEffectInterface;
use Drupal\image\ImageStyleInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\String;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a base form for image effects.
 */
abstract class ImageEffectFormBase extends FormBase {

  /**
   * The image style.
   *
   * @var \Drupal\image\ImageStyleInterface
   */
  protected $imageStyle;

  /**
   * The image effect.
   *
   * @var \Drupal\image\ImageEffectInterface
   */
  protected $imageEffect;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'image_effect_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\image\ImageStyleInterface $image_style
   *   The image style.
   * @param string $image_effect
   *   The image effect ID.
   *
   * @return array
   *   The form structure.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function buildForm(array $form, FormStateInterface $form_state, ImageStyleInterface $image_style = NULL, $image_effect = NULL) {
    $this->imageStyle = $image_style;
    try {
      $this->imageEffect = $this->prepareImageEffect($image_effect);
    }
    catch (PluginNotFoundException $e) {
      throw new NotFoundHttpException(String::format("Invalid effect id: '@id'.", array('@id' => $image_effect)));
    }
    $request = $this->getRequest();

    if (!($this->imageEffect instanceof ConfigurableImageEffectInterface)) {
      throw new NotFoundHttpException();
    }

    $form['#attached']['library'][] = 'image/admin';
    $form['uuid'] = array(
      '#type' => 'value',
      '#value' => $this->imageEffect->getUuid(),
    );
    $form['id'] = array(
      '#type' => 'value',
      '#value' => $this->imageEffect->getPluginId(),
    );

    $form['data'] = $this->imageEffect->buildConfigurationForm(array(), $form_state);
    $form['data']['#tree'] = TRUE;

    // Check the URL for a weight, then the image effect, otherwise use default.
    $form['weight'] = array(
      '#type' => 'hidden',
      '#value' => $request->query->has('weight') ? (int) $request->query->get('weight') : $this->imageEffect->getWeight(),
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
    );
    $form['actions']['cancel'] = array(
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => $this->imageStyle->urlInfo('edit-form'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // The image effect configuration is stored in the 'data' key in the form,
    // pass that through for validation.
    $effect_data = (new FormState())->setValues($form_state->getValue('data'));
    $this->imageEffect->validateConfigurationForm($form, $effect_data);
    // Update the original form values.
    $form_state->setValue('data', $effect_data->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();

    // The image effect configuration is stored in the 'data' key in the form,
    // pass that through for submission.
    $effect_data = (new FormState())->setValues($form_state->getValue('data'));
    $this->imageEffect->submitConfigurationForm($form, $effect_data);
    // Update the original form values.
    $form_state->setValue('data', $effect_data->getValues());

    $this->imageEffect->setWeight($form_state->getValue('weight'));
    if (!$this->imageEffect->getUuid()) {
      $this->imageStyle->addImageEffect($this->imageEffect->getConfiguration());
    }
    $this->imageStyle->save();

    drupal_set_message($this->t('The image effect was successfully applied.'));
    $form_state->setRedirectUrl($this->imageStyle->urlInfo('edit-form'));
  }

  /**
   * Converts an image effect ID into an object.
   *
   * @param string $image_effect
   *   The image effect ID.
   *
   * @return \Drupal\image\ImageEffectInterface
   *   The image effect object.
   */
  abstract protected function prepareImageEffect($image_effect);

}
