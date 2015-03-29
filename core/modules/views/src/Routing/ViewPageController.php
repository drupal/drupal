<?php

/**
 * @file
 * Contains \Drupal\views\Routing\ViewPageController.
 */

namespace Drupal\views\Routing;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\views\ViewExecutableFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @return null|void
   */
  public function handle($view_id, $display_id, Request $request, RouteMatchInterface $route_match) {
    $entity = $this->storage->load($view_id);
    if (empty($entity)) {
      throw new NotFoundHttpException(SafeMarkup::format('Page controller for view %id requested, but view was not found.', array('%id' => $view_id)));
    }
    $view = $this->executableFactory->get($entity);
    $view->setRequest($request);
    $view->setDisplay($display_id);
    $view->initHandlers();

    $args = array();
    $map = $route_match->getRouteObject()->getOption('_view_argument_map', array());
    $arguments_length = count($view->argument);
    for ($argument_index = 0; $argument_index < $arguments_length; $argument_index++) {
      // Allow parameters be pulled from the request.
      // The map stores the actual name of the parameter in the request. Views
      // which override existing controller, use for example 'node' instead of
      // arg_nid as name.
      $attribute = 'arg_' . $argument_index;
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

    $plugin_definition = $view->display_handler->getPluginDefinition();
    if (!empty($plugin_definition['returns_response'])) {
      return $view->executeDisplay($display_id, $args);
    }
    else {
      return $view->buildRenderable($display_id, $args);
    }
  }

}
