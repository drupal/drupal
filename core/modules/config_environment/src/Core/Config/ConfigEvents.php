<?php

// @codingStandardsIgnoreStart
// @todo: Move this back to \Drupal\Core\Config\ConfigEvents in #2991683.
// @codingStandardsIgnoreEnd
namespace Drupal\config_environment\Core\Config;

/**
 * Defines events for the configuration transform system.
 *
 * The constants in this class will be moved back into ConfigEvents.
 * But due to the fact that the config_environment is not in beta we save their
 * definitions here and use the literal strings in the mean time.
 *
 * @internal
 *
 * @deprecated The class will be merged with Drupal\Core\Config\ConfigEvents.
 */
final class ConfigEvents {

  /**
   * Name of the event fired just before importing configuration.
   *
   * This event allows subscribers to modify the configuration which is about to
   * be imported. The event listener method receives a
   * \Drupal\Core\Config\StorageTransformEvent instance. This event contains a
   * config storage which subscribers can interact with and which will finally
   * be used to import the configuration from.
   * Together with \Drupal\Core\Config\ConfigEvents::STORAGE_TRANSFORM_EXPORT
   * subscribers can alter the active configuration in a config sync workflow
   * instead of just overriding at runtime via the config-override system.
   * This allows a complete customisation of the workflow including additional
   * modules and editable configuration in different environments.
   *
   * @code
   *   $storage = $event->getStorage();
   * @endcode
   *
   * This event is also fired when just viewing the difference of configuration
   * to be imported independently of whether the import takes place or not.
   * Use the \Drupal\Core\Config\ConfigEvents::IMPORT event to subscribe to the
   * import having taken place.
   *
   * @Event
   *
   * @see \Drupal\Core\Config\StorageTransformEvent
   * @see \Drupal\Core\Config\ConfigEvents::STORAGE_TRANSFORM_EXPORT
   *
   * @var string
   */
  const STORAGE_TRANSFORM_IMPORT = 'config.transform.import';

  /**
   * Name of the event fired when the export storage is used.
   *
   * This event allows subscribers to modify the configuration which is about to
   * be exported. The event listener method receives a
   * \Drupal\Core\Config\StorageTransformEvent instance. This event contains a
   * config storage which subscribers can interact with and which will finally
   * be used to export the configuration from.
   *
   * @code
   *   $storage = $event->getStorage();
   * @endcode
   *
   * Typically subscribers will want to perform the reverse operation on the
   * storage than for \Drupal\Core\Config\ConfigEvents::STORAGE_TRANSFORM_IMPORT
   * to make sure successive exports and imports yield no difference.
   *
   * @Event
   *
   * @see \Drupal\Core\Config\StorageTransformEvent
   * @see \Drupal\Core\Config\ConfigEvents::STORAGE_TRANSFORM_IMPORT
   *
   * @var string
   */
  const STORAGE_TRANSFORM_EXPORT = 'config.transform.export';

}
