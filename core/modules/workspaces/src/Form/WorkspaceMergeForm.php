<?php

namespace Drupal\workspaces\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\WorkspaceSafeFormInterface;
use Drupal\Core\Url;
use Drupal\workspaces\WorkspaceInterface;
use Drupal\workspaces\WorkspaceOperationFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

// cspell:ignore differring

/**
 * Provides a form that merges the contents for a workspace into another one.
 */
class WorkspaceMergeForm extends ConfirmFormBase implements ContainerInjectionInterface, WorkspaceSafeFormInterface {

  /**
   * The source workspace entity.
   *
   * @var \Drupal\workspaces\WorkspaceInterface
   */
  protected $sourceWorkspace;

  /**
   * The target workspace entity.
   *
   * @var \Drupal\workspaces\WorkspaceInterface
   */
  protected $targetWorkspace;

  /**
   * The workspace operation factory.
   *
   * @var \Drupal\workspaces\WorkspaceOperationFactory
   */
  protected $workspaceOperationFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new WorkspaceMergeForm.
   *
   * @param \Drupal\workspaces\WorkspaceOperationFactory $workspace_operation_factory
   *   The workspace operation factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(WorkspaceOperationFactory $workspace_operation_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->workspaceOperationFactory = $workspace_operation_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('workspaces.operation_factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workspace_merge_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?WorkspaceInterface $source_workspace = NULL, ?WorkspaceInterface $target_workspace = NULL) {
    $this->sourceWorkspace = $source_workspace;
    $this->targetWorkspace = $target_workspace;

    $form = parent::buildForm($form, $form_state);

    $workspace_merger = $this->workspaceOperationFactory->getMerger($this->sourceWorkspace, $this->targetWorkspace);

    $args = [
      '%source_label' => $this->sourceWorkspace->label(),
      '%target_label' => $this->targetWorkspace->label(),
    ];

    // List the changes that can be merged into the target.
    if ($source_rev_diff = $workspace_merger->getDifferringRevisionIdsOnSource()) {
      $total_count = $workspace_merger->getNumberOfChangesOnSource();
      $form['merge'] = [
        '#theme' => 'item_list',
        '#title' => $this->formatPlural($total_count, 'There is @count item that can be merged from %source_label to %target_label', 'There are @count items that can be merged from %source_label to %target_label', $args),
        '#items' => [],
        '#total_count' => $total_count,
      ];
      foreach ($source_rev_diff as $entity_type_id => $revision_difference) {
        $form['merge']['#items'][$entity_type_id] = $this->entityTypeManager->getDefinition($entity_type_id)->getCountLabel(count($revision_difference));
      }
    }

    // If there are no changes to merge, show an informational message.
    if (!isset($form['merge'])) {
      $form['description'] = [
        '#markup' => $this->t('There are no changes that can be merged from %source_label to %target_label.', $args),
      ];
      $form['actions']['submit']['#disabled'] = TRUE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Would you like to merge the contents of the %source_label workspace into %target_label?', [
      '%source_label' => $this->sourceWorkspace->label(),
      '%target_label' => $this->targetWorkspace->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Merge workspace contents.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('entity.workspace.collection', [], ['query' => $this->getDestinationArray()]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->workspaceOperationFactory->getMerger($this->sourceWorkspace, $this->targetWorkspace)->merge();
    $this->messenger()->addMessage($this->t('The contents of the %source_label workspace have been merged into %target_label.', [
      '%source_label' => $this->sourceWorkspace->label(),
      '%target_label' => $this->targetWorkspace->label(),
    ]));
  }

}
