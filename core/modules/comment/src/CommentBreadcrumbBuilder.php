<?php

/**
 * @file
 * Contains \Drupal\comment\CommentBreadcrumbBuilder.
 */

namespace Drupal\comment;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class to define the comment breadcrumb builder.
 */
class CommentBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

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
    $breadcrumb = array();

    $breadcrumb[] = Link::createFromRoute($this->t('Home'), '<front>');
    $entity = $route_match->getParameter('entity');
    $breadcrumb[] = new Link($entity->label(), $entity->urlInfo());
    return $breadcrumb;
  }

}
