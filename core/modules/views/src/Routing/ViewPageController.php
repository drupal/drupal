<?php

/**
 * @file
 * Contains \Drupal\views\Routing\ViewPageController.
 */

namespace Drupal\views\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\views\Plugin\views\display\Page;
use Drupal\views\ViewExecutableFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a page controller to execute and render a view.
 */
class ViewPageController implements ContainerInjectionInterface {

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The view executable factory.
   *
   * @var \Drupal\views\ViewExecutableFactory
   */
  protected $executableFactory;

  /**
   * Constructs a ViewPageController object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage.
   * @param \Drupal\views\ViewExecutableFactory $executable_factory
   *   The view executable factory
   */
  public function __construct(EntityStorageInterface $storage, ViewExecutableFactory $executable_factory) {
    $this->storage = $storage;
    $this->executableFactory = $executable_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('view'),
      $container->get('views.executable')
    );
  }

  /**
   * Handler a response for a given view and display.
   *
   * @param string $view_id
   *   The ID of the view
   * @param string $display_id
   *   The ID of the display.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @return null|void
   */
  public function handle($view_id, $display_id, RouteMatchInterface $route_match) {
    $args = array();
    $route = $route_match->getRouteObject();
    $map = $route->hasOption('_view_argument_map') ? $route->getOption('_view_argument_map') : array();

    foreach ($map as $attribute => $parameter_name) {
      // Allow parameters be pulled from the request.
      // The map stores the actual name of the parameter in the request. Views
      // which override existing controller, use for example 'node' instead of
      // arg_nid as name.
      if (isset($map[$attribute])) {
        $attribute = $map[$attribute];
      }
      if ($arg = $route_match->getRawParameter($attribute)) {
      }
      else {
        $arg = $route_match->getParameter($attribute);
      }

      if (isset($arg)) {
        $args[] = $arg;
      }
    }

    /** @var \Drupal\views\Plugin\views\display\DisplayPluginBase $class */
    $class = $route->getOption('_view_display_plugin_class');
    if ($route->getOption('returns_response')) {
      /** @var \Drupal\views\Plugin\views\display\ResponseDisplayPluginInterface $class */
      return $class::buildResponse($view_id, $display_id, $args);
    }
    else {
      $build = $class::buildBasicRenderable($view_id, $display_id, $args, $route);
      Page::setPageRenderArray($build);

      return $build;
    }
  }

}
