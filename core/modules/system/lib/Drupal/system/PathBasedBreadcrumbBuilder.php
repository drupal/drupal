<?php

/**
 * @file
 * Contains \Drupal\system\PathBasedBreadcrumbBuilder.
 */

namespace Drupal\system;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Access\AccessManager;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Class to define the menu_link breadcrumb builder.
 */
class PathBasedBreadcrumbBuilder extends BreadcrumbBuilderBase {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The menu link access service.
   *
   * @var \Drupal\Core\Access\AccessManager
   */
  protected $accessManager;

  /**
   * The menu storage controller.
   *
   * @var \Drupal\Core\Config\Entity\ConfigStorageControllerInterface
   */
  protected $menuStorage;

  /**
   * The dynamic router service.
   *
   * @var \Symfony\Component\Routing\Matcher\RequestMatcherInterface
   */
  protected $router;

  /**
   * The dynamic router service.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * Site config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * Constructs the PathBasedBreadcrumbBuilder.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current Request object.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Access\AccessManager $access_manager
   *   The menu link access service.
   * @param \Symfony\Component\Routing\Matcher\RequestMatcherInterface $router
   *   The dynamic router service.
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $path_processor
   *   The inbound path processor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver service.
   */
  public function __construct(Request $request, EntityManagerInterface $entity_manager, AccessManager $access_manager, RequestMatcherInterface $router, InboundPathProcessorInterface $path_processor, ConfigFactoryInterface $config_factory, TitleResolverInterface $title_resolver) {
    $this->request = $request;
    $this->accessManager = $access_manager;
    $this->menuStorage = $entity_manager->getStorageController('menu');
    $this->router = $router;
    $this->pathProcessor = $path_processor;
    $this->config = $config_factory->get('system.site');
    $this->titleResolver = $title_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(array $attributes) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $attributes) {
    $links = array();

    // General path-based breadcrumbs. Use the actual request path, prior to
    // resolving path aliases, so the breadcrumb can be defined by simply
    // creating a hierarchy of path aliases.
    $path = trim($this->request->getPathInfo(), '/');
    $path_elements = explode('/', $path);
    $exclude = array();
    // Don't show a link to the front-page path.
    $front = $this->config->get('page.front');
    $exclude[$front] = TRUE;
    // /user is just a redirect, so skip it.
    // @todo Find a better way to deal with /user.
    $exclude['user'] = TRUE;
    while (count($path_elements) > 1) {
      array_pop($path_elements);
      // Copy the path elements for up-casting.
      $route_request = $this->getRequestForPath(implode('/', $path_elements), $exclude);
      if ($route_request) {
        if (!$route_request->attributes->get('_legacy')) {
          $route_name = $route_request->attributes->get(RouteObjectInterface::ROUTE_NAME);
          // Note that the parameters don't really matter here since we're
          // passing in the request which already has the upcast attributes.
          $parameters = array();
          $access = $this->accessManager->checkNamedRoute($route_name, $parameters, \Drupal::currentUser(), $route_request);
          if ($access) {
            $title = $this->titleResolver->getTitle($route_request, $route_request->attributes->get(RouteObjectInterface::ROUTE_OBJECT));
          }
        }
        if ($access) {
          if (!$title) {
            // Fallback to using the raw path component as the title if the
            // route is missing a _title or _title_callback attribute.
            $title = str_replace(array('-', '_'), ' ', Unicode::ucfirst(end($path_elements)));
          }
          // @todo Replace with a #type => link render element so that the alter
          // hook can work with the actual data.
          $links[] = l($title, $route_request->attributes->get('_system_path'));
        }
      }

    }
    if ($path && $path != $front) {
      // Add the Home link, except for the front page.
      $links[] = $this->l($this->t('Home'), '<front>');
    }
    return array_reverse($links);
  }

  /**
   * Matches a path in the router.
   *
   * @param string $path
   *   The request path.
   * @param array $exclude
   *   An array of paths or system paths to skip.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   A populated request object or NULL if the patch couldn't be matched.
   */
  protected function getRequestForPath($path, array $exclude) {
    if (!empty($exclude[$path])) {
      return NULL;
    }
    // @todo Use the RequestHelper once https://drupal.org/node/2090293 is
    //   fixed.
    $request = Request::create($this->request->getBaseUrl() . '/' . $path);
    // Find the system path by resolving aliases, language prefix, etc.
    $processed = $this->pathProcessor->processInbound($path, $request);
    if (empty($processed) || !empty($exclude[$processed])) {
      // This resolves to the front page, which we already add.
      return NULL;
    }
    $request->attributes->set('_system_path', $processed);
    // Attempt to match this path to provide a fully built request.
    try {
      $request->attributes->add($this->router->matchRequest($request));
      return $request;
    }
    catch (ParamNotConvertedException $e) {
      return NULL;
    }
    catch (ResourceNotFoundException $e) {
      return NULL;
    }
  }

}
