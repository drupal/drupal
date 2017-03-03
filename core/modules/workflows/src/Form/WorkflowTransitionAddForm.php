<?php

namespace Drupal\workflows\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workflows\State;

/**
 * Class WorkflowTransitionAddForm.
 */
class WorkflowTransitionAddForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workflow_transition_add_form';
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
      '#description' => $this->t('Label for the transition.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
    ];

    // @todo https://www.drupal.org/node/2830584 Add some ajax to ensure that
    //   only valid transitions are selectable.
    $states = array_map([State::class, 'labelCallback'], $workflow->getStates());
    $form['from'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('From'),
      '#required' => TRUE,
      '#default_value' => [],
      '#options' => $states,
    ];
    $form['to'] = [
      '#type' => 'radios',
      '#title' => $this->t('To'),
      '#required' => TRUE,
      '#default_value' => [],
      '#options' => $states,
    ];

    // Add additional form fields from the workflow type plugin.
    $form['type_settings'] = [
      $workflow->get('type') => $workflow->getTypePlugin()->buildTransitionConfigurationForm($form_state, $workflow),
      '#tree' => TRUE,
    ];

    return $form;
  }

  /**
   * Determines if the workflow transition already exists.
   *
   * @param string $transition_id
   *   The workflow transition ID.
   *
   * @return bool
   *   TRUE if the workflow transition exists, FALSE otherwise.
   */
  public function exists($transition_id) {
    /** @var \Drupal\workflows\WorkflowInterface $original_workflow */
    $original_workflow = \Drupal::entityTypeManager()->getStorage('workflow')->loadUnchanged($this->getEntity()->id());
    return $original_workflow->hasTransition($transition_id);
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
    if (!$form_state->isValidationComplete()) {
      // Only do something once form validation is complete.
      return;
    }
    /** @var \Drupal\workflows\WorkflowInterface $entity */
    $values = $form_state->getValues();
    $entity->addTransition($values['id'], $values['label'], array_filter($values['from']), $values['to']);
    if (isset($values['type_settings'])) {
      $configuration = $entity->getTypePlugin()->getConfiguration();
      $configuration['transitions'][$values['id']] = $values['type_settings'][$entity->getTypePlugin()->getPluginId()];
      $entity->set('type_settings', $configuration);
    }
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->getEntity();
    $values = $form_state->getValues();
    foreach (array_filter($values['from']) as $from_state_id) {
      if ($workflow->hasTransitionFromStateToState($from_state_id, $values['to'])) {
        $form_state->setErrorByName('from][' . $from_state_id, $this->t('The transition from %from to %to already exists.', [
          '%from' => $workflow->getState($from_state_id)->label(),
          '%to' => $workflow->getState($values['to'])->label(),
        ]));
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
    drupal_set_message($this->t('Created %label transition.', [
      '%label' => $form_state->getValue('label'),
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
