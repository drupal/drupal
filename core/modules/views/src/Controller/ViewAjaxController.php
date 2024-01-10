<?php

namespace Drupal\views\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Ajax\ScrollTopCommand;
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
   * Parameters that should be filtered and ignored inside ajax requests.
   */
  public const FILTERED_QUERY_PARAMETERS = [
    'view_name',
    'view_display_id',
    'view_args',
    'view_path',
    'view_dom_id',
    'pager_element',
    'view_base_path',
    'ajax_page_state',
    '_drupal_ajax',
    FormBuilderInterface::AJAX_FORM_REQUEST,
    MainContentViewSubscriber::WRAPPER_FORMAT,
  ];

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
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The redirect destination.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs a ViewAjaxController object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage for views.
   * @param \Drupal\views\ViewExecutableFactory $executable_factory
   *   The factory to load a view executable with.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination.
   */
  public function __construct(EntityStorageInterface $storage, ViewExecutableFactory $executable_factory, RendererInterface $renderer, CurrentPathStack $current_path, RedirectDestinationInterface $redirect_destination) {
    $this->storage = $storage;
    $this->executableFactory = $executable_factory;
    $this->renderer = $renderer;
    $this->currentPath = $current_path;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('view'),
      $container->get('views.executable'),
      $container->get('renderer'),
      $container->get('path.current'),
      $container->get('redirect.destination')
    );
  }

  /**
   * Loads and renders a view via AJAX.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Drupal\views\Ajax\ViewAjaxResponse
   *   The view response as ajax response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when the view was not found.
   */
  public function ajaxView(Request $request) {
    $name = $request->get('view_name');
    $display_id = $request->get('view_display_id');
    if (isset($name) && isset($display_id)) {
      $args = $request->get('view_args', '');
      $args = $args !== '' ? explode('/', Html::decodeEntities($args)) : [];

      // Arguments can be empty, make sure they are passed on as NULL so that
      // argument validation is not triggered.
      $args = array_map(function ($arg) {
        return ($arg == '' ? NULL : $arg);
      }, $args);

      $path = $request->get('view_path');
      $dom_id = $request->get('view_dom_id');
      $dom_id = isset($dom_id) ? preg_replace('/[^a-zA-Z0-9_-]+/', '-', $dom_id) : NULL;
      $pager_element = $request->get('pager_element');
      $pager_element = isset($pager_element) ? intval($pager_element) : NULL;

      $response = new ViewAjaxResponse();

      // Remove all of this stuff from the query of the request so it doesn't
      // end up in pagers and tablesort URLs. Additionally we need to preserve
      // ajax_page_state and add it back after the request has been processed so
      // the related listener can behave correctly.
      // @todo Remove this parsing once these are removed from the request in
      //   https://www.drupal.org/node/2504709.
      $existing_page_state = $request->get('ajax_page_state');
      foreach (self::FILTERED_QUERY_PARAMETERS as $key) {
        $request->query->remove($key);
        $request->request->remove($key);
      }

      // Load the view.
      if (!$entity = $this->storage->load($name)) {
        throw new NotFoundHttpException();
      }
      $view = $this->executableFactory->get($entity);
      if ($view && $view->access($display_id) && $view->setDisplay($display_id) && $view->display_handler->ajaxEnabled()) {
        $response->setView($view);
        // Fix the current path for paging.
        if (!empty($path)) {
          $this->currentPath->setPath('/' . ltrim($path, '/'), $request);
        }

        // Create a clone of the request object to avoid mutating the request
        // object stored in the request stack.
        $request_clone = clone $request;

        // Add all POST data, because AJAX is sometimes a POST and many things,
        // such as tablesorts, exposed filters and paging assume GET.
        $param_union = $request_clone->request->all() + $request_clone->query->all();
        $request_clone->query->replace($param_union);

        // Overwrite the destination.
        // @see the redirect.destination service.
        $origin_destination = $request_clone->getBasePath() . '/' . ltrim($path ?? '/', '/');

        $used_query_parameters = $request_clone->query->all();
        $query = UrlHelper::buildQuery($used_query_parameters);
        if ($query != '') {
          $origin_destination .= '?' . $query;
        }
        $this->redirectDestination->set($origin_destination);

        // Override the display's pager_element with the one actually used.
        if (isset($pager_element)) {
          $response->addCommand(new ScrollTopCommand(".js-view-dom-id-$dom_id"));
          $view->displayHandlers->get($display_id)->setOption('pager_element', $pager_element);
        }
        // Reuse the same DOM id so it matches that in drupalSettings.
        $view->dom_id = $dom_id;

        // Populate request attributes temporarily with ajax_page_state theme
        // and theme_token for theme negotiation.
        $theme_keys = [
          'theme' => TRUE,
          'theme_token' => TRUE,
        ];
        if (is_array($existing_page_state) &&
            ($temp_attributes = array_intersect_key($existing_page_state, $theme_keys))) {
          $request->attributes->set('ajax_page_state', $temp_attributes);
        }
        $preview = $view->preview($display_id, $args);
        $request->attributes->remove('ajax_page_state');
        $response->addCommand(new ReplaceCommand(".js-view-dom-id-$dom_id", $preview));
        $response->addCommand(new PrependCommand(".js-view-dom-id-$dom_id", ['#type' => 'status_messages']));
        $request->query->set('ajax_page_state', $existing_page_state);

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

}
