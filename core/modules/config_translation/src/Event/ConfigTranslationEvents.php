<?php

namespace Drupal\config_translation\Event;

/**
 * Provides a list of events dispatched by the Configuration Translation module.
 */
final class ConfigTranslationEvents {

  /**
   * The name of the event dispatched when a configuration mapper is populated.
   *
   * Allows modules to add related config for translation on a specific
   * translation form.
   *
   * @see \Drupal\config_translation\ConfigMapperInterface::populateFromRouteMatch()
   */
  const POPULATE_MAPPER = 'config_translation.populate_mapper';

}
