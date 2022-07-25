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
   * There are two possibilities, depending on the value of the additional
   * entity type property 'enable_page_title_template'.
   * - FALSE (default): use the output of the related field formatter if it
   *   exists. This approach only works correctly for the node entity type and
   *   with the 'string' formatter. In other cases it likely produces illegal
   *   markup and possibly incorrect display. This option has been retained for
   *   backward-compatibility to support sites that expect attributes set on
   *   the field to propagate to the page title.
   * - TRUE: use the output from the entity_page_title template. This approach
   *   works correctly in all cases, without relying on a particular field
   *   formatter or special templates and is the preferred option for the
   *   future.
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

    // If the entity has a label field, build the page title based on it.
    if ($entity instanceof FieldableEntityInterface) {
      $label_field = $entity->getEntityType()->getKey('label');
      $template_enabled = $entity->getEntityType()->get('enable_page_title_template');
      if ($label_field && $template_enabled) {
        // Set page title to the output from the entity_page_title template.
        $page_title = [
          '#theme' => 'entity_page_title',
          '#title' => $entity->label(),
          '#entity' => $entity,
          '#view_mode' => $page['#view_mode'],
        ];
        $page['#title'] = $this->renderer->render($page_title);

        // Prevent output of the label field in the main content.
        $page[$label_field]['#access'] = FALSE;
        return $page;
      }

      // Set page title to the rendered title field formatter instead of
      // the default plain text title.
      //
      // @todo https://www.drupal.org/project/drupal/issues/3015623
      //   Eventually delete this code and always use the first approach.
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
      ];

      // Set the non-aliased canonical path as a default shortlink.
      $page['#attached']['html_head_link'][] = [
        [
          'rel' => 'shortlink',
          'href' => $url->setOption('alias', TRUE)->toString(),
        ],
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
