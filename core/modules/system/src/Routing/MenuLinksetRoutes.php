<?php

namespace Drupal\system\Routing;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RouteBuilderInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Dynamically defines routes for menu linkset endpoints.
 */
class MenuLinksetRoutes extends RouteSubscriberBase implements ContainerInjectionInterface {

  /**
   * An array of enabled authentication provider IDs.
   *
   * @var string[]
   */
  protected readonly array $providerIds;

  /**
   * EventSubscriber constructor.
   *
   * @param string[] $authenticationProviders
   *   An array of authentication providers, keyed by ID.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $routeBuilder
   *   The route builder.
   */
  public function __construct(array $authenticationProviders, protected readonly ConfigFactoryInterface $configFactory, protected readonly RouteBuilderInterface $routeBuilder) {
    $this->providerIds = array_keys($authenticationProviders);
  }

  /**
   * Alter routes.
   *
   * If the endpoint is configured to be enabled, dynamically enable all
   * authentication providers on this module's routes since they cannot be known
   * in advance.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   A collection of routes.
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($this->configFactory->get('system.feature_flags')->get('linkset_endpoint')) {
      $collection->get('system.menu.linkset')->setOption('_auth', $this->providerIds);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $saved_config = $event->getConfig();
    if ($saved_config->getName() === 'system.feature_flags' && $event->isChanged('linkset_endpoint')) {
      $this->routeBuilder->setRebuildNeeded();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();
    // Run after the route alter event subscriber.
    $events[ConfigEvents::SAVE][] = ['onConfigSave', 0];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->getParameter('authentication_providers'),
      $container->get('config.factory'),
      $container->get('router.builder')
    );
  }

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes() {
    $routes = [];

    // Only enable linkset routes if the related config option is enabled.
    if ($this->configFactory->get('system.feature_flags')->get('linkset_endpoint')) {
      $routes['system.menu.linkset'] = new Route(
        '/system/menu/{menu}/linkset',
        [
          '_controller' => 'Drupal\system\Controller\LinksetController::process',
        ],
        [
          '_access' => 'TRUE',
        ],
        [
          'parameters' => [
            'menu' => [
              'type' => 'entity:menu',
            ],
          ],
        ]
      );
    }
    return $routes;
  }

}
