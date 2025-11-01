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
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Maintenance mode subscriber for controller requests.
 */
class MaintenanceModeSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly MaintenanceModeInterface $maintenanceMode,
    protected readonly ConfigFactoryInterface $configFactory,
    TranslationInterface $translation,
    protected readonly UrlGeneratorInterface $urlGenerator,
    protected readonly AccountInterface $account,
    protected readonly BareHtmlPageRendererInterface $bareHtmlPageRenderer,
    protected readonly MessengerInterface $messenger,
    protected readonly EventDispatcherInterface $eventDispatcher,
    protected readonly StateInterface $state,
    #[AutowireServiceClosure('logger.channel.default')]
    private readonly \Closure $logger,
  ) {
    $this->stringTranslation = $translation;
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
        // Display a message if the logged-in user has access to the site in
        // maintenance mode. Don't show maintenance message:
        // - on AJAX requests.
        // - on Iframe uploads.
        // - on the maintenance mode settings page.
        if ($route_match->getRouteName() != 'system.site_maintenance_mode') {
          $show_message = $route_match->getRouteName() != 'system.site_maintenance_mode' &&
            !$event->getRequest()->isXmlHttpRequest() &&
            $event->getRequest()->get('ajax_iframe_upload', FALSE) === FALSE;

          if ($show_message) {
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
   * Logs changes to maintenance mode.
   *
   * @param \Symfony\Component\HttpKernel\Event\TerminateEvent $event
   *   The event object.
   */
  public function onTerminate(TerminateEvent $event): void {
    $values = $this->state->getValuesSetDuringRequest('system.maintenance_mode');
    if ($values && $values['original'] !== $values['value']) {
      if ($values['value']) {
        $this->getLogger()->info('Maintenance mode enabled.');
      }
      else {
        $this->getLogger()->info('Maintenance mode disabled.');
      }
    }
  }

  /**
   * Gets the logging service.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logging service.
   */
  protected function getLogger(): LoggerInterface {
    return ($this->logger)();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::REQUEST][] = ['onKernelRequestMaintenance', 30];
    $events[KernelEvents::EXCEPTION][] = ['onKernelRequestMaintenance'];
    $events[MaintenanceModeEvents::MAINTENANCE_MODE_REQUEST][] = [
      'onMaintenanceModeRequest',
      -1000,
    ];
    $events[KernelEvents::TERMINATE] = ['onTerminate'];
    return $events;
  }

}
