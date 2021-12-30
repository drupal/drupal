<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\BareHtmlPageRendererInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\MaintenanceModeEvents;
use Drupal\Core\Site\MaintenanceModeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Maintenance mode subscriber for controller requests.
 */
class MaintenanceModeSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The maintenance mode.
   *
   * @var \Drupal\Core\Site\MaintenanceModeInterface
   */
  protected $maintenanceMode;

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The bare HTML page renderer.
   *
   * @var \Drupal\Core\Render\BareHtmlPageRendererInterface
   */
  protected $bareHtmlPageRenderer;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * An event dispatcher instance to use for configuration events.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new MaintenanceModeSubscriber.
   *
   * @param \Drupal\Core\Site\MaintenanceModeInterface $maintenance_mode
   *   The maintenance mode.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Render\BareHtmlPageRendererInterface $bare_html_page_renderer
   *   The bare HTML page renderer.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(MaintenanceModeInterface $maintenance_mode, ConfigFactoryInterface $config_factory, TranslationInterface $translation, UrlGeneratorInterface $url_generator, AccountInterface $account, BareHtmlPageRendererInterface $bare_html_page_renderer, MessengerInterface $messenger, EventDispatcherInterface $event_dispatcher = NULL) {
    $this->maintenanceMode = $maintenance_mode;
    $this->config = $config_factory;
    $this->stringTranslation = $translation;
    $this->urlGenerator = $url_generator;
    $this->account = $account;
    $this->bareHtmlPageRenderer = $bare_html_page_renderer;
    $this->messenger = $messenger;
    if (!$event_dispatcher) {
      @trigger_error('Calling MaintenanceModeSubscriber::__construct() without the $event_dispatcher argument is deprecated in drupal:9.4.0 and the $event_dispatcher argument will be required in drupal:10.0.0. See https://www.drupal.org/node/3255799', E_USER_DEPRECATED);
      $event_dispatcher = \Drupal::service('event_dispatcher');
    }
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Returns the site maintenance page if the site is offline.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function onKernelRequestMaintenance(RequestEvent $event) {
    $request = $event->getRequest();
    $route_match = RouteMatch::createFromRequest($request);
    if ($this->maintenanceMode->applies($route_match)) {
      // Don't cache maintenance mode pages.
      \Drupal::service('page_cache_kill_switch')->trigger();

      if (!$this->maintenanceMode->exempt($this->account)) {
        // When the account is not exempt, other subscribers handle request.
        $this->eventDispatcher->dispatch($event, MaintenanceModeEvents::MAINTENANCE_MODE_REQUEST);
      }
      else {
        // Display a message if the logged in user has access to the site in
        // maintenance mode. However, suppress it on the maintenance mode
        // settings page.
        if ($route_match->getRouteName() != 'system.site_maintenance_mode') {
          if ($this->account->hasPermission('administer site configuration')) {
            $this->messenger->addMessage($this->t('Operating in maintenance mode. <a href=":url">Go online.</a>', [':url' => $this->urlGenerator->generate('system.site_maintenance_mode')]), 'status', FALSE);
          }
          else {
            $this->messenger->addMessage($this->t('Operating in maintenance mode.'), 'status', FALSE);
          }
        }
      }
    }
  }

  /**
   * Returns response when site is in maintenance mode and user is not exempt.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function onMaintenanceModeRequest(RequestEvent $event) {
    $request = $event->getRequest();
    if ($request->getRequestFormat() !== 'html') {
      $response = new Response($this->maintenanceMode->getSiteMaintenanceMessage(), 503, ['Content-Type' => 'text/plain']);
      // Calling RequestEvent::setResponse() also stops propagation of event.
      $event->setResponse($response);
      return;
    }
    drupal_maintenance_theme();
    $response = $this->bareHtmlPageRenderer->renderBarePage(['#markup' => $this->maintenanceMode->getSiteMaintenanceMessage()], $this->t('Site under maintenance'), 'maintenance_page');
    $response->setStatusCode(503);
    // Calling RequestEvent::setResponse() also stops propagation of the event.
    $event->setResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onKernelRequestMaintenance', 30];
    $events[KernelEvents::EXCEPTION][] = ['onKernelRequestMaintenance'];
    $events[MaintenanceModeEvents::MAINTENANCE_MODE_REQUEST][] = [
      'onMaintenanceModeRequest',
      -1000,
    ];
    return $events;
  }

}
