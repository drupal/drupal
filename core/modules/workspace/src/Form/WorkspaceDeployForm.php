<?php

namespace Drupal\workspace\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the workspace deploy form.
 */
class WorkspaceDeployForm extends ContentEntityForm {

  /**
   * The workspace entity.
   *
   * @var \Drupal\workspace\WorkspaceInterface
   */
  protected $entity;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new WorkspaceDeployForm.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, MessengerInterface $messenger, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('messenger'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $repository_handler = $this->entity->getRepositoryHandler();

    $args = [
      '%source_label' => $this->entity->label(),
      '%target_label' => $repository_handler->getLabel(),
    ];
    $form['#title'] = $this->t('Deploy %source_label workspace', $args);

    // List the changes that can be pushed.
    if ($source_rev_diff = $repository_handler->getDifferringRevisionIdsOnSource()) {
      $total_count = $repository_handler->getNumberOfChangesOnSource();
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

    // List the changes that can be pulled.
    if ($target_rev_diff = $repository_handler->getDifferringRevisionIdsOnTarget()) {
      $total_count = $repository_handler->getNumberOfChangesOnTarget();
      $form['refresh'] = [
        '#theme' => 'item_list',
        '#title' => $this->formatPlural($total_count, 'There is @count item that can be refreshed from %target_label to %source_label', 'There are @count items that can be refreshed from %target_label to %source_label', $args),
        '#items' => [],
        '#total_count' => $total_count,
      ];
      foreach ($target_rev_diff as $entity_type_id => $revision_difference) {
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

    $repository_handler = $this->entity->getRepositoryHandler();

    if (isset($form['deploy'])) {
      $total_count = $form['deploy']['#total_count'];
      $elements['submit']['#value'] = $this->formatPlural($total_count, 'Deploy @count item to @target', 'Deploy @count items to @target', ['@target' => $repository_handler->getLabel()]);
      $elements['submit']['#submit'] = ['::submitForm', '::deploy'];
    }
    else {
      // Do not allow the 'Deploy' operation if there's nothing to push.
      $elements['submit']['#value'] = $this->t('Deploy');
      $elements['submit']['#disabled'] = TRUE;
    }

    // Only show the 'Refresh' operation if there's something to pull.
    if (isset($form['refresh'])) {
      $total_count = $form['refresh']['#total_count'];
      $elements['refresh'] = [
        '#type' => 'submit',
        '#value' => $this->formatPlural($total_count, 'Refresh @count item from @target', 'Refresh @count items from @target', ['@target' => $repository_handler->getLabel()]),
        '#submit' => ['::submitForm', '::refresh'],
      ];
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
      $workspace->push();
      $this->messenger->addMessage($this->t('Successful deployment.'));
    }
    catch (\Exception $e) {
      $this->messenger->addMessage($this->t('Deployment failed. All errors have been logged.'), 'error');
    }
  }

  /**
   * Form submission handler; pulls the target's content into a workspace.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function refresh(array &$form, FormStateInterface $form_state) {
    $workspace = $this->entity;

    try {
      $workspace->pull();
      $this->messenger->addMessage($this->t('Refresh successful.'));
    }
    catch (\Exception $e) {
      $this->messenger->addMessage($this->t('Refresh failed. All errors have been logged.'), 'error');
    }
  }

}
