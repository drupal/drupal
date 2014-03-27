<?php

/**
 * @file
 * Contains \Drupal\views\Controller\ViewAjaxController.
 */

namespace Drupal\views\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\views\Ajax\ScrollTopCommand;
use Drupal\views\Ajax\ViewAjaxResponse;
use Drupal\views\ViewExecutableFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Defines a controller to load a view via AJAX.
 */
class ViewAjaxController implements ContainerInjectionInterface {

  /**
   * The entity storage for views.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The factory to load a view executable with.
   *
   * @var \Drupal\views\ViewExecutableFactory
   */
  protected $executableFactory;

  /**
   * Constructs a ViewAjaxController object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage for views.
   * @param \Drupal\views\ViewExecutableFactory $executable_factory
   *   The factory to load a view executable with.
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
      if (!$entity = $this->storage->load($name)) {
        throw new NotFoundHttpException();
      }
      $view = $this->executableFactory->get($entity);
      if ($view && $view->access($display_id)) {
        $response->setView($view);
        // Fix the current path for paging.
        if (!empty($path)) {
          $request->attributes->set('_system_path', $path);
        }

        // Add all POST data, because AJAX is always a post and many things,
        // such as tablesorts, exposed filters and paging assume GET.
        $request_all = $request->request->all();
        $query_all = $request->query->all();
        $request->query->replace($request_all + $query_all);

        // Overwrite the destination.
        // @see drupal_get_destination()
        $origin_destination = $path;
        $query = UrlHelper::buildQuery($request->query->all());
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
        // Reuse the same DOM id so it matches that in drupalSettings.
        $view->dom_id = $dom_id;

        $preview = $view->preview($display_id, $args);
        $response->addCommand(new ReplaceCommand(".view-dom-id-$dom_id", $this->drupalRender($preview)));
        return $response;
      }
      else {
        throw new AccessDeniedHttpException();
      }
    }
    else {
      throw new NotFoundHttpException();
    }
  }

  /**
   * Wraps drupal_render.
   *
   * @param array $elements
   *   The structured array describing the data to be rendered.
   *
   * @return string
   *   The rendered HTML.
   *
   * @todo Remove once drupal_render is converted to autoloadable code.
   * @see https://drupal.org/node/2171071
   */
  protected function drupalRender(array $elements) {
    return drupal_render($elements);
  }

}
