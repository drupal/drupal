<?php

/**
 * @file
 * Contains \Drupal\comment\CommentBreadcrumbBuilder.
 */

namespace Drupal\comment;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Entity\EntityManager;

/**
 * Class to define the comment breadcrumb builder.
 */
class CommentBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * Stores the Entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Constructs a new CommentBreadcrumbBuilder.
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
    // @todo This only works for legacy routes. Once
    // comment/reply/%/%comment_entity_reply/% is converted to the new router
    // this code will need to be updated.
    if (isset($attributes['_drupal_menu_item'])) {
      $item = $attributes['_drupal_menu_item'];
      if ($item['path'] == 'comment/reply/%/%/%') {
        $entity = $item['map'][3];
        // Load the object in case of missing wildcard loaders.
        if (!is_object($entity)) {
          $entities = $this->entityManager->getStorageController($item['map'][2], array($entity));
          $entity = reset($entities);
        }
        $breadcrumb[] = l(t('Home'), NULL);
        $uri = $entity->uri();
        $breadcrumb[] = l($entity->label(), $uri['path'], $uri['options']);
      }
    }

    if (!empty($breadcrumb)) {
      return $breadcrumb;
    }
  }

}
