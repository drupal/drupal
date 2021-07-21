<?php

namespace Drupal\workspaces;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Workspace view builder.
 */
class WorkspaceViewBuilder extends EntityViewBuilder {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The workspace association service.
   *
   * @var \Drupal\workspaces\WorkspaceAssociationInterface
   */
  protected $workspaceAssociation;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The entity bundle information service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->workspaceAssociation = $container->get('workspaces.association');
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->bundleInfo = $container->get('entity_type.bundle.info');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    parent::buildComponents($build, $entities, $displays, $view_mode);
    $bundle_info = $this->bundleInfo->getAllBundleInfo();

    $header = [
      'title' => $this->t('Title'),
      'type' => $this->t('Type'),
      'changed' => $this->t('Last changed'),
      'owner' => $this->t('Author'),
      'operations' => $this->t('Operations'),
    ];
    foreach ($entities as $build_id => $entity) {
      $all_tracked_entities = $this->workspaceAssociation->getTrackedEntities($entity->id());

      $build[$build_id]['changes']['overview'] = [
        '#type' => 'item',
        '#title' => $this->t('Workspace changes'),
      ];

      $build[$build_id]['changes']['list'] = [
        '#type' => 'table',
        '#header' => $header,
        '#empty' => $this->t('This workspace has no changes.'),
      ];

      $changes_count = [];
      foreach ($all_tracked_entities as $entity_type_id => $tracked_entities) {
        // Ensure that newest revisions are displayed at the top.
        krsort($tracked_entities);

        $changes_count[$entity_type_id] = $this->entityTypeManager->getDefinition($entity_type_id)->getCountLabel(count($tracked_entities));

        $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
        if ($this->entityTypeManager->hasHandler($entity_type_id, 'list_builder')) {
          $list_builder = $this->entityTypeManager->getListBuilder($entity_type_id);
        }
        else {
          $list_builder = $this->entityTypeManager->createHandlerInstance(EntityListBuilder::class, $entity_type);
        }

        $revisions = $this->entityTypeManager->getStorage($entity_type_id)->loadMultipleRevisions(array_keys($tracked_entities));

        // Load all users at once.
        $user_ids = [];
        foreach ($revisions as $revision) {
          if ($revision instanceof EntityOwnerInterface) {
            $user_ids[$revision->getOwnerId()] = $revision->getOwnerId();
          }
        }

        if ($user_ids = array_filter($user_ids)) {
          $revision_owners = $this->entityTypeManager->getStorage('user')->loadMultiple($user_ids);
        }

        foreach ($revisions as $revision) {
          if ($revision->getEntityType()->hasLinkTemplate('canonical')) {
            $title = [
              '#type' => 'link',
              '#title' => $revision->label(),
              '#url' => $revision->toUrl(),
            ];
          }
          else {
            $title = ['#markup' => $revision->label()];
          }

          if (count($bundle_info[$entity_type_id]) > 1) {
            $type = [
              '#markup' => $this->t('@entity_type_label: @entity_bundle_label', [
                '@entity_type_label' => $entity_type->getLabel(),
                '@entity_bundle_label' => $bundle_info[$entity_type_id][$revision->bundle()]['label'],
              ]),
            ];
          }
          else {
            $type = ['#markup' => $bundle_info[$entity_type_id][$revision->bundle()]['label']];
          }

          $changed = $revision instanceof EntityChangedInterface
            ? $this->dateFormatter->format($revision->getChangedTime())
            : '';

          if ($revision instanceof EntityOwnerInterface && isset($revision_owners[$revision->getOwnerId()])) {
            $author = [
              '#theme' => 'username',
              '#account' => $revision_owners[$revision->getOwnerId()],
            ];
          }
          else {
            $author = ['#markup' => ''];
          }

          $build[$build_id]['changes']['list'][$entity_type_id . ':' . $revision->id()] = [
            '#entity' => $revision,
            'title' => $title,
            'type' => $type,
            'changed' => ['#markup' => $changed],
            'owner' => $author,
            'operations' => [
              '#type' => 'operations',
              '#links' => $list_builder->getOperations($revision),
            ],
          ];
        }
      }

      if ($changes_count) {
        $build[$build_id]['changes']['overview']['#markup'] = implode(', ', $changes_count);
      }
    }
  }

}
