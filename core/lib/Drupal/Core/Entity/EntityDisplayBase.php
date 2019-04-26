<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;

/**
 * Provides a common base class for entity view and form displays.
 */
abstract class EntityDisplayBase extends ConfigEntityBase implements EntityDisplayInterface {

  /**
   * The 'mode' for runtime EntityDisplay objects used to render entities with
   * arbitrary display options rather than a configured view mode or form mode.
   *
   * @todo Prevent creation of a mode with this ID
   *   https://www.drupal.org/node/2410727
   */
  const CUSTOM_MODE = '_custom';

  /**
   * Unique ID for the config entity.
   *
   * @var string
   */
  protected $id;

  /**
   * Entity type to be displayed.
   *
   * @var string
   */
  protected $targetEntityType;

  /**
   * Bundle to be displayed.
   *
   * @var string
   */
  protected $bundle;

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
  protected $mode = self::CUSTOM_MODE;

  /**
   * Whether this display is enabled or not. If the entity (form) display
   * is disabled, we'll fall back to the 'default' display.
   *
   * @var bool
   */
  protected $status;

  /**
   * List of component display options, keyed by component name.
   *
   * @var array
   */
  protected $content = [];

  /**
   * List of components that are set to be hidden.
   *
   * @var array
   */
  protected $hidden = [];

  /**
   * The original view or form mode that was requested (case of view/form modes
   * being configured to fall back to the 'default' display).
   *
   * @var string
   */
  protected $originalMode;

  /**
   * The plugin objects used for this display, keyed by field name.
   *
   * @var array
   */
  protected $plugins = [];

  /**
   * Context in which this entity will be used (e.g. 'view', 'form').
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
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    if (!isset($values['targetEntityType']) || !isset($values['bundle'])) {
      throw new \InvalidArgumentException('Missing required properties for an EntityDisplay entity.');
    }

    if (!$this->entityTypeManager()->getDefinition($values['targetEntityType'])->entityClassImplements(FieldableEntityInterface::class)) {
      throw new \InvalidArgumentException('EntityDisplay entities can only handle fieldable entity types.');
    }

    $this->renderer = \Drupal::service('renderer');

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
   * Initializes the display.
   *
   * This fills in default options for components:
   * - that are not explicitly known as either "visible" or "hidden" in the
   *   display,
   * - or that are not supposed to be configurable.
   */
  protected function init() {
    // Only populate defaults for "official" view modes and form modes.
    if ($this->mode !== static::CUSTOM_MODE) {
      $default_region = $this->getDefaultRegion();
      // Fill in defaults for extra fields.
      $context = $this->displayContext == 'view' ? 'display' : $this->displayContext;
      $extra_fields = \Drupal::service('entity_field.manager')->getExtraFields($this->targetEntityType, $this->bundle);
      $extra_fields = isset($extra_fields[$context]) ? $extra_fields[$context] : [];
      foreach ($extra_fields as $name => $definition) {
        if (!isset($this->content[$name]) && !isset($this->hidden[$name])) {
          // Extra fields are visible by default unless they explicitly say so.
          if (!isset($definition['visible']) || $definition['visible'] == TRUE) {
            $this->setComponent($name, [
              'weight' => $definition['weight'],
            ]);
          }
          else {
            $this->removeComponent($name);
          }
        }
        // Ensure extra fields have a 'region'.
        if (isset($this->content[$name])) {
          $this->content[$name] += ['region' => $default_region];
        }
      }

      // Fill in defaults for fields.
      $fields = $this->getFieldDefinitions();
      foreach ($fields as $name => $definition) {
        if (!$definition->isDisplayConfigurable($this->displayContext) || (!isset($this->content[$name]) && !isset($this->hidden[$name]))) {
          $options = $definition->getDisplayOptions($this->displayContext);

          // @todo Remove handling of 'type' in https://www.drupal.org/node/2799641.
          if (!isset($options['region']) && !empty($options['type']) && $options['type'] === 'hidden') {
            $options['region'] = 'hidden';
            @trigger_error("Specifying 'type' => 'hidden' is deprecated, use 'region' => 'hidden' instead.", E_USER_DEPRECATED);
          }

          if (!empty($options['region']) && $options['region'] === 'hidden') {
            $this->removeComponent($name);
          }
          elseif ($options) {
            $options += ['region' => $default_region];
            $this->setComponent($name, $options);
          }
          // Note: (base) fields that do not specify display options are not
          // tracked in the display at all, in order to avoid cluttering the
          // configuration that gets saved back.
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeId() {
    return $this->targetEntityType;
  }

  /**
   * {@inheritdoc}
   */
  public function getMode() {
    return $this->get('mode');
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalMode() {
    return $this->get('originalMode');
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetBundle() {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetBundle($bundle) {
    $this->set('bundle', $bundle);
    return $this;
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
  public function preSave(EntityStorageInterface $storage) {
    // Ensure that a region is set on each component.
    foreach ($this->getComponents() as $name => $component) {
      $this->handleHiddenType($name, $component);
      // Ensure that a region is set.
      if (isset($this->content[$name]) && !isset($component['region'])) {
        // Directly set the component to bypass other changes in setComponent().
        $this->content[$name]['region'] = $this->getDefaultRegion();
      }
    }

    ksort($this->content);
    ksort($this->hidden);
    parent::preSave($storage);
  }

  /**
   * Handles a component type of 'hidden'.
   *
   * @deprecated This method exists only for backwards compatibility.
   *
   * @todo Remove this in https://www.drupal.org/node/2799641.
   *
   * @param string $name
   *   The name of the component.
   * @param array $component
   *   The component array.
   */
  protected function handleHiddenType($name, array $component) {
    if (!isset($component['region']) && isset($component['type']) && $component['type'] === 'hidden') {
      $this->removeComponent($name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $target_entity_type = $this->entityTypeManager()->getDefinition($this->targetEntityType);

    // Create dependency on the bundle.
    $bundle_config_dependency = $target_entity_type->getBundleConfigDependency($this->bundle);
    $this->addDependency($bundle_config_dependency['type'], $bundle_config_dependency['name']);

    // If field.module is enabled, add dependencies on 'field_config' entities
    // for both displayed and hidden fields. We intentionally leave out base
    // field overrides, since the field still exists without them.
    if (\Drupal::moduleHandler()->moduleExists('field')) {
      $components = $this->content + $this->hidden;
      $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($this->targetEntityType, $this->bundle);
      foreach (array_intersect_key($field_definitions, $components) as $field_name => $field_definition) {
        if ($field_definition instanceof ConfigEntityInterface && $field_definition->getEntityTypeId() == 'field_config') {
          $this->addDependency('config', $field_definition->getConfigDependencyName());
        }
      }
    }

    // Depend on configured modes.
    if ($this->mode != 'default') {
      $mode_entity = $this->entityTypeManager()->getStorage('entity_' . $this->displayContext . '_mode')->load($target_entity_type->id() . '.' . $this->mode);
      $this->addDependency('config', $mode_entity->getConfigDependencyName());
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $properties = parent::toArray();
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
  public function setComponent($name, array $options = []) {
    // If no weight specified, make sure the field sinks at the bottom.
    if (!isset($options['weight'])) {
      $max = $this->getHighestWeight();
      $options['weight'] = isset($max) ? $max + 1 : 0;
    }

    // For a field, fill in default options.
    if ($field_definition = $this->getFieldDefinition($name)) {
      $options = $this->pluginManager->prepareConfiguration($field_definition->getType(), $options);
    }

    // Ensure we always have an empty settings and array.
    $options += ['settings' => [], 'third_party_settings' => []];

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
    $weights = [];

    // Collect weights for the components in the display.
    foreach ($this->content as $options) {
      if (isset($options['weight'])) {
        $weights[] = $options['weight'];
      }
    }

    // Let other modules feedback about their own additions.
    $weights = array_merge($weights, \Drupal::moduleHandler()->invokeAll('field_info_max_weight', [$this->targetEntityType, $this->bundle, $this->displayContext, $this->mode]));

    return $weights ? max($weights) : NULL;
  }

  /**
   * Gets the field definition of a field.
   */
  protected function getFieldDefinition($field_name) {
    $definitions = $this->getFieldDefinitions();
    return isset($definitions[$field_name]) ? $definitions[$field_name] : NULL;
  }

  /**
   * Gets the definitions of the fields that are candidate for display.
   */
  protected function getFieldDefinitions() {
    if (!isset($this->fieldDefinitions)) {
      $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($this->targetEntityType, $this->bundle);
      // For "official" view modes and form modes, ignore fields whose
      // definition states they should not be displayed.
      if ($this->mode !== static::CUSTOM_MODE) {
        $definitions = array_filter($definitions, [$this, 'fieldHasDisplayOptions']);
      }
      $this->fieldDefinitions = $definitions;
    }

    return $this->fieldDefinitions;
  }

  /**
   * Determines if a field has options for a given display.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $definition
   *   A field definition.
   * @return array|null
   */
  private function fieldHasDisplayOptions(FieldDefinitionInterface $definition) {
    // The display only cares about fields that specify display options.
    // Discard base fields that are not rendered through formatters / widgets.
    return $definition->getDisplayOptions($this->displayContext);
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);
    foreach ($dependencies['config'] as $entity) {
      if ($entity->getEntityTypeId() == 'field_config') {
        // Remove components for fields that are being deleted.
        $this->removeComponent($entity->getName());
        unset($this->hidden[$entity->getName()]);
        $changed = TRUE;
      }
    }
    foreach ($this->getComponents() as $name => $component) {
      if ($renderer = $this->getRenderer($name)) {
        if (in_array($renderer->getPluginDefinition()['provider'], $dependencies['module'])) {
          // Revert to the defaults if the plugin that supplies the widget or
          // formatter depends on a module that is being uninstalled.
          $this->setComponent($name);
          $changed = TRUE;
        }

        // Give this component the opportunity to react on dependency removal.
        $component_removed_dependencies = $this->getPluginRemovedDependencies($renderer->calculateDependencies(), $dependencies);
        if ($component_removed_dependencies) {
          if ($renderer->onDependencyRemoval($component_removed_dependencies)) {
            // Update component settings to reflect changes.
            $component['settings'] = $renderer->getSettings();
            $component['third_party_settings'] = [];
            foreach ($renderer->getThirdPartyProviders() as $module) {
              $component['third_party_settings'][$module] = $renderer->getThirdPartySettings($module);
            }
            $this->setComponent($name, $component);
            $changed = TRUE;
          }
          // If there are unresolved deleted dependencies left, disable this
          // component to avoid the removal of the entire display entity.
          if ($this->getPluginRemovedDependencies($renderer->calculateDependencies(), $dependencies)) {
            $this->removeComponent($name);
            $arguments = [
              '@display' => (string) $this->getEntityType()->getLabel(),
              '@id' => $this->id(),
              '@name' => $name,
            ];
            $this->getLogger()->warning("@display '@id': Component '@name' was disabled because its settings depend on removed dependencies.", $arguments);
            $changed = TRUE;
          }
        }
      }
    }
    return $changed;
  }

  /**
   * Returns the plugin dependencies being removed.
   *
   * The function recursively computes the intersection between all plugin
   * dependencies and all removed dependencies.
   *
   * Note: The two arguments do not have the same structure.
   *
   * @param array[] $plugin_dependencies
   *   A list of dependencies having the same structure as the return value of
   *   ConfigEntityInterface::calculateDependencies().
   * @param array[] $removed_dependencies
   *   A list of dependencies having the same structure as the input argument of
   *   ConfigEntityInterface::onDependencyRemoval().
   *
   * @return array
   *   A recursively computed intersection.
   *
   * @see \Drupal\Core\Config\Entity\ConfigEntityInterface::calculateDependencies()
   * @see \Drupal\Core\Config\Entity\ConfigEntityInterface::onDependencyRemoval()
   */
  protected function getPluginRemovedDependencies(array $plugin_dependencies, array $removed_dependencies) {
    $intersect = [];
    foreach ($plugin_dependencies as $type => $dependencies) {
      if ($removed_dependencies[$type]) {
        // Config and content entities have the dependency names as keys while
        // module and theme dependencies are indexed arrays of dependency names.
        // @see \Drupal\Core\Config\ConfigManager::callOnDependencyRemoval()
        if (in_array($type, ['config', 'content'])) {
          $removed = array_intersect_key($removed_dependencies[$type], array_flip($dependencies));
        }
        else {
          $removed = array_values(array_intersect($removed_dependencies[$type], $dependencies));
        }
        if ($removed) {
          $intersect[$type] = $removed;
        }
      }
    }
    return $intersect;
  }

  /**
   * Gets the default region.
   *
   * @return string
   *   The default region for this display.
   */
  protected function getDefaultRegion() {
    return 'content';
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    // Only store the definition, not external objects or derived data.
    $keys = array_keys($this->toArray());
    // In addition, we need to keep the entity type and the "is new" status.
    $keys[] = 'entityTypeId';
    $keys[] = 'enforceIsNew';
    // Keep track of the serialized keys, to avoid calling toArray() again in
    // __wakeup(). Because of the way __sleep() works, the data has to be
    // present in the object to be included in the serialized values.
    $keys[] = '_serializedKeys';
    $this->_serializedKeys = $keys;
    return $keys;
  }

  /**
   * {@inheritdoc}
   */
  public function __wakeup() {
    // Determine what were the properties from toArray() that were saved in
    // __sleep().
    $keys = $this->_serializedKeys;
    unset($this->_serializedKeys);
    $values = array_intersect_key(get_object_vars($this), array_flip($keys));
    // Run those values through the __construct(), as if they came from a
    // regular entity load.
    $this->__construct($values, $this->entityTypeId);
  }

  /**
   * Provides the 'system' channel logger service.
   *
   * @return \Psr\Log\LoggerInterface
   *   The 'system' channel logger.
   */
  protected function getLogger() {
    return \Drupal::logger('system');
  }

}
