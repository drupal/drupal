<?php

/**
 * @file
 * Contains \Drupal\comment\CommentBreadcrumbBuilder.
 */

namespace Drupal\comment;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderBase;
use Drupal\Core\Entity\EntityManager;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Class to define the comment breadcrumb builder.
 */
class CommentBreadcrumbBuilder extends BreadcrumbBuilderBase {

  /**
   * Stores the Entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Constructs a CommentBreadcrumbBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityManager
   *   The entity manager.
   */
  public function __construct(EntityManager $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $attributes) {
    if (isset($attributes[RouteObjectInterface::ROUTE_NAME]) && $attributes[RouteObjectInterface::ROUTE_NAME] == 'comment.reply'
      && isset($attributes['entity_type'])
      && isset($attributes['entity_id'])
      && isset($attributes['field_name'])
      ) {
      $breadcrumb[] = $this->l($this->t('Home'), '<front>');
      $entity = $this->entityManager
        ->getStorageController($attributes['entity_type'])
        ->load($attributes['entity_id']);
      $uri = $entity->uri();
      $breadcrumb[] = l($entity->label(), $uri['path'], $uri['options']);
      return $breadcrumb;
    }
  }

}
