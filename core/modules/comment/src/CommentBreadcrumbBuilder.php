<?php

/**
 * @file
 * Contains \Drupal\comment\CommentBreadcrumbBuilder.
 */

namespace Drupal\comment;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Class to define the comment breadcrumb builder.
 */
class CommentBreadcrumbBuilder extends BreadcrumbBuilderBase {

  /**
   * Stores the Entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a CommentBreadcrumbBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $route_match->getRouteName() == 'comment.reply'
      && $route_match->getParameter('entity_type')
      && $route_match->getParameter('entity_id')
      && $route_match->getParameter('field_name');
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = array();

    $breadcrumb[] = $this->l($this->t('Home'), '<front>');
    $entity = $this->entityManager
      ->getStorage($route_match->getParameter('entity_type'))
      ->load($route_match->getParameter('entity_id'));
    $breadcrumb[] = \Drupal::linkGenerator()->generateFromUrl($entity->label(), $entity->urlInfo());
    return $breadcrumb;
  }

}
