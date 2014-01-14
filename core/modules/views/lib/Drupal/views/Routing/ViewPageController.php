<?php

/**
 * @file
 * Contains \Drupal\views\Routing\ViewPageController.
 */

namespace Drupal\views\Routing;

use Drupal\Component\Utility\String;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\views\ViewExecutableFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Defines a page controller to execute and render a view.
 */
class ViewPageController implements ContainerInjectionInterface {

  /**
   * The entity storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $storageController;

  /**
   * The view executable factory.
   *
   * @var \Drupal\views\ViewExecutableFactory
   */
  protected $executableFactory;

  /**
   * Constructs a ViewPageController object.
   *
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage_controller
   *   The entity storage controller.
   * @param \Drupal\views\ViewExecutableFactory $executable_factory
   *   The view executable factory
   */
  public function __construct(EntityStorageControllerInterface $storage_controller, ViewExecutableFactory $executable_factory) {
    $this->storageController = $storage_controller;
    $this->executableFactory = $executable_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorageController('view'),
      $container->get('views.executable')
    );
  }

  /**
   * Handles a response for a view.
   */
  public function handle(Request $request) {
    $view_id = $request->attributes->get('view_id');
    $display_id = $request->attributes->get('display_id');

    $entity = $this->storageController->load($view_id);
    if (empty($entity)) {
      throw new NotFoundHttpException(String::format('Page controller for view %id requested, but view was not found.', array('%id' => $view_id)));
    }
    $view = $this->executableFactory->get($entity);
    $view->setRequest($request);
    $view->setDisplay($display_id);
    $view->initHandlers();

    $args = array();
    $map = $request->attributes->get('_view_argument_map', array());
    $arguments_length = count($view->argument);
    for ($argument_index = 0; $argument_index < $arguments_length; $argument_index++) {
      // Allow parameters be pulled from the request.
      // The map stores the actual name of the parameter in the request. Views
      // which override existing controller, use for example 'node' instead of
      // arg_nid as name.
      $attribute = 'arg_' . $argument_index;
      if (isset($map[$attribute])) {
        $attribute = $map[$attribute];

        // First try to get from the original values then on the not converted
        // ones.
        if ($request->attributes->has('_raw_variables')) {
          $arg = $request->attributes->get('_raw_variables')->get($attribute);
        }
        else {
          $arg = $request->attributes->get($attribute);
        }
      }
      else {
        $arg = $request->attributes->get($attribute);
      }

      if (isset($arg)) {
        $args[] = $arg;
      }
    }

    return $view->executeDisplay($display_id, $args);
  }

}
