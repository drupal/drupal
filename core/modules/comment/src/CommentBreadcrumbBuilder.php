<?php

/**
 * @file
 * Contains \Drupal\comment\CommentBreadcrumbBuilder.
 */

namespace Drupal\comment;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Entity\EntityManagerInterface;
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
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->storage = $entity_manager->getStorage('comment');
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
    $breadcrumb = [Link::createFromRoute($this->t('Home'), '<front>')];

    $entity = $route_match->getParameter('entity');
    $breadcrumb[] = new Link($entity->label(), $entity->urlInfo());

    if (($pid = $route_match->getParameter('pid')) && ($comment = $this->storage->load($pid))) {
      /** @var \Drupal\comment\CommentInterface $comment */
      // Display link to parent comment.
      // @todo Clean-up permalink in https://www.drupal.org/node/2198041
      $breadcrumb[] = new Link($comment->getSubject(), $comment->urlInfo());
    }

    return $breadcrumb;
  }

}
