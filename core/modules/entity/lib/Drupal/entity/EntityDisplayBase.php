<?php

/**
 * @file
 * Contains \Drupal\entity\EntityDisplayBase.
 */

namespace Drupal\entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Base class for config entity types that store configuration for entity forms
 * and displays.
 */
abstract class EntityDisplayBase extends ConfigEntityBase implements EntityDisplayInterface {

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
   * A partial entity, created via _field_create_entity_from_ids() from
   * $targetEntityType and $bundle.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   *
   * @todo Remove when getFieldDefinition() is fixed to not need it.
   *   https://drupal.org/node/2114707
   */
  private $targetEntity;

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
    if (\Drupal::entityManager()->hasController($this->targetEntityType, 'view_builder')) {
      \Drupal::entityManager()->getViewBuilder($this->targetEntityType)->resetCache();
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
    // Until https://drupal.org/node/2144919 allows base fields to be configured
    // in the UI, many base fields are also still registered as "extra fields"
    // to keep appearing in the "Manage (form) display" screens.
    // - Field UI still treats them as "base fields", saving only the weight
    //   and visibility flag in the EntityDisplay.
    // - For some of them (e.g. node title), the custom rendering code has been
    //   removed in favor of regular widgets/formatters. Their display options
    //   are "upgraded" to those of a field (widget/formatter + settings) at
    //   runtime using hook_entity_display_alter().
    // The getComponent() / setComponent() methods handle this by treating
    // components as "extra fields" if they are registered as such, *and* if
    // their display options contain no 'type' entry specifying a widget or
    // formatter.
    // @todo Cleanup after https://drupal.org/node/2144919 is fixed.
    $extra_fields = field_info_extra_fields($this->targetEntityType, $this->bundle, $this->displayContext);
    if (isset($extra_fields[$name]) && !isset($this->content[$name]['type'])) {
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
    elseif (isset($this->content[$name])) {
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
    // See remark in getComponent().
    // @todo Cleanup after https://drupal.org/node/2144919 is fixed.
    $extra_fields = field_info_extra_fields($this->targetEntityType, $this->bundle, $this->displayContext);
    if (isset($extra_fields[$name]) && !isset($options['type'])) {
      $options['visible'] = TRUE;
    }
    elseif ($field_definition = $this->getFieldDefinition($name)) {
      $options = $this->pluginManager->prepareConfiguration($field_definition->getType(), $options);
    }

    // Clear the persisted plugin, if any.
    unset($this->plugins[$name]);

    $this->content[$name] = $options;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeComponent($name) {
    // See remark in getComponent().
    // @todo Cleanup after https://drupal.org/node/2144919 is fixed.
    $extra_fields = field_info_extra_fields($this->targetEntityType, $this->bundle, $this->displayContext);
    if (isset($extra_fields[$name]) && !isset($this->content[$name]['type'])) {
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
    }

    unset($this->plugins[$name]);

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

  /**
   * Returns the field definition of a field.
   */
  protected function getFieldDefinition($field_name) {
    // @todo Replace this entire implementation with
    //   \Drupal::entityManager()->getFieldDefinition() when it can hand the
    //   $instance objects - https://drupal.org/node/2114707
    if (!isset($this->targetEntity)) {
      $this->targetEntity = _field_create_entity_from_ids((object) array('entity_type' => $this->targetEntityType, 'bundle' => $this->bundle, 'entity_id' => NULL));
    }
    if (($this->targetEntity instanceof ContentEntityInterface) && $this->targetEntity->hasField($field_name)) {
      return $this->targetEntity->get($field_name)->getFieldDefinition();
    }
  }
}
