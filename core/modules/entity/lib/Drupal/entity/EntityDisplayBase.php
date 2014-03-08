<?php

/**
 * @file
 * Contains \Drupal\entity\EntityDisplayBase.
 */

namespace Drupal\entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;

/**
 * Provides a common base class for entity view and form displays.
 */
abstract class EntityDisplayBase extends ConfigEntityBase implements EntityDisplayInterface {

  /**
   * Unique ID for the config entity.
   *
   * @var string
   */
  public $id;

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
   * A list of field definitions eligible for configuration in this display.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface[]
   */
  protected $fieldDefinitions;

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
   * List of components that are set to be hidden.
   *
   * @var array
   */
  protected $hidden = array();

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
    //   throw new \InvalidArgumentException('Missing required properties for an EntityDisplayBase entity.');
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

    $this->init();
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
  public function preSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    // Sort elements by weight before saving.
    uasort($this->content, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    // Reset the render cache for the target entity type.
    if (\Drupal::entityManager()->hasController($this->targetEntityType, 'view_builder')) {
      \Drupal::entityManager()->getViewBuilder($this->targetEntityType)->resetCache();
    }
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
      'hidden',
      'status',
    );
    $properties = array();
    foreach ($names as $name) {
      $properties[$name] = $this->get($name);
    }

    // Do not store options for fields whose display is not set to be
    // configurable.
    foreach ($this->getFieldDefinitions() as $field_name => $definition) {
      if (!$definition->isDisplayConfigurable($this->displayContext)) {
        unset($properties['content'][$field_name]);
        unset($properties['hidden'][$field_name]);
      }
    }

    return $properties;
  }

  /**
   * Initializes the display.
   *
   * This fills in default options for components:
   * - that are not explicitly known as either "visible" or "hidden" in the
   *   display,
   * - or that are not supposed to be configurable.
   */
  protected function init() {
    // Fill in defaults for extra fields.
    $extra_fields = field_info_extra_fields($this->targetEntityType, $this->bundle, ($this->displayContext == 'view' ? 'display' : $this->displayContext));
    foreach ($extra_fields as $name => $definition) {
      if (!isset($this->content[$name]) && !isset($this->hidden[$name])) {
        // Extra fields are visible by default unless they explicitly say so.
        if (!isset($definition['visible']) || $definition['visible'] == TRUE) {
          $this->content[$name] = array(
            'weight' => $definition['weight']
          );
        }
        else {
          $this->hidden[$name] = TRUE;
        }
      }
    }

    // Fill in defaults for fields.
    $fields = $this->getFieldDefinitions();
    foreach ($fields as $name => $definition) {
      if (!$definition->isDisplayConfigurable($this->displayContext) || (!isset($this->content[$name]) && !isset($this->hidden[$name]))) {
        $options = $definition->getDisplayOptions($this->displayContext);

        if (!empty($options['type']) && $options['type'] == 'hidden') {
          $this->hidden[$name] = TRUE;
        }
        elseif ($options) {
          $this->content[$name] = $this->pluginManager->prepareConfiguration($definition->getType(), $options);
        }
        // Note: (base) fields that do not specify display options are not
        // tracked in the display at all, in order to avoid cluttering the
        // configuration that gets saved back.
      }
    }
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
    return $this->content;
  }

  /**
   * {@inheritdoc}
   */
  public function getComponent($name) {
    return isset($this->content[$name]) ? $this->content[$name] : NULL;
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

    // For a field, fill in default options.
    if ($field_definition = $this->getFieldDefinition($name)) {
      $options = $this->pluginManager->prepareConfiguration($field_definition->getType(), $options);
    }

    $this->content[$name] = $options;
    unset($this->hidden[$name]);
    unset($this->plugins[$name]);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeComponent($name) {
    $this->hidden[$name] = TRUE;
    unset($this->content[$name]);
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
    $weights = array_merge($weights, \Drupal::moduleHandler()->invokeAll('field_info_max_weight', array($this->targetEntityType, $this->bundle, $this->displayContext, $this->mode)));

    return $weights ? max($weights) : NULL;
  }

  /**
   * Returns the field definition of a field.
   */
  protected function getFieldDefinition($field_name) {
    $definitions = $this->getFieldDefinitions();
    return isset($definitions[$field_name]) ? $definitions[$field_name] : NULL;
  }

  /**
   * Returns the definitions of the fields that are candidate for display.
   */
  protected function getFieldDefinitions() {
    // Entity displays are sometimes created for non-content entities.
    // @todo Prevent this in https://drupal.org/node/2095195.
    if (!\Drupal::entityManager()->getDefinition($this->targetEntityType)->isSubclassOf('\Drupal\Core\Entity\ContentEntityInterface')) {
      return array();
    }

    if (!isset($this->fieldDefinitions)) {
      $definitions = \Drupal::entityManager()->getFieldDefinitions($this->targetEntityType, $this->bundle);

      // The display only cares about fields that specify display options.
      // Discard base fields that are not rendered through formatters / widgets.
      $display_context = $this->displayContext;
      $this->fieldDefinitions = array_filter($definitions, function (FieldDefinitionInterface $definition) use ($display_context) {
        return $definition->getDisplayOptions($display_context);
      });
    }

    return $this->fieldDefinitions;
  }

}
