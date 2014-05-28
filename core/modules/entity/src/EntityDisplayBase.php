<?php

/**
 * @file
 * Contains \Drupal\entity\EntityDisplayBase.
 */

namespace Drupal\entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\field\Entity\FieldInstanceConfig;

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
  public function preSave(EntityStorageInterface $storage, $update = TRUE) {
    // Sort elements by weight before saving.
    uasort($this->content, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    parent::preSave($storage, $update);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $target_entity_type = \Drupal::entityManager()->getDefinition($this->targetEntityType);

    $bundle_entity_type_id = $target_entity_type->getBundleEntityType();
    if ($bundle_entity_type_id != 'bundle') {
      // If the target entity type uses entities to manage its bundles then
      // depend on the bundle entity.
      $bundle_entity = \Drupal::entityManager()->getStorage($bundle_entity_type_id)->load($this->bundle);
      $this->addDependency('entity', $bundle_entity->getConfigDependencyName());
    }
    else {
      // Depend on the provider of the entity type.
      $this->addDependency('module', $target_entity_type->getProvider());
    }
    // Create dependencies on both hidden and visible fields.
    $fields = $this->content + $this->hidden;
    foreach ($fields as $field_name => $component) {
      $field_instance = FieldInstanceConfig::loadByName($this->targetEntityType, $this->bundle, $field_name);
      if ($field_instance) {
        $this->addDependency('entity', $field_instance->getConfigDependencyName());
      }
      // Create a dependency on the module that provides the formatter or
      // widget.
      if (isset($component['type']) && $definition = $this->pluginManager->getDefinition($component['type'], FALSE)) {
        $this->addDependency('module', $definition['provider']);
      }
    }
    // Depend on configured modes.
    if ($this->mode != 'default') {
      $mode_entity = \Drupal::entityManager()->getStorage($this->displayContext . '_mode')->load($target_entity_type->id() . '.' . $this->mode);
      $this->addDependency('entity', $mode_entity->getConfigDependencyName());
    }
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    // Reset the render cache for the target entity type.
    if (\Drupal::entityManager()->hasController($this->targetEntityType, 'view_builder')) {
      \Drupal::entityManager()->getViewBuilder($this->targetEntityType)->resetCache();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $names = array(
      'uuid',
      'targetEntityType',
      'bundle',
      'mode',
      'content',
      'hidden',
      'status',
      'dependencies'
    );
    $properties = array(
      'id' => $this->id(),
    );
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
    $context = $this->displayContext == 'view' ? 'display' : $this->displayContext;
    $extra_fields = \Drupal::entityManager()->getExtraFields($this->targetEntityType, $this->bundle);
    $extra_fields = isset($extra_fields[$context]) ? $extra_fields[$context] : array();
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
      $this->fieldDefinitions = array_filter($definitions, array($this, 'fieldHasDisplayOptions'));
    }

    return $this->fieldDefinitions;
  }

  /**
   * Determines if a field has options for a given display.
   *
   * @param FieldDefinitionInterface $definition
   *   A field instance definition.
   * @return array|null
   */
  private function fieldHasDisplayOptions(FieldDefinitionInterface $definition) {
    // The display only cares about fields that specify display options.
    // Discard base fields that are not rendered through formatters / widgets.
    return $definition->getDisplayOptions($this->displayContext);
  }

}
