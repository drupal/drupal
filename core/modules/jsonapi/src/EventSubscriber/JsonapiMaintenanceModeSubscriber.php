<?php

namespace Drupal\jsonapi\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Site\MaintenanceModeEvents;
use Drupal\Core\Site\MaintenanceModeInterface;
use Drupal\jsonapi\JsonApiResource\ErrorCollection;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\NullIncludedData;
use Drupal\jsonapi\ResourceResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Maintenance mode subscriber for JSON:API requests.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 */
class JsonapiMaintenanceModeSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new JsonapiMaintenanceModeSubscriber.
   *
   * @param \Drupal\Core\Site\MaintenanceModeInterface $maintenanceMode
   *   The maintenance mode.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   */
  public function __construct(
    protected MaintenanceModeInterface $maintenanceMode,
    protected ConfigFactoryInterface $config,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events[MaintenanceModeEvents::MAINTENANCE_MODE_REQUEST][] = [
      'onMaintenanceModeRequest',
      -800,
    ];
    return $events;
  }

  /**
   * Returns response when site is in maintenance mode and user is not exempt.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function onMaintenanceModeRequest(RequestEvent $event) {
    $request = $event->getRequest();

    if ($request->getRequestFormat() !== 'api_json') {
      return;
    }
    // Retry-After will be random within a range defined in jsonapi settings.
    // The goals are to keep it short and to reduce the thundering herd problem.
    $header_settings = $this->config->get('jsonapi.settings')->get('maintenance_header_retry_seconds');
    $retry_after_time = rand($header_settings['min'], $header_settings['max']);
    $http_exception = new HttpException(503, $this->maintenanceMode->getSiteMaintenanceMessage());
    $document = new JsonApiDocumentTopLevel(new ErrorCollection([$http_exception]), new NullIncludedData(), new LinkCollection([]));
    $response = new ResourceResponse($document, $http_exception->getStatusCode(), [
      'Content-Type' => 'application/vnd.api+json',
      'Retry-After' => $retry_after_time,
    ]);
    // Calling RequestEvent::setResponse() also stops propagation of event.
    $event->setResponse($response);
  }

}
