<?php

namespace Drupal\workspaces\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\workspaces\WorkspaceAccessException;
use Drupal\workspaces\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form that activates a different workspace.
 */
class WorkspaceSwitcherForm extends FormBase implements WorkspaceFormInterface {

  /**
   * The workspace manager.
   *
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * The workspace entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $workspaceStorage;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new WorkspaceSwitcherForm.
   *
   * @param \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger) {
    $this->workspaceManager = $workspace_manager;
    $this->workspaceStorage = $entity_type_manager->getStorage('workspace');
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('workspaces.manager'),
      $container->get('entity_type.manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workspace_switcher_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $workspaces = $this->workspaceStorage->loadMultiple();
    $workspace_labels = [];
    foreach ($workspaces as $workspace) {
      $workspace_labels[$workspace->id()] = $workspace->label();
    }

    $active_workspace = $this->workspaceManager->getActiveWorkspace();
    if ($active_workspace) {
      unset($workspace_labels[$active_workspace->id()]);
    }

    $form['current'] = [
      '#type' => 'item',
      '#title' => $this->t('Current workspace'),
      '#markup' => $active_workspace ? $active_workspace->label() : $this->t('None'),
      '#wrapper_attributes' => [
        'class' => ['container-inline'],
      ],
    ];

    $form['workspace_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Select workspace'),
      '#required' => TRUE,
      '#options' => $workspace_labels,
      '#wrapper_attributes' => [
        'class' => ['container-inline'],
      ],
      '#access' => !empty($workspace_labels),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Activate'),
      '#button_type' => 'primary',
      '#access' => !empty($workspace_labels),
    ];

    if ($active_workspace) {
      $form['actions']['switch_to_live'] = [
        '#type' => 'submit',
        '#submit' => ['::submitSwitchToLive'],
        '#value' => $this->t('Switch to Live'),
        '#limit_validation_errors' => [],
        '#button_type' => 'primary',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $id = $form_state->getValue('workspace_id');

    /** @var \Drupal\workspaces\WorkspaceInterface $workspace */
    $workspace = $this->workspaceStorage->load($id);

    try {
      $this->workspaceManager->setActiveWorkspace($workspace);
      $this->messenger->addMessage($this->t('%workspace_label is now the active workspace.', ['%workspace_label' => $workspace->label()]));
    }
    catch (WorkspaceAccessException $e) {
      $this->messenger->addError($this->t('You do not have access to activate the %workspace_label workspace.', ['%workspace_label' => $workspace->label()]));
    }
  }

  /**
   * Submit handler for switching to the live version of the site.
   */
  public function submitSwitchToLive(array &$form, FormStateInterface $form_state) {
    $this->workspaceManager->switchToLive();
    $this->messenger->addMessage($this->t('You are now viewing the live version of the site.'));
  }

}
