<?php

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
    $routes = [];
    // @todo Decide if /search should continue to redirect to /search/$default,
    //   or just perform the appropriate search.
    if ($default_page = $this->searchPageRepository->getDefaultSearchPage()) {
      $routes['search.view'] = new Route(
        '/search',
        [
          '_controller' => 'Drupal\search\Controller\SearchController::redirectSearchPage',
          '_title' => 'Search',
          'entity' => $default_page,
        ],
        [
          '_entity_access' => 'entity.view',
          '_permission' => 'search content',
        ],
        [
          'parameters' => [
            'entity' => [
              'type' => 'entity:search_page',
            ],
          ],
        ]
      );
    }
    $active_pages = $this->searchPageRepository->getActiveSearchPages();
    foreach ($active_pages as $entity_id => $entity) {
      $routes["search.view_$entity_id"] = new Route(
        '/search/' . $entity->getPath(),
        [
          '_controller' => 'Drupal\search\Controller\SearchController::view',
          '_title' => 'Search',
          'entity' => $entity_id,
        ],
        [
          '_entity_access' => 'entity.view',
          '_permission' => 'search content',
        ],
        [
          'parameters' => [
            'entity' => [
              'type' => 'entity:search_page',
            ],
          ],
        ]
      );

      $routes["search.help_$entity_id"] = new Route(
        '/search/' . $entity->getPath() . '/help',
        [
          '_controller' => 'Drupal\search\Controller\SearchController::searchHelp',
          '_title' => 'Search help',
          'entity' => $entity_id,
        ],
        [
          '_entity_access' => 'entity.view',
          '_permission' => 'search content',
        ],
        [
          'parameters' => [
            'entity' => [
              'type' => 'entity:search_page',
            ],
          ],
        ]
      );
      if ($entity->getPlugin()->usesAdminTheme()) {
        $routes["search.view_$entity_id"]->setOption('_admin_route', TRUE);
        $routes["search.help_$entity_id"]->setOption('_admin_route', TRUE);
      }
    }
    return $routes;
  }

}
