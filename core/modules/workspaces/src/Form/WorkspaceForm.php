<?php

namespace Drupal\workspaces\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityConstraintViolationListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\workspaces\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the workspace edit forms.
 */
class WorkspaceForm extends ContentEntityForm {

  /**
   * The workspace entity.
   *
   * @var \Drupal\workspaces\WorkspaceInterface
   */
  protected $entity;

  /**
   * The workspace manager.
   */
  protected WorkspaceManagerInterface $workspaceManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->workspaceManager = $container->get('workspaces.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $workspace = $this->entity;

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit workspace %label', ['%label' => $workspace->label()]);
    }
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $workspace->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Workspace ID'),
      '#maxlength' => 255,
      '#default_value' => $workspace->id(),
      '#disabled' => !$workspace->isNew(),
      '#machine_name' => [
        'exists' => '\Drupal\workspaces\Entity\Workspace::load',
      ],
      '#element_validate' => [],
    ];

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditedFieldNames(FormStateInterface $form_state) {
    return array_merge([
      'label',
      'id',
    ], parent::getEditedFieldNames($form_state));
  }

  /**
   * {@inheritdoc}
   */
  protected function flagViolations(EntityConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    // Manually flag violations of fields not handled by the form display. This
    // is necessary as entity form displays only flag violations for fields
    // contained in the display.
    $field_names = [
      'label',
      'id',
    ];
    foreach ($violations->getByFields($field_names) as $violation) {
      [$field_name] = explode('.', $violation->getPropertyPath(), 2);
      $form_state->setErrorByName($field_name, $violation->getMessage());
    }
    parent::flagViolations($violations, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state): array {
    $actions = parent::actions($form, $form_state);

    // When adding a new workspace, the default action should also activate it.
    if ($this->entity->isNew()) {
      $actions['submit']['#value'] = $this->t('Save and switch');
      $actions['submit']['#submit'] = ['::submitForm', '::save', '::activate'];

      $actions['save'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#submit' => ['::submitForm', '::save'],
      ];
    }

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $workspace = $this->entity;
    $workspace->setNewRevision(TRUE);
    $status = $workspace->save();

    $info = ['%info' => $workspace->label()];
    $context = ['@type' => $workspace->bundle(), '%info' => $workspace->label()];
    $logger = $this->logger('workspaces');

    if ($status == SAVED_UPDATED) {
      $logger->notice('@type: updated %info.', $context);
      $this->messenger()->addMessage($this->t('Workspace %info has been updated.', $info));
    }
    else {
      $logger->notice('@type: added %info.', $context);
      $this->messenger()->addMessage($this->t('Workspace %info has been created.', $info));
    }

    if ($workspace->id()) {
      $form_state->setValue('id', $workspace->id());
      $form_state->set('id', $workspace->id());

      $collection_url = $workspace->toUrl('collection');
      $redirect = $collection_url->access() ? $collection_url : Url::fromRoute('<front>');
      $form_state->setRedirectUrl($redirect);
    }
    else {
      $this->messenger()->addError($this->t('The workspace could not be saved.'));
      $form_state->setRebuild();
    }
  }

  /**
   * Form submission handler for the 'submit' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function activate(array $form, FormStateInterface $form_state): void {
    $this->workspaceManager->setActiveWorkspace($this->entity);
    $this->messenger()->addMessage($this->t('%label is now the active workspace.', [
      '%label' => $this->entity->label(),
    ]));
  }

}
