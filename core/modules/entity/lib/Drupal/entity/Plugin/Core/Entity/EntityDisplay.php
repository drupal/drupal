<?php

/**
 * @file
 * Contains \Drupal\entity\Plugin\Core\Entity\EntityDisplay.
 */

namespace Drupal\entity\Plugin\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Configuration entity that contains display options for all components of a
 * rendered entity in a given view mode..
 *
 * @EntityType(
 *   id = "entity_display",
 *   label = @Translation("Entity display"),
 *   module = "entity",
 *   controller_class = "Drupal\Core\Config\Entity\ConfigStorageController",
 *   config_prefix = "entity.display",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class EntityDisplay extends ConfigEntityBase {

  /**
   * Unique ID for the config entity.
   *
   * @var string
   */
  public $id;

  /**
   * Unique UUID for the config entity.
   *
   * @var string
   */
  public $uuid;

  /**
   * Entity type to be displayed.
   *
   * @var string
   */
  public $targetEntityType;

  /**
   * Bundle to be displayed.
   *
   * @var string
   */
  public $bundle;

  /**
   * View mode to be displayed.
   *
   * @var string
   */
  public $viewMode;

  /**
   * List of component display options, keyed by component name.
   *
   * @var array
   */
  protected $content = array();

  /**
   * The original view mode that was requested (case of view modes being
   * configured to fall back to the 'default' display).
   *
   * @var string
   */
  public $originalViewMode;

  /**
   * The formatter objects used for this display, keyed by field name.
   *
   * @var array
   */
  protected $formatters = array();

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigEntityBase::__construct().
   */
  public function __construct(array $values, $entity_type) {
    // @todo See http://drupal.org/node/1825044#comment-6847792: contact.module
    // currently produces invalid entities with a NULL bundle in some cases.
    // Add the validity checks back when http://drupal.org/node/1856556 is
    // fixed.
    // if (!isset($values['targetEntityType']) || !isset($values['bundle']) || !isset($values['viewMode'])) {
    //   throw new \InvalidArgumentException('Missing required properties for an EntiyDisplay entity.');
    // }
    parent::__construct($values, $entity_type);

    $this->originalViewMode = $this->viewMode;
  }

  /**
   * Overrides \Drupal\Core\Entity\Entity::id().
   */
  public function id() {
    return $this->targetEntityType . '.' . $this->bundle . '.' . $this->viewMode;
  }

  /**
   * Overrides \Drupal\config\ConfigEntityBase::save().
   */
  public function save() {
    // Build an ID if none is set.
    if (empty($this->id)) {
      $this->id = $this->id();
    }
    return parent::save();
  }

  /**
   * Overrides \Drupal\config\ConfigEntityBase::getExportProperties();
   */
  public function getExportProperties() {
    $names = array(
      'id',
      'uuid',
      'targetEntityType',
      'bundle',
      'viewMode',
      'content',
    );
    $properties = array();
    foreach ($names as $name) {
      $properties[$name] = $this->get($name);
    }
    return $properties;
  }

  /**
   * Creates a duplicate of the EntityDisplay object on a different view mode.
   *
   * The new object necessarily has the same $targetEntityType and $bundle
   * properties than the original one.
   *
   * @param $view_mode
   *   The view mode for the new object.
   *
   * @return \Drupal\entity\Plugin\Core\Entity\EntityDisplay
   *   The new object.
   */
  public function createCopy($view_mode) {
    $display = $this->createDuplicate();
    $display->viewMode = $display->originalViewMode = $view_mode;
    return $display;
  }

  /**
   * Gets the display options for all components.
   *
   * @return array
   *   The array of display options, keyed by component name.
   */
  public function getComponents() {
    $result = array();
    foreach ($this->content as $name => $options) {
      if (!isset($options['visible']) || $options['visible'] === TRUE) {
        unset($options['visible']);
        $result[$name] = $options;
      }
    }
    return $result;
  }

  /**
   * Gets the display options set for a component.
   *
   * @param string $name
   *   The name of the component.
   *
   * @return array|null
   *   The display options for the component, or NULL if the component is not
   *   displayed.
   */
  public function getComponent($name) {
    // We always store 'extra fields', whether they are visible or hidden.
    $extra_fields = field_info_extra_fields($this->targetEntityType, $this->bundle, 'display');
    if (isset($extra_fields[$name])) {
      // If we have explicit settings, return an array or NULL depending on
      // visibility.
      if (isset($this->content[$name])) {
        if ($this->content[$name]['visible']) {
          return array(
            'weight' => $this->content[$name]['weight'],
          );
        }
        else {
          return NULL;
        }
      }

      // If no explicit settings for the extra field, look at the default
      // visibility in its definition.
      $definition = $extra_fields[$name];
      if (!isset($definition['visible']) || $definition['visible'] == TRUE) {
        return array(
          'weight' => $definition['weight']
        );
      }
      else {
        return NULL;
      }
    }

    if (isset($this->content[$name])) {
      return $this->content[$name];
    }
  }

  /**
   * Sets the display options for a component.
   *
   * @param string $name
   *   The name of the component.
   * @param array $options
   *   The display options.
   *
   * @return \Drupal\entity\Plugin\Core\Entity\EntityDisplay
   *   The EntityDisplay object.
   */
  public function setComponent($name, array $options = array()) {
    // If no weight specified, make sure the field sinks at the bottom.
    if (!isset($options['weight'])) {
      $max = $this->getHighestWeight();
      $options['weight'] = isset($max) ? $max + 1 : 0;
    }

    if ($instance = field_info_instance($this->targetEntityType, $name, $this->bundle)) {
      $field = field_info_field($instance['field_name']);
      $options = drupal_container()->get('plugin.manager.field.formatter')->prepareConfiguration($field['type'], $options);

      // Clear the persisted formatter, if any.
      unset($this->formatters[$name]);
    }

    // We always store 'extra fields', whether they are visible or hidden.
    $extra_fields = field_info_extra_fields($this->targetEntityType, $this->bundle, 'display');
    if (isset($extra_fields[$name])) {
      $options['visible'] = TRUE;
    }

    $this->content[$name] = $options;

    return $this;
  }

  /**
   * Sets a component to be hidden.
   *
   * @param string $name
   *   The name of the component.
   *
   * @return \Drupal\entity\Plugin\Core\Entity\EntityDisplay
   *   The EntityDisplay object.
   */
  public function removeComponent($name) {
    $extra_fields = field_info_extra_fields($this->targetEntityType, $this->bundle, 'display');
    if (isset($extra_fields[$name])) {
      // 'Extra fields' are exposed in hooks and can appear at any given time.
      // Therefore we store extra fields that are explicitly being hidden, so
      // that we can differenciate with those that are simply not configured
      // yet.
      $this->content[$name] = array(
        'visible' => FALSE,
      );
    }
    else {
      unset($this->content[$name]);
      unset($this->formatters[$name]);
    }

    return $this;
  }

  /**
   * Returns the highest weight of the components in the display.
   *
   * @return int|null
   *   The highest weight of the components in the display, or NULL if the
   *   display is empty.
   */
  public function getHighestWeight() {
    $weights = array();

    // Collect weights for the components in the display.
    foreach ($this->content as $options) {
      if (isset($options['weight'])) {
        $weights[] = $options['weight'];
      }
    }

    // Let other modules feedback about their own additions.
    $weights = array_merge($weights, module_invoke_all('field_info_max_weight', $this->targetEntityType, $this->bundle, $this->viewMode));

    return $weights ? max($weights) : NULL;
  }

  /**
   * Returns the Formatter plugin for a field.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return \Drupal\field\Plugin\Type\Formatter\FormatterInterface
   *   If the field is not hidden, the Formatter plugin to use for rendering
   *   it.
   */
  public function getFormatter($field_name) {
    if (isset($this->formatters[$field_name])) {
      return $this->formatters[$field_name];
    }

    // Instantiate the formatter object from the stored display properties.
    if ($configuration = $this->getComponent($field_name)) {
      $instance = field_info_instance($this->targetEntityType, $field_name, $this->bundle);
      $formatter = drupal_container()->get('plugin.manager.field.formatter')->getInstance(array(
        'instance' => $instance,
        'view_mode' => $this->originalViewMode,
        // No need to prepare, defaults have been merged in setComponent().
        'prepare' => FALSE,
        'configuration' => $configuration
      ));
    }
    else {
      $formatter = NULL;
    }

    // Persist the formatter object.
    $this->formatters[$field_name] = $formatter;
    return $formatter;
  }

}
