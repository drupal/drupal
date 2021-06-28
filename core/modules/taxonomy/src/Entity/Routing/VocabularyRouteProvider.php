<?php

namespace Drupal\taxonomy\Entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

class VocabularyRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {

    $collection = parent::getRoutes($entity_type);

    if ($reset_page_route = $this->getResetPageRoute($entity_type)) {
      $collection->add("entity.taxonomy_vocabulary.reset_form", $reset_page_route);
    }

    if ($overview_page_route = $this->getOverviewPageRoute($entity_type)) {
      $collection->add("entity.taxonomy_vocabulary.overview_form", $overview_page_route);
    }

    return $collection;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getCollectionRoute($entity_type)) {
      $route->setRequirement('_permission', 'access taxonomy overview+administer taxonomy');
      return $route;
    }
  }

  /**
   * Gets the reset page route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getResetPageRoute(EntityTypeInterface $entity_type) {
    $route = new Route('/admin/structure/taxonomy/manage/{taxonomy_vocabulary}/reset');
    $route->setDefault('_entity_form', 'taxonomy_vocabulary.reset');
    $route->setDefault('_title', 'Reset');
    $route->setRequirement('_permission', $entity_type->getAdminPermission());
    $route->setOption('_admin_route', TRUE);
    $route->setOption('parameters', [
      'taxonomy_vocabulary' => [
        'with_config_overrides' => TRUE,
      ],
    ]);

    return $route;
  }

  /**
   * Gets the overview page route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getOverviewPageRoute(EntityTypeInterface $entity_type) {
    $route = new Route('/admin/structure/taxonomy/manage/{taxonomy_vocabulary}/overview');
    $route->setDefault('_title_callback', '\Drupal\Core\Entity\Controller\EntityController::title');
    $route->setDefault('_form', 'Drupal\taxonomy\Form\OverviewTerms');
    $route->setRequirement('_entity_access', 'taxonomy_vocabulary.access taxonomy overview');
    $route->setOption('_admin_route', TRUE);
    $route->setOption('parameters', [
      'taxonomy_vocabulary' => [
        'with_config_overrides' => TRUE,
      ],
    ]);

    return $route;
  }

}
