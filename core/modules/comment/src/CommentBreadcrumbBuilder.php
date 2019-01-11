<?php

namespace Drupal\comment;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
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
   * The comment storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs the CommentBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->storage = $entity_type_manager->getStorage('comment');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $route_match->getRouteName() == 'comment.reply' && $route_match->getParameter('entity');
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['route']);
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));

    $entity = $route_match->getParameter('entity');
    $breadcrumb->addLink(new Link($entity->label(), $entity->toUrl()));
    $breadcrumb->addCacheableDependency($entity);

    if (($pid = $route_match->getParameter('pid')) && ($comment = $this->storage->load($pid))) {
      /** @var \Drupal\comment\CommentInterface $comment */
      $breadcrumb->addCacheableDependency($comment);
      // Display link to parent comment.
      // @todo Clean-up permalink in https://www.drupal.org/node/2198041
      $breadcrumb->addLink(new Link($comment->getSubject(), $comment->toUrl()));
    }

    return $breadcrumb;
  }

}
