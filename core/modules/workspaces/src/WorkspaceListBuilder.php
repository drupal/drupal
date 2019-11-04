<?php

namespace Drupal\workspaces;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of workspace entities.
 *
 * @see \Drupal\workspaces\Entity\Workspace
 */
class WorkspaceListBuilder extends EntityListBuilder {

  use AjaxHelperTrait;

  /**
   * The workspace manager service.
   *
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * The workspace repository service.
   *
   * @var \Drupal\workspaces\WorkspaceRepositoryInterface
   */
  protected $workspaceRepository;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, WorkspaceManagerInterface $workspace_manager, WorkspaceRepositoryInterface $workspace_repository, RendererInterface $renderer) {
    parent::__construct($entity_type, $storage);
    $this->workspaceManager = $workspace_manager;
    $this->workspaceRepository = $workspace_repository;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('workspaces.manager'),
      $container->get('workspaces.repository'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    // Get all the workspace entities and sort them in tree order.
    $workspace_tree = $this->workspaceRepository->loadTree();
    $entities = array_replace($workspace_tree, $this->storage->loadMultiple());
    foreach ($entities as $id => $entity) {
      $entity->_depth = $workspace_tree[$id]['depth'];
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Workspace');
    $header['uid'] = $this->t('Owner');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\workspaces\WorkspaceInterface $entity */
    if (isset($entity->_depth) && $entity->_depth > 0) {
      $indentation = [
        '#theme' => 'indentation',
        '#size' => $entity->_depth,
      ];
    }

    $row['data'] = [
      'label' => [
        'data' => [
          '#prefix' => isset($indentation) ? $this->renderer->render($indentation) : '',
          '#markup' => $entity->label(),
        ],
      ],
      'owner' => $entity->getOwner()->getDisplayname(),
    ];
    $row['data'] = $row['data'] + parent::buildRow($entity);

    $active_workspace = $this->workspaceManager->getActiveWorkspace();
    if ($active_workspace && $entity->id() === $active_workspace->id()) {
      $row['class'] = 'active-workspace';
    }
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\workspaces\WorkspaceInterface $entity */
    $operations = parent::getDefaultOperations($entity);
    if (isset($operations['edit'])) {
      $operations['edit']['query']['destination'] = $entity->toUrl('collection')->toString();
    }

    $active_workspace = $this->workspaceManager->getActiveWorkspace();
    if (!$active_workspace || $entity->id() != $active_workspace->id()) {
      $operations['activate'] = [
        'title' => $this->t('Switch to @workspace', ['@workspace' => $entity->label()]),
        // Use a weight lower than the one of the 'Edit' operation because we
        // want the 'Activate' operation to be the primary operation.
        'weight' => 0,
        'url' => $entity->toUrl('activate-form', ['query' => ['destination' => $entity->toUrl('collection')->toString()]]),
      ];
    }

    if (!$entity->hasParent()) {
      $operations['deploy'] = [
        'title' => $this->t('Deploy content'),
        // The 'Deploy' operation should be the default one for the currently
        // active workspace.
        'weight' => ($active_workspace && $entity->id() == $active_workspace->id()) ? 0 : 20,
        'url' => $entity->toUrl('deploy-form', ['query' => ['destination' => $entity->toUrl('collection')->toString()]]),
      ];
    }
    else {
      /** @var \Drupal\workspaces\WorkspaceInterface $parent */
      $parent = $entity->parent->entity;
      $operations['merge'] = [
        'title' => $this->t('Merge into @target_label', [
          '@target_label' => $parent->label(),
        ]),
        'weight' => 5,
        'url' => Url::fromRoute('entity.workspace.merge_form',
          [
            'source_workspace' => $entity->id(),
            'target_workspace' => $parent->id(),
          ],
          [
            'query' => ['destination' => $entity->toUrl('collection')->toString()],
          ]
        ),
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    if ($this->isAjax()) {
      $this->offCanvasRender($build);
    }
    else {
      $build['#attached'] = [
        'library' => ['workspaces/drupal.workspaces.overview'],
      ];
    }
    return $build;
  }

  /**
   * Renders the off canvas elements.
   *
   * @param array $build
   *   A render array.
   */
  protected function offCanvasRender(array &$build) {
    $active_workspace = $this->workspaceManager->getActiveWorkspace();
    if ($active_workspace) {
      $active_workspace_classes = [
        'active-workspace--not-default',
        'active-workspace--' . $active_workspace->id(),
      ];
    }
    else {
      $active_workspace_classes = [
        'active-workspace--default',
      ];
    }

    $collection_url = Url::fromRoute('entity.workspace.collection');
    $row_count = count($build['table']['#rows']);
    $build['active_workspace'] = [
      '#type' => 'container',
      '#weight' => -20,
      '#attributes' => [
        'class' => array_merge(['active-workspace'], $active_workspace_classes),
      ],
      'label' => [
        '#type' => 'label',
        '#prefix' => '<div class="active-workspace__title">' . $this->t('Current workspace:') . '</div>',
        '#title' => $active_workspace ? $active_workspace->label() : $this->t('Live'),
        '#title_display' => '',
        '#attributes' => ['class' => 'active-workspace__label'],
      ],
      'manage' => [
        '#type' => 'link',
        '#title' => $this->t('Manage workspaces'),
        '#url' => $collection_url,
        '#attributes' => [
          'class' => ['active-workspace__manage'],
        ],
      ],
    ];
    if ($active_workspace) {
      $build['active_workspace']['actions'] = [
        '#type' => 'container',
        '#weight' => 20,
        '#attributes' => [
          'class' => ['active-workspace__actions'],
        ],
      ];
      if (!$active_workspace->hasParent()) {
        $build['active_workspace']['actions']['deploy'] = [
          '#type' => 'link',
          '#title' => $this->t('Deploy content'),
          '#url' => $active_workspace->toUrl('deploy-form', ['query' => ['destination' => $active_workspace->toUrl('collection')->toString()]]),
          '#attributes' => [
            'class' => ['button', 'active-workspace__button'],
          ],
        ];
      }
      else {
        $build['active_workspace']['actions']['merge'] = [
          '#type' => 'link',
          '#title' => $this->t('Merge content'),
          '#url' => Url::fromRoute('entity.workspace.merge_form',
            [
              'source_workspace' => $active_workspace->id(),
              'target_workspace' => $active_workspace->parent->target_id,
            ],
            [
              'query' => ['destination' => $active_workspace->toUrl('collection')->toString()],
            ]
          ),
          '#attributes' => [
            'class' => ['button', 'active-workspace__button'],
          ],
        ];
      }
    }
    if ($row_count > 2) {
      $build['all_workspaces'] = [
        '#type' => 'link',
        '#title' => $this->t('View all @count workspaces', ['@count' => $row_count]),
        '#url' => $collection_url,
        '#attributes' => [
          'class' => ['all-workspaces'],
        ],
      ];
    }
    $items = [];
    $rows = array_slice($build['table']['#rows'], 0, 5, TRUE);
    foreach ($rows as $id => $row) {
      if (!$active_workspace || $active_workspace->id() !== $id) {
        $url = Url::fromRoute('entity.workspace.activate_form', ['workspace' => $id], ['query' => $this->getDestinationArray()]);
        $items[] = [
          '#type' => 'link',
          '#title' => $row['data']['label'],
          '#url' => $url,
          '#attributes' => [
            'class' => ['use-ajax', 'workspaces__item', 'workspaces__item--not-default'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode([
              'width' => 500,
            ]),
          ],
        ];
      }
    }

    // Add an item for switching to Live.
    if ($active_workspace) {
      $items[] = [
        '#type' => 'link',
        '#title' => $this->t('Live'),
        '#url' => Url::fromRoute('workspaces.switch_to_live', [], ['query' => $this->getDestinationArray()]),
        '#attributes' => [
          'class' => ['use-ajax', 'workspaces__item', 'workspaces__item--default'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 500,
          ]),
        ],
      ];
    }

    $build['workspaces'] = [
      '#theme' => 'item_list',
      '#items' => $items,
      '#wrapper_attributes' => ['class' => ['workspaces']],
      '#cache' => [
        'contexts' => $this->entityType->getListCacheContexts(),
        'tags' => $this->entityType->getListCacheTags(),
      ],
    ];
    unset($build['table']);
    unset($build['pager']);
  }

}
