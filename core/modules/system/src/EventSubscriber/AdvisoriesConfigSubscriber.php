<?php

namespace Drupal\system\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\system\SecurityAdvisories\SecurityAdvisoriesFetcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines a config subscriber for changes to 'system.advisories'.
 */
class AdvisoriesConfigSubscriber implements EventSubscriberInterface {

  /**
   * The security advisory fetcher service.
   *
   * @var \Drupal\system\SecurityAdvisories\SecurityAdvisoriesFetcher
   */
  protected $securityAdvisoriesFetcher;

  /**
   * Constructs a new ConfigSubscriber object.
   *
   * @param \Drupal\system\SecurityAdvisories\SecurityAdvisoriesFetcher $security_advisories_fetcher
   *   The security advisory fetcher service.
   */
  public function __construct(SecurityAdvisoriesFetcher $security_advisories_fetcher) {
    $this->securityAdvisoriesFetcher = $security_advisories_fetcher;
  }

  /**
   * Deletes the stored response from the security advisories feed, if needed.
   *
   * The stored response will only be deleted if the 'interval_hours' config
   * setting is reduced from the previous value.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigSave(ConfigCrudEvent $event): void {
    $saved_config = $event->getConfig();
    if ($saved_config->getName() === 'system.advisories' && $event->isChanged('interval_hours')) {
      $original_interval = $saved_config->getOriginal('interval_hours');
      if ($original_interval && $saved_config->get('interval_hours') < $original_interval) {
        // If the new interval is less than the original interval, delete the
        // stored results.
        $this->securityAdvisoriesFetcher->deleteStoredResponse();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[ConfigEvents::SAVE][] = ['onConfigSave'];
    return $events;
  }

}
