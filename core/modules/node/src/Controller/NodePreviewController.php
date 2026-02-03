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
    protected ?FormBuilderInterface $formBuilder = NULL,
  ) {
    parent::__construct($entity_type_manager, $renderer);
    if ($this->formBuilder === NULL) {
      @trigger_error('Calling ' . __CLASS__ . ' constructor without the $formBuilder argument is deprecated in drupal:11.4.0 and it will be required in drupal:12.0.0. See https://www.drupal.org/project/drupal/issues/3339905', E_USER_DEPRECATED);
      $this->formBuilder = \Drupal::service('form_builder');
    }
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

  /**
   * The _title_callback for the page that renders a single node in preview.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node_preview
   *   The current node.
   *
   * @return string
   *   The page title.
   *
   * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is no
   * replacement.
   * @see https://www.drupal.org/project/drupal/issues/3024386
   */
  public function title(EntityInterface $node_preview) {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/project/drupal/issues/3024386', E_USER_DEPRECATED);
    return $this->entityRepository->getTranslationFromContext($node_preview)->label();
  }

}
