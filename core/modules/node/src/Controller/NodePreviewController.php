<?php

namespace Drupal\node\Controller;

use Drupal\Core\Entity\Controller\EntityViewController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\Form\NodePreviewForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller to render a single node in preview.
 */
class NodePreviewController extends EntityViewController {

  /**
   * Creates a NodePreviewController object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RendererInterface $renderer,
    protected readonly EntityRepositoryInterface $entityRepository,
    protected readonly FormBuilderInterface $formBuilder,
  ) {
    parent::__construct($entity_type_manager, $renderer);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('entity.repository'),
      $container->get('form_builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $node_preview, $view_mode_id = 'full', $langcode = NULL) {
    $node_preview->preview_view_mode = $view_mode_id;
    $build = parent::view($node_preview, $view_mode_id);

    $build['#attached']['library'][] = 'node/drupal.node.preview';
    $build['#attached']['page_top']['node_preview'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'node-preview-container',
          'container-inline',
        ],
      ],
      'view_mode' => $this->formBuilder->getForm(NodePreviewForm::class, $node_preview),
    ];
    // Don't render cache previews.
    unset($build['#cache']);

    return $build;
  }

}
