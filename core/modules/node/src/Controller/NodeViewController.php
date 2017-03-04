<?php

namespace Drupal\node\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Controller\EntityViewController;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller to render a single node.
 */
class NodeViewController extends EntityViewController {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Creates an NodeViewController object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user. For backwards compatibility this is optional, however
   *   this will be removed before Drupal 9.0.0.
   */
  public function __construct(EntityManagerInterface $entity_manager, RendererInterface $renderer, AccountInterface $current_user = NULL) {
    parent::__construct($entity_manager, $renderer);
    $this->currentUser = $current_user ?: \Drupal::currentUser();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('renderer'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $node, $view_mode = 'full', $langcode = NULL) {
    $build = parent::view($node, $view_mode, $langcode);

    foreach ($node->uriRelationships() as $rel) {
      $url = $node->toUrl($rel);
      // Add link relationships if the user is authenticated or if the anonymous
      // user has access. Access checking must be done for anonymous users to
      // avoid traffic to inaccessible pages from web crawlers. For
      // authenticated users, showing the links in HTML head does not impact
      // user experience or security, since the routes are access checked when
      // visited and only visible via view source. This prevents doing
      // potentially expensive and hard to cache access checks on every request.
      // This means that the page will vary by user.permissions. We also rely on
      // the access checking fallback to ensure the correct cacheability
      // metadata if we have to check access.
      if ($this->currentUser->isAuthenticated() || $url->access($this->currentUser)) {
        // Set the node path as the canonical URL to prevent duplicate content.
        $build['#attached']['html_head_link'][] = [
          [
            'rel' => $rel,
            'href' => $url->toString(),
          ],
          TRUE,
        ];
      }

      if ($rel == 'canonical') {
        // Set the non-aliased canonical path as a default shortlink.
        $build['#attached']['html_head_link'][] = [
          [
            'rel' => 'shortlink',
            'href' => $url->setOption('alias', TRUE)->toString(),
          ],
          TRUE,
        ];
      }
    }

    // Given this varies by $this->currentUser->isAuthenticated(), add a cache
    // context based on the anonymous role.
    $build['#cache']['contexts'][] = 'user.roles:anonymous';

    return $build;
  }

  /**
   * The _title_callback for the page that renders a single node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function title(EntityInterface $node) {
    return $this->entityManager->getTranslationFromContext($node)->label();
  }

}
