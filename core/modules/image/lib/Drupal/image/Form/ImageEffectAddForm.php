<?php

/**
 * @file
 * Contains \Drupal\image\Form\ImageEffectAddForm.
 */

namespace Drupal\image\Form;

use Drupal\Core\Controller\ControllerInterface;
use Drupal\image\ImageEffectManager;
use Drupal\image\ImageStyleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an add form for image effects.
 */
class ImageEffectAddForm extends ImageEffectFormBase implements ControllerInterface {

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
  public function buildForm(array $form, array &$form_state, Request $request = NULL, ImageStyleInterface $image_style = NULL, $image_effect = NULL) {
    $form = parent::buildForm($form, $form_state, $request, $image_style, $image_effect);

    drupal_set_title(t('Add %label effect', array('%label' => $this->imageEffect->label())), PASS_THROUGH);
    $form['actions']['submit']['#value'] = t('Add effect');

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
