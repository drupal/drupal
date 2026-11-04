<?php

namespace Drupal\language\Config;

use Drupal\Component\EventDispatcher\Event;

/**
 * Provides a language override event for event listeners.
 *
 * @see \Drupal\Core\Config\ConfigCrudEvent
 */
class LanguageConfigOverrideCrudEvent extends Event {

  /**
   * Constructs a configuration event object.
   *
   * @param \Drupal\language\Config\LanguageConfigOverride $override
   *   Configuration object.
   */
  public function __construct(
    protected LanguageConfigOverride $override,
  ) {
  }

  /**
   * Gets configuration object.
   *
   * @return \Drupal\language\Config\LanguageConfigOverride
   *   The configuration object that caused the event to fire.
   */
  public function getLanguageConfigOverride() {
    return $this->override;
  }

}
