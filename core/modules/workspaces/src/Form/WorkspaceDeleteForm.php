<?php

namespace Drupal\workspaces\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workspaces\WorkspaceAssociationInterface;
use Drupal\workspaces\WorkspaceRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a workspace.
 *
 * @internal
 */
class WorkspaceDeleteForm extends ContentEntityDeleteForm {

  /**
   * The workspace entity.
   *
   * @var \Drupal\workspaces\WorkspaceInterface
   */
  protected $entity;

  /**
   * The workspace association service.
   *
   * @var \Drupal\workspaces\WorkspaceAssociationInterface
   */
  protected $workspaceAssociation;

  /**
   * The workspace repository service.
   *
   * @var \Drupal\workspaces\WorkspaceRepositoryInterface
   */
  protected $workspaceRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('workspaces.association'),
      $container->get('workspaces.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * Constructs a WorkspaceDeleteForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\workspaces\WorkspaceAssociationInterface $workspace_association
   *   The workspace association service to check how many revisions will be
   *   deleted.
   * @param \Drupal\workspaces\WorkspaceRepositoryInterface $workspace_repository
   *   The workspace repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, WorkspaceAssociationInterface $workspace_association, WorkspaceRepositoryInterface $workspace_repository, ?EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, ?TimeInterface $time = NULL) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->workspaceAssociation = $workspace_association;
    $this->workspaceRepository = $workspace_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $workspace_tree = $this->workspaceRepository->loadTree();
    if (!empty($workspace_tree[$this->entity->id()]['descendants'])) {
      $form['description']['#markup'] = $this->t('The %label workspace can not be deleted because it has child workspaces.', [
        '%label' => $this->entity->label(),
      ]);
      $form['actions']['submit']['#disabled'] = TRUE;

      return $form;
    }

    $tracked_entities = $this->workspaceAssociation->getTrackedEntities($this->entity->id());
    $items = [];
    foreach ($tracked_entities as $entity_type_id => $entity_ids) {
      $revision_ids = $this->workspaceAssociation->getAssociatedRevisions($this->entity->id(), $entity_type_id, $entity_ids);
      $label = $this->entityTypeManager->getDefinition($entity_type_id)->getLabel();
      $items[] = $this->formatPlural(count($revision_ids), '1 @label revision.', '@count @label revisions.', ['@label' => $label]);
    }
    $form['revisions'] = [
      '#theme' => 'item_list',
      '#title' => $this->t('The following will also be deleted:'),
      '#items' => $items,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action cannot be undone, and will also delete all content created in this workspace.');
  }

}
