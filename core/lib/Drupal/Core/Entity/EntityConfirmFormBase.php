<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityConfirmFormBase.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\ConfirmFormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a generic base class for an entity-based confirmation form.
 */
abstract class EntityConfirmFormBase extends EntityFormController implements ConfirmFormInterface {

  /**
   * {@inheritdoc}
   */
  public function getBaseFormID() {
    return $this->entity->getEntityTypeId() . '_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Confirm');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormName() {
    return 'confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['#title'] = $this->getQuestion();

    $form['#attributes']['class'][] = 'confirmation';
    $form['description'] = array('#markup' => $this->getDescription());
    $form[$this->getFormName()] = array('#type' => 'hidden', '#value' => 1);

    // By default, render the form using theme_confirm_form().
    if (!isset($form['#theme'])) {
      $form['#theme'] = 'confirm_form';
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->getConfirmText();
    unset($actions['delete']);

    // Prepare cancel link.
    $actions['cancel'] = ConfirmFormHelper::buildCancelLink($this, $this->getRequest());
    return $actions;
  }

}
