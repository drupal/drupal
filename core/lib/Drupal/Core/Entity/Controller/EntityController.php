<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Controller\EntityController.
 */

namespace Drupal\Core\Entity\Controller;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides generic entity title callbacks for use in routing.
 *
 * It provides:
 * - A view title callback.
 * - An edit title callback.
 * - A delete title callback.
 */
class EntityController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new EntityController.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct(EntityManagerInterface $entity_manager, TranslationInterface $string_translation) {
    $this->entityManager = $entity_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * Provides a generic title callback for a single entity.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityInterface $_entity
   *   (optional) An entity, passed in directly from the request attributes.
   *
   * @return string
   *   The title for the entity view page.
   */
  public function title(RouteMatchInterface $route_match, EntityInterface $_entity = NULL) {
    if ($entity = $this->doGetEntity($route_match, $_entity)) {
      return $entity->label();
    }
  }

  /**
   * Provides a generic edit title callback.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityInterface $_entity
   *   (optional) An entity, passed in directly from the request attributes.
   *
   * @return string
   *   The title for the entity edit page.
   */
  public function editTitle(RouteMatchInterface $route_match, EntityInterface $_entity = NULL) {
    if ($entity = $this->doGetEntity($route_match, $_entity)) {
      return $this->t('Edit %label', ['%label' => $entity->label()]);
    }
  }

  /**
   * Provides a generic delete title callback.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityInterface $_entity
   *   (optional) An entity, passed in directly from the request attributes, and
   *   set in \Drupal\Core\Entity\Enhancer\EntityRouteEnhancer.
   *
   * @return string
   *   The title for the delete entity page.
   */
  public function deleteTitle(RouteMatchInterface $route_match, EntityInterface $_entity = NULL) {
    if ($entity = $this->doGetEntity($route_match, $_entity)) {
      return $this->t('Delete %label', ['%label' => $entity->label()]);
    }
  }

  /**
   * Determines the entity.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityInterface $_entity
   *   (optional) The entity, set in
   *   \Drupal\Core\Entity\Enhancer\EntityRouteEnhancer.
   *
   * @return \Drupal\Core\Entity\EntityInterface|NULL
   *   The entity, if it is passed in directly or if the first parameter of the
   *   active route is an entity; otherwise, NULL.
   */
  protected function doGetEntity(RouteMatchInterface $route_match, EntityInterface $_entity = NULL) {
    if ($_entity) {
      $entity = $_entity;
    }
    else {
      // Let's look up in the route object for the name of upcasted values.
      foreach ($route_match->getParameters() as $parameter) {
        if ($parameter instanceof EntityInterface) {
          $entity = $parameter;
          break;
        }
      }
    }
    if ($entity) {
      return $this->entityManager->getTranslationFromContext($entity);
    }
  }

}
