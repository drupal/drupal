<?php

namespace Drupal\system;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Link;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Utility\RequestGenerator;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * Defines a class to build path-based breadcrumbs.
 *
 * @see \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface
 */
class PathBasedBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;
  use DeprecatedServicePropertyTrait;

  /**
   * Defines deprecated injected properties.
   *
   * @var array
   */
  protected array $deprecatedProperties = [
    'router' => 'router',
    'pathProcessor' => 'path_processor_manager',
    'currentPath' => 'path.current',
  ];

  /**
   * The router request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $context;

  /**
   * The access check service.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

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
   * The patch matcher service.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The request generator.
   *
   * @var \Drupal\Core\Utility\RequestGenerator
   */
  protected $requestGenerator;

  /**
   * Constructs the PathBasedBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Routing\RequestContext $context
   *   The router request context.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access check service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface|\Symfony\Component\Routing\Matcher\RequestMatcherInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Controller\TitleResolverInterface|\Drupal\Core\PathProcessor\InboundPathProcessorInterface $title_resolver
   *   The title resolver service.
   * @param \Drupal\Core\Session\AccountInterface|\Drupal\Core\Config\ConfigFactoryInterface $current_user
   *   The current user object.
   * @param \Drupal\Core\Path\PathMatcherInterface|\Drupal\Core\Controller\TitleResolverInterface $path_matcher
   *   The path matcher service.
   * @param \Drupal\Core\Utility\RequestGenerator|\Drupal\Core\Session\AccountInterface $request_generator
   *   The request generator.
   */
  public function __construct(
    RequestContext $context,
    AccessManagerInterface $access_manager,
    ConfigFactoryInterface|RequestMatcherInterface $config_factory,
    TitleResolverInterface|InboundPathProcessorInterface $title_resolver,
    AccountInterface|ConfigFactoryInterface $current_user,
    PathMatcherInterface|TitleResolverInterface $path_matcher,
    RequestGenerator|AccountInterface $request_generator,
  ) {
    $this->context = $context;
    $this->accessManager = $access_manager;
    if ($config_factory instanceof RequestMatcherInterface) {
      @trigger_error('Calling PathBasedBreadcrumbBuilder::__construct() with the $router, $path_processor, $current_path arguments is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. See https://www.drupal.org/node/3397213', E_USER_DEPRECATED);
      @trigger_error('Calling PathBasedBreadcrumbBuilder::__construct() without the $request_generator argument is deprecated in drupal:10.3.0 and will be required in drupal:11.0.0. See https://www.drupal.org/node/3397213', E_USER_DEPRECATED);
      $config_factory = $current_user;
      $this->titleResolver = $path_matcher;
      $this->currentUser = $request_generator;
      $this->requestGenerator = \Drupal::service('request_generator');
      $this->pathMatcher = func_get_arg(8) ?: \Drupal::service('path.matcher');
    }
    else {
      $this->titleResolver = $title_resolver;
      $this->currentUser = $current_user;
      $this->requestGenerator = $request_generator;
      $this->pathMatcher = $path_matcher;
    }
    $this->config = $config_factory->get('system.site');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $links = [];

    // Add the url.path.parent cache context. This code ignores the last path
    // part so the result only depends on the path parents.
    $breadcrumb->addCacheContexts(['url.path.parent', 'url.path.is_front']);

    // Do not display a breadcrumb on the frontpage.
    if ($this->pathMatcher->isFrontPage()) {
      return $breadcrumb;
    }

    // General path-based breadcrumbs. Use the actual request path, prior to
    // resolving path aliases, so the breadcrumb can be defined by simply
    // creating a hierarchy of path aliases.
    $path = trim($this->context->getPathInfo(), '/');
    $path_elements = explode('/', $path);
    $exclude = [];
    // Don't show a link to the front-page path.
    $front = $this->config->get('page.front');
    $exclude[$front] = TRUE;
    // /user is just a redirect, so skip it.
    // @todo Find a better way to deal with /user.
    $exclude['/user'] = TRUE;
    while (count($path_elements) > 1) {
      array_pop($path_elements);
      // Copy the path elements for up-casting.
      $route_request = $this->requestGenerator->generateRequestForPath('/' . implode('/', $path_elements), $exclude);
      if ($route_request) {
        $route_match = RouteMatch::createFromRequest($route_request);
        $access = $this->accessManager->check($route_match, $this->currentUser, NULL, TRUE);
        // The set of breadcrumb links depends on the access result, so merge
        // the access result's cacheability metadata.
        $breadcrumb = $breadcrumb->addCacheableDependency($access);
        if ($access->isAllowed()) {
          $title = $this->titleResolver->getTitle($route_request, $route_match->getRouteObject());
          if (!isset($title)) {
            // Fallback to using the raw path component as the title if the
            // route is missing a _title or _title_callback attribute.
            $title = str_replace(['-', '_'], ' ', Unicode::ucfirst(end($path_elements)));
          }
          $url = Url::fromRouteMatch($route_match);
          $links[] = new Link($title, $url);
        }
      }
    }

    // Add the Home link.
    $links[] = Link::createFromRoute($this->t('Home'), '<front>');

    return $breadcrumb->setLinks(array_reverse($links));
  }

}
