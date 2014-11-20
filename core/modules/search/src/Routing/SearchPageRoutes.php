<?php

/**
 * @file
 * Contains \Drupal\search\Routing\SearchPageRoutes.
 */

namespace Drupal\search\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\search\SearchPageRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides dynamic routes for search.
 */
class SearchPageRoutes implements ContainerInjectionInterface {

  /**
   * The search page repository.
   *
   * @var \Drupal\search\SearchPageRepositoryInterface
   */
  protected $searchPageRepository;

  /**
   * Constructs a new search route subscriber.
   *
   * @param \Drupal\search\SearchPageRepositoryInterface $search_page_repository
   *   The search page repository.
   */
  public function __construct(SearchPageRepositoryInterface $search_page_repository) {
    $this->searchPageRepository = $search_page_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('search.search_page_repository')
    );
  }

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes() {
    $routes = array();
    // @todo Decide if /search should continue to redirect to /search/$default,
    //   or just perform the appropriate search.
    if ($default_page = $this->searchPageRepository->getDefaultSearchPage()) {
      $routes['search.view'] = new Route(
        '/search',
        array(
          '_controller' => 'Drupal\search\Controller\SearchController::redirectSearchPage',
          '_title' => 'Search',
          'entity' => $default_page,
        ),
        array(
          '_entity_access' => 'entity.view',
          '_permission' => 'search content',
        ),
        array(
          'parameters' => array(
            'entity' => array(
              'type' => 'entity:search_page',
            ),
          ),
        )
      );
    }
    $active_pages = $this->searchPageRepository->getActiveSearchPages();
    foreach ($active_pages as $entity_id => $entity) {
      $routes["search.view_$entity_id"] = new Route(
        '/search/' . $entity->getPath(),
        array(
          '_controller' => 'Drupal\search\Controller\SearchController::view',
          '_title' => 'Search',
          'entity' => $entity_id,
        ),
        array(
          '_entity_access' => 'entity.view',
          '_permission' => 'search content',
        ),
        array(
          'parameters' => array(
            'entity' => array(
              'type' => 'entity:search_page',
            ),
          ),
        )
      );
    }
    return $routes;
  }

}
