<?php

namespace Drupal\workspaces\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\workspaces\WorkspaceAccessException;
use Drupal\workspaces\WorkspaceOperationFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the workspace deploy form.
 */
class WorkspaceDeployForm extends ContentEntityForm implements WorkspaceFormInterface {

  /**
   * The workspace entity.
   *
   * @var \Drupal\workspaces\WorkspaceInterface
   */
  protected $entity;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The workspace operation factory.
   *
   * @var \Drupal\workspaces\WorkspaceOperationFactory
   */
  protected $workspaceOperationFactory;

  /**
   * Constructs a new WorkspaceDeployForm.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\workspaces\WorkspaceOperationFactory $workspace_operation_factory
   *   The workspace operation factory service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, MessengerInterface $messenger, WorkspaceOperationFactory $workspace_operation_factory) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->messenger = $messenger;
    $this->workspaceOperationFactory = $workspace_operation_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('messenger'),
      $container->get('workspaces.operation_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $workspace_publisher = $this->workspaceOperationFactory->getPublisher($this->entity);

    $args = [
      '%source_label' => $this->entity->label(),
      '%target_label' => $workspace_publisher->getTargetLabel(),
    ];
    $form['#title'] = $this->t('Deploy %source_label workspace', $args);

    // List the changes that can be pushed.
    if ($source_rev_diff = $workspace_publisher->getDifferringRevisionIdsOnSource()) {
      $total_count = $workspace_publisher->getNumberOfChangesOnSource();
      $form['deploy'] = [
        '#theme' => 'item_list',
        '#title' => $this->formatPlural($total_count, 'There is @count item that can be deployed from %source_label to %target_label', 'There are @count items that can be deployed from %source_label to %target_label', $args),
        '#items' => [],
        '#total_count' => $total_count,
      ];
      foreach ($source_rev_diff as $entity_type_id => $revision_difference) {
        $form['deploy']['#items'][$entity_type_id] = $this->entityTypeManager->getDefinition($entity_type_id)->getCountLabel(count($revision_difference));
      }
    }

    // If there are no changes to push or pull, show an informational message.
    if (!isset($form['deploy']) && !isset($form['refresh'])) {
      $form['help'] = [
        '#markup' => $this->t('There are no changes that can be deployed from %source_label to %target_label.', $args),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $elements = parent::actions($form, $form_state);
    unset($elements['delete']);

    $workspace_publisher = $this->workspaceOperationFactory->getPublisher($this->entity);

    if (isset($form['deploy'])) {
      $total_count = $form['deploy']['#total_count'];
      $elements['submit']['#value'] = $this->formatPlural($total_count, 'Deploy @count item to @target', 'Deploy @count items to @target', ['@target' => $workspace_publisher->getTargetLabel()]);
      $elements['submit']['#submit'] = ['::submitForm', '::deploy'];
    }
    else {
      // Do not allow the 'Deploy' operation if there's nothing to push.
      $elements['submit']['#value'] = $this->t('Deploy');
      $elements['submit']['#disabled'] = TRUE;
    }

    $elements['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button']],
      '#url' => $this->entity->toUrl('collection'),
    ];

    return $elements;
  }

  /**
   * Form submission handler; deploys the content to the workspace's target.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function deploy(array &$form, FormStateInterface $form_state) {
    $workspace = $this->entity;

    try {
      $workspace->publish();
      $this->messenger->addMessage($this->t('Successful deployment.'));
    }
    catch (WorkspaceAccessException $e) {
      $this->messenger->addMessage($e->getMessage(), 'error');
    }
    catch (\Exception $e) {
      $this->messenger->addMessage($this->t('Deployment failed. All errors have been logged.'), 'error');
    }
  }

}
