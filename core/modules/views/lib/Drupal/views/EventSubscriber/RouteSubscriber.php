<?php

/**
 * @file
 * Contains \Drupal\views\EventSubscriber\RouteSubscriber.
 */

namespace Drupal\views\EventSubscriber;

use Drupal\Component\Utility\MapArray;
use Drupal\Core\DestructableInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\views\Plugin\views\display\DisplayRouterInterface;
use Drupal\views\ViewExecutable;
use Symfony\Component\Routing\RouteCollection;

/**
 * Builds up the routes of all views.
 *
 * The general idea is to execute first all alter hooks to determine which
 * routes are overridden by views. This information is used to determine which
 * views have to be added by views in the dynamic event.
 *
 * @see \Drupal\views\Plugin\views\display\PathPluginBase
 */
class RouteSubscriber extends RouteSubscriberBase implements DestructableInterface {

  /**
   * Stores a list of view,display IDs which haven't be used in the alter event.
   *
   * @var array
   */
  protected $viewsDisplayPairs;

  /**
   * The view storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $viewStorageController;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $state;

  /**
   * Stores an array of route names keyed by view_id.display_id.
   *
   * @var array
   */
  protected $viewRouteNames = array();

  /**
   * Constructs a \Drupal\views\EventSubscriber\RouteSubscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreInterface $state
   *   The state key value store.
   */
  public function __construct(EntityManagerInterface $entity_manager, KeyValueStoreInterface $state) {
    $this->viewStorageController = $entity_manager->getStorageController('view');
    $this->state = $state;
  }

  /**
   * Resets the internal state of the route subscriber.
   */
  public function reset() {
    $this->viewsDisplayPairs = NULL;
  }

  /**
   * Gets all the views and display IDs using a route.
   */
  protected function getViewsDisplayIDsWithRoute() {
    if (!isset($this->viewsDisplayPairs)) {
      $this->viewsDisplayPairs = array();

      // @todo Convert this method to some service.
      $views = $this->getApplicableViews();
      foreach ($views as $data) {
        list($view, $display_id) = $data;
        $id = $view->storage->id();
        $this->viewsDisplayPairs[] = $id . '.' . $display_id;
      }
      $this->viewsDisplayPairs = MapArray::copyValuesToKeys($this->viewsDisplayPairs);
    }
    return $this->viewsDisplayPairs;
  }

  /**
   * {@inheritdoc}
   */
  protected function routes(RouteCollection $collection) {
    foreach ($this->getViewsDisplayIDsWithRoute() as $pair) {
      list($view_id, $display_id) = explode('.', $pair);
      $view = $this->viewStorageController->load($view_id);
      // @todo This should have an executable factory injected.
      if (($view = $view->getExecutable()) && $view instanceof ViewExecutable) {
        if ($view->setDisplay($display_id) && $display = $view->displayHandlers->get($display_id)) {
          if ($display instanceof DisplayRouterInterface) {
            $view_route_names = (array) $display->collectRoutes($collection);

            $this->viewRouteNames += $view_route_names;
          }
        }
        $view->destroy();
      }
    }

    $this->state->set('views.view_route_names', $this->viewRouteNames);
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection, $module) {
    foreach ($this->getViewsDisplayIDsWithRoute() as $pair) {
      list($view_id, $display_id) = explode('.', $pair);
      $view = $this->viewStorageController->load($view_id);
      // @todo This should have an executable factory injected.
      if (($view = $view->getExecutable()) && $view instanceof ViewExecutable) {
        if ($view->setDisplay($display_id) && $display = $view->displayHandlers->get($display_id)) {
          if ($display instanceof DisplayRouterInterface) {
            // If the display returns TRUE a route item was found, so it does not
            // have to be added.
            $view_route_names = $display->alterRoutes($collection);
            $this->viewRouteNames += $view_route_names;
            foreach ($view_route_names as $id_display => $route_name) {
              unset($this->viewsDisplayPairs[$id_display]);
            }
          }
        }
        $view->destroy();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {
    $this->state->set('views.view_route_names', $this->viewRouteNames);
  }

  /**
   * Returns all views/display combinations with routes.
   *
   * @see views_get_applicable_views()
   */
  protected function getApplicableViews() {
    return views_get_applicable_views('uses_route');
  }

}
