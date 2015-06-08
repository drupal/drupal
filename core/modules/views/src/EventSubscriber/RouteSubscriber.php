<?php

/**
 * @file
 * Contains \Drupal\views\EventSubscriber\RouteSubscriber.
 */

namespace Drupal\views\EventSubscriber;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\views\Plugin\views\display\DisplayRouterInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Builds up the routes of all views.
 *
 * The general idea is to execute first all alter hooks to determine which
 * routes are overridden by views. This information is used to determine which
 * views have to be added by views in the dynamic event.
 *
 *
 * @see \Drupal\views\Plugin\views\display\PathPluginBase
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Stores a list of view,display IDs which haven't be used in the alter event.
   *
   * @var array
   */
  protected $viewsDisplayPairs;

  /**
   * The view storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $viewStorage;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
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
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  public function __construct(EntityManagerInterface $entity_manager, StateInterface $state) {
    $this->viewStorage = $entity_manager->getStorage('view');
    $this->state = $state;
  }

  /**
   * Resets the internal state of the route subscriber.
   */
  public function reset() {
    $this->viewsDisplayPairs = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::FINISHED] = array('routeRebuildFinished');
    // Ensure to run after the entity resolver subscriber
    // @see \Drupal\Core\EventSubscriber\EntityRouteAlterSubscriber
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -175];

    return $events;
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
        list($view_id, $display_id) = $data;
        $this->viewsDisplayPairs[] = $view_id . '.' . $display_id;
      }
      $this->viewsDisplayPairs = array_combine($this->viewsDisplayPairs, $this->viewsDisplayPairs);
    }
    return $this->viewsDisplayPairs;
  }

  /**
   * Returns a set of route objects.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   A route collection.
   */
  public function routes() {
    $collection = new RouteCollection();
    foreach ($this->getViewsDisplayIDsWithRoute() as $pair) {
      list($view_id, $display_id) = explode('.', $pair);
      $view = $this->viewStorage->load($view_id);
      // @todo This should have an executable factory injected.
      if (($view = $view->getExecutable()) && $view instanceof ViewExecutable) {
        if ($view->setDisplay($display_id) && $display = $view->displayHandlers->get($display_id)) {
          if ($display instanceof DisplayRouterInterface) {
            $this->viewRouteNames += (array) $display->collectRoutes($collection);
          }
        }
        $view->destroy();
      }
    }

    $this->state->set('views.view_route_names', $this->viewRouteNames);
    return $collection;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->getViewsDisplayIDsWithRoute() as $pair) {
      list($view_id, $display_id) = explode('.', $pair);
      $view = $this->viewStorage->load($view_id);
      // @todo This should have an executable factory injected.
      if (($view = $view->getExecutable()) && $view instanceof ViewExecutable) {
        if ($view->setDisplay($display_id) && $display = $view->displayHandlers->get($display_id)) {
          if ($display instanceof DisplayRouterInterface) {
            // If the display returns TRUE a route item was found, so it does not
            // have to be added.
            $view_route_names = $display->alterRoutes($collection);
            $this->viewRouteNames = $view_route_names + $this->viewRouteNames;
            foreach ($view_route_names as $id_display => $route_name) {
              $view_route_name = $this->viewsDisplayPairs[$id_display];
              unset($this->viewsDisplayPairs[$id_display]);
              $collection->remove("views.$view_route_name");
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
  public function routeRebuildFinished() {
    $this->reset();
    $this->state->set('views.view_route_names', $this->viewRouteNames);
  }

  /**
   * Returns all views/display combinations with routes.
   *
   * @see \Drupal\views\Views::getApplicableViews()
   */
  protected function getApplicableViews() {
    return Views::getApplicableViews('uses_route');
  }

}
