<?php

namespace Drupal\Core\Routing;

use Drupal\Core\Access\CheckProviderInterface;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Discovery\YamlDiscovery;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\DestructableInterface;
use Drupal\Component\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Managing class for rebuilding the router table.
 *
 * Deprecated service properties:
 *
 * @property \Drupal\Core\Controller\ControllerResolverInterface $controllerResolver
 * @property \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
 */
class RouteBuilder implements RouteBuilderInterface, DestructableInterface {
  use DeprecatedServicePropertyTrait;

  /**
   * The dumper to which we should send collected routes.
   *
   * @var \Drupal\Core\Routing\MatcherDumperInterface
   */
  protected $dumper;

  /**
   * The used lock backend instance.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The event dispatcher to notify of routes.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * The route collection during the rebuild.
   *
   * @var \Symfony\Component\Routing\RouteCollection
   */
  protected $routeCollection;

  /**
   * Flag that indicates if we are currently rebuilding the routes.
   *
   * @var bool
   */
  protected $building = FALSE;

  /**
   * Flag that indicates if we should rebuild at the end of the request.
   *
   * @var bool
   */
  protected $rebuildNeeded = FALSE;

  /**
   * The check provider.
   *
   * @var \Drupal\Core\Access\CheckProviderInterface
   */
  protected $checkProvider;

  /**
   * Deprecated service properties.
   *
   * @var string[]
   */
  protected array $deprecatedProperties = [
    'controllerResolver' => 'controller_resolver',
    'moduleHandler' => 'module_handler',
  ];

  /**
   * Constructs the RouteBuilder using the passed MatcherDumperInterface.
   */
  public function __construct(MatcherDumperInterface $dumper, LockBackendInterface $lock, EventDispatcherInterface $dispatcher, CheckProviderInterface|ModuleHandlerInterface $check_provider) {
    $this->dumper = $dumper;
    $this->lock = $lock;
    $this->dispatcher = $dispatcher;
    if ($check_provider instanceof ModuleHandlerInterface && count(func_get_args()) === 6) {
      $check_provider = func_get_arg(5);
      @trigger_error('Calling ' . __METHOD__ . '() with the module handler and controller resolver services is deprecated in drupal:11.4.0 and will be removed in drupal:12.0.0. See https://www.drupal.org/node/3324751', E_USER_DEPRECATED);
    }
    if (!$check_provider instanceof CheckProviderInterface) {
      throw new \InvalidArgumentException();
    }
    $this->checkProvider = $check_provider;
  }

  /**
   * {@inheritdoc}
   */
  public function setRebuildNeeded() {
    $this->rebuildNeeded = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function rebuild() {
    if ($this->building) {
      throw new \RuntimeException('Recursive router rebuild detected.');
    }

    if (!$this->lock->acquire('router_rebuild')) {
      // Wait for another request that is already doing this work.
      // We choose to block here since otherwise the routes might not be
      // available, resulting in a 404.
      $this->lock->wait('router_rebuild');
      return FALSE;
    }

    $this->building = TRUE;
    $collection = new RouteCollection();

    // STATIC is supposed to be used to add new routes based static information
    // like routing.yml files or PHP attributes.
    $this->dispatcher->dispatch(new RouteBuildEvent($collection), RoutingEvents::STATIC);

    // DYNAMIC is supposed to be used to add new routes based upon all the
    // static defined ones.
    $this->dispatcher->dispatch(new RouteBuildEvent($collection), RoutingEvents::DYNAMIC);

    // ALTER is the final step to alter all the existing routes. We cannot stop
    // people from adding new routes here, but we define it as a separate step
    // to make it clear.
    $this->dispatcher->dispatch(new RouteBuildEvent($collection), RoutingEvents::ALTER);

    $this->checkProvider->setChecks($collection);

    $this->dumper->addRoutes($collection);
    $this->dumper->dump();

    $this->lock->release('router_rebuild');
    $this->dispatcher->dispatch(new Event(), RoutingEvents::FINISHED);
    $this->building = FALSE;

    $this->rebuildNeeded = FALSE;

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function rebuildIfNeeded() {
    if ($this->rebuildNeeded) {
      return $this->rebuild();
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {
    // Rebuild routes only once at the end of the request lifecycle to not
    // trigger multiple rebuilds and also make the page more responsive for the
    // user.
    $this->rebuildIfNeeded();
  }

  /**
   * Retrieves all defined routes from .routing.yml files.
   *
   * @return array
   *   The defined routes, keyed by provider.
   *
   * @deprecated in drupal:11.4.0 and is removed from drupal:12.0.0. This code
   *   has moved to \Drupal\Core\Routing\YamlRouteDiscovery.
   *
   * @see https://www.drupal.org/node/3324758
   */
  protected function getRouteDefinitions() {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:11.4.0 and is removed from drupal:12.0.0. This code has moved to \Drupal\Core\Routing\YamlRouteDiscovery. See https://www.drupal.org/node/3324758', E_USER_DEPRECATED);
    // Always instantiate a new YamlDiscovery object so that we always search on
    // the up-to-date list of modules.
    $discovery = new YamlDiscovery('routing', $this->moduleHandler->getModuleDirectories());
    return $discovery->findAll();
  }

}
