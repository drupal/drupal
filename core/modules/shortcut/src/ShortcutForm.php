<?php

/**
 * @file
 * Contains \Drupal\shortcut\ShortcutForm.
 */

namespace Drupal\shortcut;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
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
    parent::validate($form, $form_state);

    $this->entity->path->value = $form_state->getValue('path');
    /** @var \Symfony\Component\Validator\ConstraintViolationListInterface $result */
    $result = $this->entity->path->validate();
    if ($result->count()) {
      $form_state->setErrorByName('path', $result->offsetGet(0)->getMessage());
    }
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
