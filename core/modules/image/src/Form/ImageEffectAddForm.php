<?php

namespace Drupal\image\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\image\ImageEffectManager;
use Drupal\image\ImageStyleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an add form for image effects.
 *
 * @internal
 */
class ImageEffectAddForm extends ImageEffectFormBase {

  /**
   * The image effect manager.
   *
   * @var \Drupal\image\ImageEffectManager
   */
  protected $effectManager;

  /**
   * Constructs a new ImageEffectAddForm.
   *
   * @param \Drupal\image\ImageEffectManager $effect_manager
   *   The image effect manager.
   */
  public function __construct(ImageEffectManager $effect_manager) {
    $this->effectManager = $effect_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.image.effect')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ImageStyleInterface $image_style = NULL, $image_effect = NULL) {
    $form = parent::buildForm($form, $form_state, $image_style, $image_effect);

    $form['#title'] = $this->t('Add %label effect to style %style', [
      '%label' => $this->imageEffect->label(),
      '%style' => $image_style->label(),
    ]);
    $form['actions']['submit']['#value'] = $this->t('Add effect');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareImageEffect($image_effect) {
    $image_effect = $this->effectManager->createInstance($image_effect);
    // Set the initial weight so this effect comes last.
    $image_effect->setWeight(count($this->imageStyle->getEffects()));
    return $image_effect;
  }

}
