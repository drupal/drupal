<?php

/**
 * @file
 * Contains \Drupal\entity\Entity\EntityDisplay.
 */

namespace Drupal\entity\Entity;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\entity\EntityDisplayBase;

/**
 * Configuration entity that contains display options for all components of a
 * rendered entity in a given view mode.
 *
 * @EntityType(
 *   id = "entity_display",
 *   label = @Translation("Entity display"),
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController"
 *   },
 *   config_prefix = "entity.display",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "status" = "status"
 *   }
 * )
 */
class EntityDisplay extends EntityDisplayBase implements EntityViewDisplayInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    $this->pluginManager = \Drupal::service('plugin.manager.field.formatter');
    $this->displayContext = 'display';

    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderer($field_name) {
    if (isset($this->plugins[$field_name])) {
      return $this->plugins[$field_name];
    }

    // Instantiate the formatter object from the stored display properties.
    if (($configuration = $this->getComponent($field_name)) && isset($configuration['type']) && ($definition = $this->getFieldDefinition($field_name))) {
      $formatter = $this->pluginManager->getInstance(array(
        'field_definition' => $definition,
        'view_mode' => $this->originalMode,
        // No need to prepare, defaults have been merged in setComponent().
        'prepare' => FALSE,
        'configuration' => $configuration
      ));
    }
    else {
      $formatter = NULL;
    }

    // Persist the formatter object.
    $this->plugins[$field_name] = $formatter;
    return $formatter;
  }

}
