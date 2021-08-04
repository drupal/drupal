<?php

namespace Drupal\Core\Entity\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a generic controller to render a single entity.
 */
class EntityViewController implements ContainerInjectionInterface, TrustedCallbackInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Creates an EntityViewController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * Pre-render callback to build the page title.
   *
   * @param array $page
   *   A page render array.
   *
   * @return array
   *   The changed page render array.
   */
  public function buildTitle(array $page) {
    $entity_type = $page['#entity_type'];
    $entity = $page['#' . $entity_type];
    // If the entity's label is rendered using a field formatter, set the
    // rendered title field formatter as the page title instead of the default
    // plain text title. This allows attributes set on the field to propagate
    // correctly (e.g. in-place editing).
    if ($entity instanceof FieldableEntityInterface) {
      $label_field = $entity->getEntityType()->getKey('label');
      if (isset($page[$label_field])) {
        // Allow templates and theme functions to generate different markup
        // for the page title, which must be inline markup as it will be placed
        // inside <h1>.  See field--node--title.html.twig.
        $page[$label_field]['#is_page_title'] = TRUE;
        $page['#title'] = $this->renderer->render($page[$label_field]);
      }
    }
    return $page;
  }

  /**
   * Provides a page to render a single entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $_entity
   *   The Entity to be rendered. Note this variable is named $_entity rather
   *   than $entity to prevent collisions with other named placeholders in the
   *   route.
   * @param string $view_mode
   *   (optional) The view mode that should be used to display the entity.
   *   Defaults to 'full'.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function view(EntityInterface $_entity, $view_mode = 'full') {
    $page = $this->entityTypeManager
      ->getViewBuilder($_entity->getEntityTypeId())
      ->view($_entity, $view_mode);

    $page['#pre_render'][] = [$this, 'buildTitle'];
    $page['#entity_type'] = $_entity->getEntityTypeId();
    $page['#' . $page['#entity_type']] = $_entity;

    // Add canonical and shortlink links if the entity has a canonical
    // link template and is not new.
    if ($_entity->hasLinkTemplate('canonical') && !$_entity->isNew()) {

      $url = $_entity->toUrl('canonical')->setAbsolute(TRUE);
      $page['#attached']['html_head_link'][] = [
        [
          'rel' => 'canonical',
          'href' => $url->toString(),
        ],
        TRUE,
      ];

      // Set the non-aliased canonical path as a default shortlink.
      $page['#attached']['html_head_link'][] = [
        [
          'rel' => 'shortlink',
          'href' => $url->setOption('alias', TRUE)->toString(),
        ],
        TRUE,
      ];

      // Since this generates absolute URLs, it can only be cached "per site".
      $page['#cache']['contexts'][] = 'url.site';
    }

    return $page;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['buildTitle'];
  }

  /**
   * Provides a page to render a single entity revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface $_entity_revision
   *   The Entity to be rendered. Note this variable is named $_entity_revision
   *   rather than $entity to prevent collisions with other named placeholders
   *   in the route.
   * @param string $view_mode
   *   (optional) The view mode that should be used to display the entity.
   *   Defaults to 'full'.
   *
   * @return array
   *   A render array.
   */
  public function viewRevision(EntityInterface $_entity_revision, $view_mode = 'full') {
    return $this->view($_entity_revision, $view_mode);
  }

}
