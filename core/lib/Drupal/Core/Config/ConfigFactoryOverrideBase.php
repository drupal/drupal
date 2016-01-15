<?php

/**
 * @file
 * Contains \Drupal\Core\Config\ConfigFactoryOverrideBase.
 */

namespace Drupal\Core\Config;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines a base event listener implementation configuration overrides.
 */
abstract class ConfigFactoryOverrideBase implements EventSubscriberInterface {

  /**
   * Reacts to the ConfigEvents::COLLECTION_INFO event.
   *
   * @param \Drupal\Core\Config\ConfigCollectionInfo $collection_info
   *   The configuration collection info event.
   */
  abstract public function addCollections(ConfigCollectionInfo $collection_info);

  /**
   * Actions to be performed to configuration override on configuration save.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The config CRUD event.
   */
  abstract public function onConfigSave(ConfigCrudEvent $event);

  /**
   * Actions to be performed to configuration override on configuration delete.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The config CRUD event.
   */
  abstract public function onConfigDelete(ConfigCrudEvent $event);

  /**
   * Actions to be performed to configuration override on configuration rename.
   *
   * @param \Drupal\Core\Config\ConfigRenameEvent $event
   *   The config rename event.
   */
  abstract public function onConfigRename(ConfigRenameEvent $event);

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[ConfigEvents::COLLECTION_INFO][] = array('addCollections');
    $events[ConfigEvents::SAVE][] = array('onConfigSave', 20);
    $events[ConfigEvents::DELETE][] = array('onConfigDelete', 20);
    $events[ConfigEvents::RENAME][] = array('onConfigRename', 20);
    return $events;
  }

  /**
   * Filters data in the override based on what is currently in configuration.
   *
   * @param \Drupal\Core\Config\Config $config
   *   Current configuration object.
   * @param \Drupal\Core\Config\StorableConfigBase $override
   *   Override object corresponding to the configuration to filter data in.
   */
  protected function filterOverride(Config $config, StorableConfigBase $override) {
    $override_data = $override->get();
    $changed = $this->filterNestedArray($config->get(), $override_data);
    if (empty($override_data)) {
      // If no override values are left that would apply, remove the override.
      $override->delete();
    }
    elseif ($changed) {
      // Otherwise set the filtered override values back.
      $override->setData($override_data)->save(TRUE);
    }
  }

  /**
   * Filters data in nested arrays.
   *
   * @param array $original_data
   *   Original data array to filter against.
   * @param array $override_data
   *   Override data to filter.
   *
   * @return bool
   *   TRUE if $override_data was changed, FALSE otherwise.
   */
  protected function filterNestedArray(array $original_data, array &$override_data) {
    $changed = FALSE;
    foreach ($override_data as $key => $value) {
      if (!isset($original_data[$key])) {
        // The original data is not there anymore, remove the override.
        unset($override_data[$key]);
        $changed = TRUE;
      }
      elseif (is_array($override_data[$key])) {
        if (is_array($original_data[$key])) {
          // Do the filtering one level deeper.
          $changed = $this->filterNestedArray($original_data[$key], $override_data[$key]);
          // If no overrides are left under this level, remove the level.
          if (empty($override_data[$key])) {
            unset($override_data[$key]);
            $changed = TRUE;
          }
        }
        else {
          // The override is an array but the value is not, this will not go
          // well, remove the override.
          unset($override_data[$key]);
          $changed = TRUE;
        }
      }
    }
    return $changed;
  }

}
