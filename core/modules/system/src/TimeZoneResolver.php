<?php

namespace Drupal\system;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountEvents;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event handler that resolves time zone based on site and user configuration.
 *
 * Sets the time zone using date_default_timezone_set().
 *
 * @see date_default_timezone_set()
 */
class TimeZoneResolver implements EventSubscriberInterface {

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $currentUser;

  /**
   * TimeZoneResolver constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(AccountInterface $current_user, ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
  }

  /**
   * Sets the default time zone.
   */
  public function setDefaultTimeZone() {
    if ($time_zone = $this->getTimeZone()) {
      date_default_timezone_set($time_zone);
    }
  }

  /**
   * Updates the default time zone when time zone config changes.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The config crud event.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $saved_config = $event->getConfig();
    if ($saved_config->getName() === 'system.date' && ($event->isChanged('timezone.default') || $event->isChanged('timezone.user.configurable'))) {
      $this->setDefaultTimeZone();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onConfigSave', 0];
    // The priority for this must run directly after the authentication
    // subscriber.
    $events[KernelEvents::REQUEST][] = ['setDefaultTimeZone', 299];
    $events[AccountEvents::SET_USER][] = ['setDefaultTimeZone'];
    return $events;
  }

  /**
   * Gets the time zone based on site and user configuration.
   *
   * @return string|null
   *   The time zone, or NULL if nothing is set.
   */
  protected function getTimeZone() {
    $config = $this->configFactory->get('system.date');
    if ($config->get('timezone.user.configurable') && $this->currentUser->isAuthenticated() && $this->currentUser->getTimezone()) {
      return $this->currentUser->getTimeZone();
    }
    elseif ($default_timezone = $config->get('timezone.default')) {
      return $default_timezone;
    }
    return NULL;
  }

}
