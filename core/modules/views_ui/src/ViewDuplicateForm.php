<?php

namespace Drupal\views_ui;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the Views duplicate form.
 */
class ViewDuplicateForm extends ViewFormBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    // Do not prepare the entity while it is being added.
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    parent::form($form, $form_state);

    $form['#title'] = $this->t('Duplicate of @label', array('@label' => $this->entity->label()));

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('View name'),
      '#required' => TRUE,
      '#size' => 32,
      '#maxlength' => 255,
      '#default_value' => $this->t('Duplicate of @label', array('@label' => $this->entity->label())),
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#maxlength' => 128,
      '#machine_name' => array(
        'exists' => '\Drupal\views\Views::getView',
        'source' => array('label'),
      ),
      '#default_value' => '',
      '#description' => $this->t('A unique machine-readable name for this View. It must only contain lowercase letters, numbers, and underscores.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Duplicate'),
    );
    return $actions;
  }

  /**
   * Form submission handler for the 'clone' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A reference to a keyed array containing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity = $this->entity->createDuplicate();
    $this->entity->set('label', $form_state->getValue('label'));
    $this->entity->set('id', $form_state->getValue('id'));
    $this->entity->save();

    // Redirect the user to the view admin form.
    $form_state->setRedirectUrl($this->entity->urlInfo('edit-form'));
  }

}
