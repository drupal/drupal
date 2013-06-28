<?php

/**
 * @file
 * Contains \Drupal\entity\Plugin\Core\Entity\EntityFormDisplay.
 */

namespace Drupal\entity\Plugin\Core\Entity;

use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\entity\EntityDisplayBase;
use Drupal\entity\EntityFormDisplayInterface;

/**
 * Configuration entity that contains widget options for all components of a
 * entity form in a given form mode.
 *
 * @EntityType(
 *   id = "entity_form_display",
 *   label = @Translation("Entity form display"),
 *   module = "entity",
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController"
 *   },
 *   config_prefix = "entity.form_display",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class EntityFormDisplay extends EntityDisplayBase implements EntityFormDisplayInterface, \Serializable {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    $this->pluginManager = \Drupal::service('plugin.manager.field.widget');
    $this->displayContext = 'form';

    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderer($field_name) {
    if (isset($this->plugins[$field_name])) {
      return $this->plugins[$field_name];
    }

    // Instantiate the widget object from the stored display properties.
    if ($configuration = $this->getComponent($field_name)) {
      $instance = field_info_instance($this->targetEntityType, $field_name, $this->bundle);
      $widget = $this->pluginManager->getInstance(array(
        'field_definition' => $instance,
        'form_mode' => $this->originalMode,
        // No need to prepare, defaults have been merged in setComponent().
        'prepare' => FALSE,
        'configuration' => $configuration
      ));
    }
    else {
      $widget = NULL;
    }

    // Persist the widget object.
    $this->plugins[$field_name] = $widget;
    return $widget;
  }

  /**
   * {@inheritdoc}
   */
  public function serialize() {
    // Only store the definition, not external objects or derived data.
    $data = $this->getExportProperties() + array('entityType' => $this->entityType());
    return serialize($data);
  }

  /**
   * {@inheritdoc}
   */
  public function unserialize($serialized) {
    $data = unserialize($serialized);
    $entity_type = $data['entityType'];
    unset($data['entityType']);
    $this->__construct($data, $entity_type);
  }

}
