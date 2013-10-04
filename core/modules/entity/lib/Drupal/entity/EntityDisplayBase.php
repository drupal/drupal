<?php

/**
 * @file
 * Contains \Drupal\entity\EntityDisplayBase.
 */

namespace Drupal\entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Base class for config entity types that store configuration for entity forms
 * and displays.
 */
abstract class EntityDisplayBase extends ConfigEntityBase implements EntityDisplayBaseInterface {

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
   * View or form mode to be displayed.
   *
   * @var string
   */
  public $mode;

  /**
   * Whether this display is enabled or not. If the entity (form) display
   * is disabled, we'll fall back to the 'default' display.
   *
   * @var boolean
   */
  public $status;

  /**
   * List of component display options, keyed by component name.
   *
   * @var array
   */
  protected $content = array();

  /**
   * The original view or form mode that was requested (case of view/form modes
   * being configured to fall back to the 'default' display).
   *
   * @var string
   */
  public $originalMode;

  /**
   * The plugin objects used for this display, keyed by field name.
   *
   * @var array
   */
  protected $plugins = array();

  /**
   * Context in which this entity will be used (e.g. 'display', 'form').
   *
   * @var string
   */
  protected $displayContext;

  /**
   * The plugin manager used by this entity type.
   *
   * @var \Drupal\Component\Plugin\PluginManagerBase
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    // @todo See http://drupal.org/node/1825044#comment-6847792: contact.module
    // currently produces invalid entities with a NULL bundle in some cases.
    // Add the validity checks back when http://drupal.org/node/1856556 is
    // fixed.
    // if (!isset($values['targetEntityType']) || !isset($values['bundle']) || !isset($values['mode'])) {
    //   throw new \InvalidArgumentException('Missing required properties for an EntityDisplay entity.');
    // }

    // A plugin manager and a context type needs to be set by extending classes.
    if (!isset($this->pluginManager)) {
      throw new \RuntimeException('Missing plugin manager.');
    }
    if (!isset($this->displayContext)) {
      throw new \RuntimeException('Missing display context type.');
    }

    parent::__construct($values, $entity_type);

    $this->originalMode = $this->mode;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->targetEntityType . '.' . $this->bundle . '.' . $this->mode;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $return = parent::save();

    // Reset the render cache for the target entity type.
    if (\Drupal::entityManager()->hasController($this->targetEntityType, 'render')) {
      \Drupal::entityManager()->getRenderController($this->targetEntityType)->resetCache();
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getExportProperties() {
    $names = array(
      'id',
      'uuid',
      'targetEntityType',
      'bundle',
      'mode',
      'content',
      'status',
    );
    $properties = array();
    foreach ($names as $name) {
      $properties[$name] = $this->get($name);
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function createCopy($mode) {
    $display = $this->createDuplicate();
    $display->mode = $display->originalMode = $mode;
    return $display;
  }

  /**
   * {@inheritdoc}
   */
  public function getComponents() {
    $result = array();
    foreach ($this->content as $name => $options) {
      if (!isset($options['visible']) || $options['visible'] == TRUE) {
        unset($options['visible']);
        $result[$name] = $options;
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getComponent($name) {
    // We always store 'extra fields', whether they are visible or hidden.
    $extra_fields = field_info_extra_fields($this->targetEntityType, $this->bundle, $this->displayContext);
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
   * {@inheritdoc}
   */
  public function setComponent($name, array $options = array()) {
    // If no weight specified, make sure the field sinks at the bottom.
    if (!isset($options['weight'])) {
      $max = $this->getHighestWeight();
      $options['weight'] = isset($max) ? $max + 1 : 0;
    }

    if ($instance = field_info_instance($this->targetEntityType, $name, $this->bundle)) {
      $options = $this->pluginManager->prepareConfiguration($instance->getFieldType(), $options);

      // Clear the persisted plugin, if any.
      unset($this->plugins[$name]);
    }

    // We always store 'extra fields', whether they are visible or hidden.
    $extra_fields = field_info_extra_fields($this->targetEntityType, $this->bundle, $this->displayContext);
    if (isset($extra_fields[$name])) {
      $options['visible'] = TRUE;
    }

    $this->content[$name] = $options;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeComponent($name) {
    $extra_fields = field_info_extra_fields($this->targetEntityType, $this->bundle, $this->displayContext);
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
      unset($this->plugins[$name]);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
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
    $weights = array_merge($weights, module_invoke_all('field_info_max_weight', $this->targetEntityType, $this->bundle, $this->displayContext, $this->mode));

    return $weights ? max($weights) : NULL;
  }

}
