<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\ContentEntityConfirmFormBase.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\ConfirmFormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a generic base class for an entity-based confirmation form.
 */
abstract class ContentEntityConfirmFormBase extends ContentEntityForm implements ConfirmFormInterface {

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
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
  public function buildForm(array $form, FormStateInterface $form_state) {
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
  public function form(array $form, FormStateInterface $form_state) {
    // Do not attach fields to the confirm form.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    return array(
      'submit' => array(
        '#type' => 'submit',
        '#value' => $this->getConfirmText(),
        '#submit' => array(
          array($this, 'submitForm'),
        ),
      ),
      'cancel' => ConfirmFormHelper::buildCancelLink($this, $this->getRequest()),
    );
  }

  /**
   * {@inheritdoc}
   *
   * The save() method is not used in ContentEntityConfirmFormBase. This
   * overrides the default implementation that saves the entity.
   *
   * Confirmation forms should override submitForm() instead for their logic.
   */
  public function save(array $form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   *
   * The delete() method is not used in ContentEntityConfirmFormBase. This
   * overrides the default implementation that redirects to the delete-form
   * confirmation form.
   *
   * Confirmation forms should override submitForm() instead for their logic.
   */
  public function delete(array $form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Override the default validation implementation as it is not necessary
    // nor possible to validate an entity in a confirmation form.
    return $this->entity;
  }

}
