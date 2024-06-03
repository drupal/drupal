<?php

namespace Drupal\workspaces\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\WorkspaceSafeFormInterface;
use Drupal\Core\Url;
use Drupal\workspaces\WorkspaceAccessException;
use Drupal\workspaces\WorkspaceInterface;
use Drupal\workspaces\WorkspaceOperationFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the workspace publishing form.
 */
class WorkspacePublishForm extends ConfirmFormBase implements ContainerInjectionInterface, WorkspaceSafeFormInterface {

  /**
   * The workspace that will be published.
   *
   * @var \Drupal\workspaces\WorkspaceInterface
   */
  protected $workspace;

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
   * Constructs a new WorkspacePublishForm.
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
    return 'workspace_publish_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?WorkspaceInterface $workspace = NULL) {
    $this->workspace = $workspace;

    $form = parent::buildForm($form, $form_state);

    $workspace_publisher = $this->workspaceOperationFactory->getPublisher($this->workspace);

    $args = [
      '%source_label' => $this->workspace->label(),
      '%target_label' => $workspace_publisher->getTargetLabel(),
    ];
    $form['#title'] = $this->t('Publish %source_label workspace', $args);

    // List the changes that can be pushed.
    if ($source_rev_diff = $workspace_publisher->getDifferringRevisionIdsOnSource()) {
      $total_count = $workspace_publisher->getNumberOfChangesOnSource();
      $form['description'] = [
        '#theme' => 'item_list',
        '#title' => $this->formatPlural($total_count, 'There is @count item that can be published from %source_label to %target_label', 'There are @count items that can be published from %source_label to %target_label', $args),
        '#items' => [],
        '#total_count' => $total_count,
      ];
      foreach ($source_rev_diff as $entity_type_id => $revision_difference) {
        $form['description']['#items'][$entity_type_id] = $this->entityTypeManager->getDefinition($entity_type_id)->getCountLabel(count($revision_difference));
      }

      $form['actions']['submit']['#value'] = $this->formatPlural($total_count, 'Publish @count item to @target', 'Publish @count items to @target', ['@target' => $workspace_publisher->getTargetLabel()]);
    }
    else {
      // If there are no changes to push or pull, show an informational message.
      $form['help'] = [
        '#markup' => $this->t('There are no changes that can be published from %source_label to %target_label.', $args),
      ];

      // Do not allow the 'Publish' operation if there's nothing to publish.
      $form['actions']['submit']['#value'] = $this->t('Publish');
      $form['actions']['submit']['#disabled'] = TRUE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Would you like to publish the contents of the %label workspace?', [
      '%label' => $this->workspace->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Publish workspace contents.');
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
    $workspace = $this->workspace;

    try {
      $workspace->publish();
      $this->messenger()->addMessage($this->t('Successful publication.'));
    }
    catch (WorkspaceAccessException $e) {
      $this->messenger()->addMessage($e->getMessage(), 'error');
    }
    catch (\Exception $e) {
      $this->messenger()->addMessage($this->t('Publication failed. All errors have been logged.'), 'error');
      $this->getLogger('workspaces')->error($e->getMessage());
    }
  }

}
