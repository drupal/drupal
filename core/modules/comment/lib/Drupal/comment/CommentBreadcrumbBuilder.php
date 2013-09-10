<?php

/**
 * @file
 * Contains \Drupal\comment\CommentBreadcrumbBuilder.
 */

namespace Drupal\comment;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\StringTranslation\TranslationManager;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Class to define the comment breadcrumb builder.
 */
class CommentBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * The translation manager service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translation;

  /**
   * Stores the Entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Constructs a CommentBreadcrumbBuilder object.
   *
   * @param \Drupal\Core\StringTranslation\TranslationManager $translation
   *   The translation manager.
   * @param \Drupal\Core\Entity\EntityManager
   *   The entity manager.
   */
  public function __construct(TranslationManager $translation, EntityManager $entity_manager) {
    $this->translation = $translation;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $attributes) {
    if (!empty($attributes[RouteObjectInterface::ROUTE_NAME]) && $attributes[RouteObjectInterface::ROUTE_NAME] == 'comment_reply'
      && isset($attributes['entity_type'])
      && isset($attributes['entity_id'])
      && isset($attributes['field_name'])
      ) {
      $breadcrumb[] = l(t('Home'), NULL);
      // @todo clean-up.
      $entity = entity_load($attributes['entity_type'], $attributes['entity_id']);
      $uri = $entity->uri();
      $breadcrumb[] = l($entity->label(), $uri['path'], $uri['options']);
    }

    if (!empty($breadcrumb)) {
      return $breadcrumb;
    }
  }

}
