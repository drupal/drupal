<?php

/**
 * @file
 * Contains \Drupal\shortcut\ShortcutForm.
 */

namespace Drupal\shortcut;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the shortcut entity forms.
 */
class ShortcutForm extends ContentEntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\shortcut\ShortcutInterface
   */
  protected $entity;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * Constructs a new ShortcutForm instance.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   */
  public function __construct(EntityManagerInterface $entity_manager, PathValidatorInterface $path_validator) {
    parent::__construct($entity_manager);

    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'), $container->get('path.validator'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['path'] = array(
      '#type' => 'textfield',
      '#title' => t('Path'),
      '#size' => 40,
      '#maxlength' => 255,
      '#field_prefix' => $this->url('<front>', array(), array('absolute' => TRUE)),
      '#default_value' => $this->entity->path->value,
    );

    $form['langcode'] = array(
      '#title' => t('Language'),
      '#type' => 'language_select',
      '#default_value' => $this->entity->getUntranslated()->language()->getId(),
      '#languages' => LanguageInterface::STATE_ALL,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $entity = parent::buildEntity($form, $form_state);

    // Set the computed 'path' value so it can used in the preSave() method to
    // derive the route name and parameters.
    $entity->path->value = $form_state->getValue('path');

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    if (!$this->pathValidator->isValid($form_state->getValue('path'))) {
      $form_state->setErrorByName('path', $this->t('The shortcut must correspond to a valid path on the site.'));
    }

    parent::validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->save();

    if ($entity->isNew()) {
      $message = $this->t('The shortcut %link has been updated.', array('%link' => $entity->getTitle()));
    }
    else {
      $message = $this->t('Added a shortcut for %title.', array('%title' => $entity->getTitle()));
    }
    drupal_set_message($message);

    $form_state->setRedirect(
      'entity.shortcut_set.customize_form',
      array('shortcut_set' => $entity->bundle())
    );
  }

}
