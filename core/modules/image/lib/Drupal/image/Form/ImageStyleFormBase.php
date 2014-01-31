<?php

/**
 * @file
 * Contains \Drupal\image\Form\ImageStyleFormBase.
 */

namespace Drupal\image\Form;

use Drupal\Core\Entity\EntityFormController;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form controller for image style add and edit forms.
 */
abstract class ImageStyleFormBase extends EntityFormController {

  /**
   * The image style entity storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $imageStyleStorage;

  /**
   * Constructs a base class for image style add and edit forms.
   *
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $image_style_storage
   *   The image style entity storage controller.
   */
  public function __construct(EntityStorageControllerInterface $image_style_storage) {
    $this->imageStyleStorage = $image_style_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorageController('image_style')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Image style name'),
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
    );
    $form['name'] = array(
      '#type' => 'machine_name',
      '#machine_name' => array(
        'exists' => array($this->imageStyleStorage, 'load'),
      ),
      '#default_value' => $this->entity->id(),
      '#required' => TRUE,
    );

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $this->entity->save();
    $form_state['redirect_route'] = $this->entity->urlInfo('edit-form');
  }

}
