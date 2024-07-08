<?php

namespace Drupal\comment;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class to define the comment breadcrumb builder.
 */
class CommentBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the CommentBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match, ?CacheableMetadata $cacheable_metadata = NULL) {
    // @todo Remove null safe operator in Drupal 12.0.0, see
    //   https://www.drupal.org/project/drupal/issues/3459277.
    $cacheable_metadata?->addCacheContexts(['route']);
    return $route_match->getRouteName() == 'comment.reply' && $route_match->getParameter('entity');
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    // @todo Remove in Drupal 12.0.0, will be added from ::applies(). See
    //   https://www.drupal.org/project/drupal/issues/3459277
    $breadcrumb->addCacheContexts(['route']);
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));

    $entity = $route_match->getParameter('entity');
    $breadcrumb->addLink(new Link($entity->label(), $entity->toUrl()));
    $breadcrumb->addCacheableDependency($entity);

    if (($pid = $route_match->getParameter('pid')) && ($comment = $this->entityTypeManager->getStorage('comment')->load($pid))) {
      /** @var \Drupal\comment\CommentInterface $comment */
      $breadcrumb->addCacheableDependency($comment);
      // Display link to parent comment.
      // @todo Clean-up permalink in https://www.drupal.org/node/2198041
      $breadcrumb->addLink(new Link($comment->getSubject(), $comment->toUrl()));
    }

    return $breadcrumb;
  }

}
