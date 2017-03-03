<?php

namespace Drupal\workflows\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class WorkflowStateAddForm.
 */
class WorkflowStateAddForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workflow_state_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->getEntity();
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => '',
      '#description' => $this->t('Label for the state.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
    ];

    // Add additional form fields from the workflow type plugin.
    $form['type_settings'] = [
      $workflow->get('type') => $workflow->getTypePlugin()->buildStateConfigurationForm($form_state, $workflow),
      '#tree' => TRUE,
    ];

    return $form;
  }

  /**
   * Determines if the workflow state already exists.
   *
   * @param string $state_id
   *   The workflow state ID.
   *
   * @return bool
   *   TRUE if the workflow state exists, FALSE otherwise.
   */
  public function exists($state_id) {
    /** @var \Drupal\workflows\WorkflowInterface $original_workflow */
    $original_workflow = \Drupal::entityTypeManager()->getStorage('workflow')->loadUnchanged($this->getEntity()->id());
    return $original_workflow->hasState($state_id);
  }

  /**
   * Copies top-level form values to entity properties
   *
   * This form can only change values for a state, which is part of workflow.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the current form should operate upon.
   * @param array $form
   *   A nested array of form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    /** @var \Drupal\workflows\WorkflowInterface $entity */
    $values = $form_state->getValues();

    // This is fired twice so we have to check that the entity does not already
    // have the state.
    if (!$entity->hasState($values['id'])) {
      $entity->addState($values['id'], $values['label']);
      if (isset($values['type_settings'])) {
        $configuration = $entity->getTypePlugin()->getConfiguration();
        $configuration['states'][$values['id']] = $values['type_settings'][$entity->getTypePlugin()->getPluginId()];
        $entity->set('type_settings', $configuration);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->entity;
    $workflow->save();
    drupal_set_message($this->t('Created %label state.', [
      '%label' => $workflow->getState($form_state->getValue('id'))->label(),
    ]));
    $form_state->setRedirectUrl($workflow->toUrl('edit-form'));
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => ['::submitForm', '::save'],
    ];
    return $actions;
  }

}
