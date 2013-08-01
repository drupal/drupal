<?php

/**
 * @file
 * Contains \Drupal\views\Controller\ViewAjaxController.
 */

namespace Drupal\views\Controller;

use Drupal\Component\Utility\Url;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\views\Ajax\ScrollTopCommand;
use Drupal\views\Ajax\ViewAjaxResponse;
use Drupal\views\ViewExecutableFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Defines a controller to load a view via AJAX.
 */
class ViewAjaxController implements ControllerInterface {

  /**
   * The entity storage controller for views.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $storageController;

  /**
   * The factory to load a view executable with.
   *
   * @var \Drupal\views\ViewExecutableFactory
   */
  protected $executableFactory;

  /**
   * Constructs a ViewAjaxController object.
   *
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage_controller
   *   The entity storage controller for views.
   * @param \Drupal\views\ViewExecutableFactory $executable_factory
   *   The factory to load a view executable with.
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
      $container->get('plugin.manager.entity')->getStorageController('view'),
      $container->get('views.executable')
    );
  }

  /**
   * Loads and renders a view via AJAX.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *  The current request object.
   *
   * @return \Drupal\views\Ajax\ViewAjaxResponse
   *  The view response as ajax response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when the view was not found.
   */
  public function ajaxView(Request $request) {
    $name = $request->request->get('view_name');
    $display_id = $request->request->get('view_display_id');
    if (isset($name) && isset($display_id)) {
      $args = $request->request->get('view_args');
      $args = isset($args) && $args !== '' ? explode('/', $args) : array();
      $path = $request->request->get('view_path');
      $dom_id = $request->request->get('view_dom_id');
      $dom_id = isset($dom_id) ? preg_replace('/[^a-zA-Z0-9_-]+/', '-', $dom_id) : NULL;
      $pager_element = $request->request->get('pager_element');
      $pager_element = isset($pager_element) ? intval($pager_element) : NULL;

      $response = new ViewAjaxResponse();

      // Remove all of this stuff from the query of the request so it doesn't
      // end up in pagers and tablesort URLs.
      foreach (array('view_name', 'view_display_id', 'view_args', 'view_path', 'view_dom_id', 'pager_element', 'view_base_path', 'ajax_html_ids', 'ajax_page_state') as $key) {
        $request->query->remove($key);
        $request->request->remove($key);
      }

      // Load the view.
      $result = $this->storageController->load(array($name));
      if (!$entity = reset($result)) {
        throw new NotFoundHttpException();
      }
      $view = $this->executableFactory->get($entity);
      if ($view && $view->access($display_id)) {
        // Fix the current path for paging.
        if (!empty($path)) {
          $request->attributes->set('_system_path', $path);
        }

        // Add all $_POST data, because AJAX is always a post and many things,
        // such as tablesorts, exposed filters and paging assume $_GET.
        $request_all = $request->request->all();
        $query_all = $request->query->all();
        $request->query->replace($request_all + $query_all);

        // Overwrite the destination.
        // @see drupal_get_destination()
        $origin_destination = $path;
        $query = Url::buildQuery($request->query->all());
        if ($query != '') {
          $origin_destination .= '?' . $query;
        }
        $destination = &drupal_static('drupal_get_destination');
        $destination = array('destination' => $origin_destination);

        // Override the display's pager_element with the one actually used.
        if (isset($pager_element)) {
          $response->addCommand(new ScrollTopCommand(".view-dom-id-$dom_id"));
          $view->displayHandlers->get($display_id)->setOption('pager_element', $pager_element);
        }
        // Reuse the same DOM id so it matches that in Drupal.settings.
        $view->dom_id = $dom_id;

        $preview = $view->preview($display_id, $args);
        $response->addCommand(new ReplaceCommand(".view-dom-id-$dom_id", drupal_render($preview)));
      }
      return $response;
    }
    else {
      throw new NotFoundHttpException();
    }
  }

}
