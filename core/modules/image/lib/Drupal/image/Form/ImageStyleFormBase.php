<?php

/**
 * @file
 * Contains \Drupal\image\Form\ImageStyleFormBase.
 */

namespace Drupal\image\Form;

use Drupal\Core\Entity\EntityControllerInterface;
use Drupal\Core\Entity\EntityFormController;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\Translator\TranslatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form controller for image style add and edit forms.
 */
abstract class ImageStyleFormBase extends EntityFormController implements EntityControllerInterface {

  /**
   * The image style entity storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $imageStyleStorage;

  /**
   * The translator service.
   *
   * @var \Drupal\Core\StringTranslation\Translator\TranslatorInterface
   */
  protected $translator;

  /**
   * Constructs a base class for image style add and edit forms.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $image_style_storage
   *   The image style entity storage controller.
   * @param \Drupal\Core\StringTranslation\Translator\TranslatorInterface $translator
   *   The translator service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, EntityStorageControllerInterface $image_style_storage, TranslatorInterface $translator) {
    parent::__construct($module_handler);
    $this->imageStyleStorage = $image_style_storage;
    $this->translator = $translator;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $container->get('module_handler'),
      $container->get('plugin.manager.entity')->getStorageController($entity_type),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->translator->translate('Image style name'),
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
    $form_state['redirect'] = 'admin/config/media/image-styles/manage/' . $this->entity->id();
    return $this->entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);
    unset($actions['delete']);
    return $actions;
  }

}
