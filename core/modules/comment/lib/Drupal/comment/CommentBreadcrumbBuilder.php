<?php

/**
 * @file
 * Contains \Drupal\comment\CommentBreadcrumbBuilder.
 */

namespace Drupal\comment;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

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
  public function applies(array $attributes) {
    return isset($attributes[RouteObjectInterface::ROUTE_NAME]) && $attributes[RouteObjectInterface::ROUTE_NAME] == 'comment.reply'
    && isset($attributes['entity_type'])
    && isset($attributes['entity_id'])
    && isset($attributes['field_name']);
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $attributes) {
    $breadcrumb = array();

    $breadcrumb[] = $this->l($this->t('Home'), '<front>');
    $entity = $this->entityManager
      ->getStorageController($attributes['entity_type'])
      ->load($attributes['entity_id']);
    $uri = $entity->urlInfo();
    $breadcrumb[] = \Drupal::l($entity->label(), $uri['route_name'], $uri['route_parameters'], $uri['options']);
    return $breadcrumb;
  }

}
