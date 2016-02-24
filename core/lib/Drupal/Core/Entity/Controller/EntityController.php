<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Controller\EntityController.
 */

namespace Drupal\Core\Entity\Controller;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
 * - An add title callback for entities without bundles.
 * - An add title callback for entities with bundles.
 * - A view title callback.
 * - An edit title callback.
 * - A delete title callback.
 */
class EntityController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a new EntityController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityRepositoryInterface $entity_repository, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityRepository = $entity_repository;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity.repository'),
      $container->get('string_translation')
    );
  }

  /**
   * Provides a generic add title callback for entities without bundles.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string
   *    The title for the entity add page.
   */
  public function addTitle($entity_type_id) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    return $this->t('Add @entity-type', ['@entity-type' => $entity_type->getLowercaseLabel()]);
  }

  /**
   * Provides a generic add title callback for entities with bundles.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle_parameter
   *   The name of the route parameter that holds the bundle.
   *
   * @return string
   *    The title for the entity add page, if the bundle was found.
   */
  public function addBundleTitle(RouteMatchInterface $route_match, $entity_type_id, $bundle_parameter) {
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
    // If the entity has bundle entities, the parameter might have been upcasted
    // so fetch the raw parameter.
    $bundle = $route_match->getRawParameter($bundle_parameter);
    if ((count($bundles) > 1) && isset($bundles[$bundle])) {
      return $this->t('Add @bundle', ['@bundle' => $bundles[$bundle]['label']]);
    }
    // If the entity supports bundles generally, but only has a single bundle,
    // the bundle is probably something like 'Default' so that it preferable to
    // use the entity type label.
    else {
      return $this->addTitle($entity_type_id);
    }
  }

  /**
   * Provides a generic title callback for a single entity.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityInterface $_entity
   *   (optional) An entity, passed in directly from the request attributes.
   *
   * @return string|null
   *   The title for the entity view page, if an entity was found.
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
   * @return string|null
   *   The title for the entity edit page, if an entity was found.
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
   *   The title for the entity delete page.
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
    if (isset($entity)) {
      return $this->entityRepository->getTranslationFromContext($entity);
    }
  }

}
