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
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Class to define the menu_link breadcrumb builder.
 */
class PathBasedBreadcrumbBuilder extends BreadcrumbBuilderBase {

  /**
   * The router request context.
   *
   * @var \Symfony\Component\Routing\RequestContext
   */
  protected $context;

  /**
   * The menu link access service.
   *
   * @var \Drupal\Core\Access\AccessManager
   */
  protected $accessManager;

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
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs the PathBasedBreadcrumbBuilder.
   *
   * @param \Symfony\Component\Routing\RequestContext $context
   *   The router request context.
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
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user object.
   */
  public function __construct(RequestContext $context, AccessManager $access_manager, RequestMatcherInterface $router, InboundPathProcessorInterface $path_processor, ConfigFactoryInterface $config_factory, TitleResolverInterface $title_resolver, AccountInterface $current_user) {
    $this->context = $context;
    $this->accessManager = $access_manager;
    $this->router = $router;
    $this->pathProcessor = $path_processor;
    $this->config = $config_factory->get('system.site');
    $this->titleResolver = $title_resolver;
    $this->currentUser = $current_user;
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
    $path = trim($this->context->getPathInfo(), '/');
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
        $route_name = $route_request->attributes->get(RouteObjectInterface::ROUTE_NAME);
        // Note that the parameters don't really matter here since we're
        // passing in the request which already has the upcast attributes.
        $parameters = array();
        $access = $this->accessManager->checkNamedRoute($route_name, $parameters, $this->currentUser, $route_request);
        if ($access) {
          $title = $this->titleResolver->getTitle($route_request, $route_request->attributes->get(RouteObjectInterface::ROUTE_OBJECT));
        }
        if ($access) {
          if (!isset($title)) {
            // Fallback to using the raw path component as the title if the
            // route is missing a _title or _title_callback attribute.
            $title = str_replace(array('-', '_'), ' ', Unicode::ucfirst(end($path_elements)));
          }
          // @todo Replace with a #type => link render element so that the alter
          // hook can work with the actual data.
          $links[] = $this->l($title, $route_request->attributes->get(RouteObjectInterface::ROUTE_NAME), $route_request->attributes->get('_raw_variables')->all(), array('html' => TRUE));
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
   *   A populated request object or NULL if the path couldn't be matched.
   */
  protected function getRequestForPath($path, array $exclude) {
    if (!empty($exclude[$path])) {
      return NULL;
    }
    // @todo Use the RequestHelper once https://drupal.org/node/2090293 is
    //   fixed.
    $request = Request::create($this->context->getBaseUrl() . '/' . $path);
    // Performance optimization: set a short accept header to reduce overhead in
    // AcceptHeaderMatcher when matching the request.
    $request->headers->set('Accept', 'text/html');
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
    catch (MethodNotAllowedException $e) {
      return NULL;
    }
  }

}
