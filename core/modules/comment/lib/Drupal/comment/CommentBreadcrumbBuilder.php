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
    if (isset($attributes[RouteObjectInterface::ROUTE_NAME]) && $attributes[RouteObjectInterface::ROUTE_NAME] == 'comment.reply'
      && isset($attributes['entity_type'])
      && isset($attributes['entity_id'])
      && isset($attributes['field_name'])
      ) {
      $breadcrumb[] = l($this->t('Home'), NULL);
      $entity = $this->entityManager
        ->getStorageController($attributes['entity_type'])
        ->load($attributes['entity_id']);
      $uri = $entity->uri();
      $breadcrumb[] = l($entity->label(), $uri['path'], $uri['options']);
      return $breadcrumb;
    }
  }

  /**
   * Translates a string to the current language or to a given language.
   *
   * See the t() documentation for details.
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->translation->translate($string, $args, $options);
  }

}
