<?php

namespace Drupal\image\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\image\ConfigurableImageEffectInterface;
use Drupal\image\ImageStyleInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
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
   * @var \Drupal\image\ImageEffectInterface|\Drupal\image\ConfigurableImageEffectInterface
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
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
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
  public function buildForm(array $form, FormStateInterface $form_state, ?ImageStyleInterface $image_style = NULL, $image_effect = NULL) {
    $this->imageStyle = $image_style;
    try {
      $this->imageEffect = $this->prepareImageEffect($image_effect);
    }
    catch (PluginNotFoundException $e) {
      throw new NotFoundHttpException("Invalid effect id: '$image_effect'.");
    }
    $request = $this->getRequest();

    if (!($this->imageEffect instanceof ConfigurableImageEffectInterface)) {
      throw new NotFoundHttpException();
    }

    $form['#attached']['library'][] = 'image/admin';
    $form['uuid'] = [
      '#type' => 'value',
      '#value' => $this->imageEffect->getUuid(),
    ];
    $form['id'] = [
      '#type' => 'value',
      '#value' => $this->imageEffect->getPluginId(),
    ];

    $form['data'] = [];
    $subform_state = SubformState::createForSubform($form['data'], $form, $form_state);
    $form['data'] = $this->imageEffect->buildConfigurationForm($form['data'], $subform_state);
    $form['data']['#tree'] = TRUE;

    // Check the URL for a weight, then the image effect, otherwise use default.
    $form['weight'] = [
      '#type' => 'hidden',
      '#value' => $request->query->has('weight') ? (int) $request->query->get('weight') : $this->imageEffect->getWeight(),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => $this->imageStyle->toUrl('edit-form'),
      '#attributes' => ['class' => ['button']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // The image effect configuration is stored in the 'data' key in the form,
    // pass that through for validation.
    $this->imageEffect->validateConfigurationForm($form['data'], SubformState::createForSubform($form['data'], $form, $form_state));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();

    // The image effect configuration is stored in the 'data' key in the form,
    // pass that through for submission.
    $this->imageEffect->submitConfigurationForm($form['data'], SubformState::createForSubform($form['data'], $form, $form_state));

    $this->imageEffect->setWeight($form_state->getValue('weight'));
    if (!$this->imageEffect->getUuid()) {
      $this->imageStyle->addImageEffect($this->imageEffect->getConfiguration());
    }
    $this->imageStyle->save();

    $this->messenger()->addStatus($this->t('The image effect was successfully applied.'));
    $form_state->setRedirectUrl($this->imageStyle->toUrl('edit-form'));
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
