<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin;

use Drupal\ckeditor5\HTMLRestrictionsUtilities;
use Drupal\Component\Assertion\Inspector;
use Drupal\Component\Plugin\Definition\PluginDefinition;
use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Config\Schema\SchemaCheckTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides an implementation of a CKEditor 5 plugin definition.
 */
final class CKEditor5PluginDefinition extends PluginDefinition implements PluginDefinitionInterface {

  use SchemaCheckTrait;

  /**
   * The CKEditor 5 aspects of the plugin definition.
   *
   * @var array
   */
  private $ckeditor5;

  /**
   * The Drupal aspects of the plugin definition.
   *
   * @var array
   */
  private $drupal;

  /**
   * CKEditor5PluginDefinition constructor.
   *
   * @param array $definition
   *   An array of values from the annotation/YAML.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(array $definition) {
    $this->id = $id = $definition['id'];

    $expected_prefix = sprintf("%s_", $definition['provider']);
    if (strpos($id, $expected_prefix) !== 0) {
      throw new InvalidPluginDefinitionException($id, sprintf('The "%s" CKEditor 5 plugin definition must have a plugin ID that starts with "%s".', $id, $expected_prefix));
    }
    $this->provider = $definition['provider'];

    static::validateCKEditor5Aspects($id, $definition);
    $this->ckeditor5 = $definition['ckeditor5'];

    $this->validateDrupalAspects($id, $definition);
    $this->drupal = $definition['drupal'];
  }

  /**
   * Gets an array representation of this CKEditor 5 plugin definition.
   *
   * @return array
   */
  public function toArray(): array {
    return [
      'id' => $this->id(),
      'provider' => $this->provider,
      'ckeditor5' => $this->ckeditor5,
      'drupal' => $this->drupal,
    ];
  }

  /**
   * Validates the CKEditor 5 aspects of the CKEditor 5 plugin definition.
   *
   * @param string $id
   *   The plugin ID, for use in exception messages.
   * @param array $definition
   *   The plugin definition to validate.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  private static function validateCKEditor5Aspects(string $id, array $definition): void {
    if (!isset($definition['ckeditor5'])) {
      throw new InvalidPluginDefinitionException($id, sprintf('The "%s" CKEditor 5 plugin definition must contain a "ckeditor5" key.', $id));
    }

    if (!isset($definition['ckeditor5']['plugins'])) {
      throw new InvalidPluginDefinitionException($id, sprintf('The "%s" CKEditor 5 plugin definition must contain a "ckeditor5.plugins" key.', $id));
    }
  }

  /**
   * Validates the Drupal aspects of the CKEditor 5 plugin definition.
   *
   * @param string $id
   *   The plugin ID, for use in exception messages.
   * @param array $definition
   *   The plugin definition to validate.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  private function validateDrupalAspects(string $id, array $definition): void {
    if (!isset($definition['drupal'])) {
      throw new InvalidPluginDefinitionException($id, sprintf('The "%s" CKEditor 5 plugin definition must contain a "drupal" key.', $id));
    }

    // Without a label, the CKEditor 5 UI, validation constraints et cetera
    // cannot be as informative in guiding the end user.
    if (!isset($definition['drupal']['label'])) {
      throw new InvalidPluginDefinitionException($id, sprintf('The "%s" CKEditor 5 plugin definition must contain a "drupal.label" key.', $id));
    }
    elseif (!is_string($definition['drupal']['label']) && !$definition['drupal']['label'] instanceof TranslatableMarkup) {
      throw new InvalidPluginDefinitionException($id, sprintf('The "%s" CKEditor 5 plugin definition has a "drupal.label" value that is not a string nor a TranslatableMarkup instance.', $id));
    }

    // Without accurate and complete metadata about what HTML elements a
    // CKEditor 5 plugin supports, Drupal cannot ensure a complete and accurate
    // upgrade path.
    if (!isset($definition['drupal']['elements'])) {
      throw new InvalidPluginDefinitionException($id, sprintf('The "%s" CKEditor 5 plugin definition must contain a "drupal.elements" key.', $id));
    }
    elseif ($definition['drupal']['elements'] !== FALSE && !(is_array($definition['drupal']['elements']) && !empty($definition['drupal']['elements']) && Inspector::assertAllStrings($definition['drupal']['elements']))) {
      throw new InvalidPluginDefinitionException($id, sprintf('The "%s" CKEditor 5 plugin definition has a "drupal.elements" value that is neither a list of HTML tags/attributes nor false.', $id));
    }
    elseif (is_array($definition['drupal']['elements'])) {
      foreach ($definition['drupal']['elements'] as $index => $element) {
        // ckeditor5_sourceEditing is the edge case here: it is the only plugin
        // that is allowed to return a superset. It's a special case because it
        // is through configuring this particular plugin that additional HTML
        // tags can be allowed.
        // Even though its plugin definition says '<*>' is supported, this is a
        // little lie to convey that this plugin is capable of supporting any
        // HTML tag … but which ones are actually supported depends on the
        // configuration.
        // This also means that without any configuration, it does not support
        // any HTML tags.
        // @see \Drupal\ckeditor5\Plugin\CKEditor5PluginManager::getProvidedElements()
        if ($definition['id'] === 'ckeditor5_sourceEditing') {
          continue;
        }
        $parsed_elements = HTMLRestrictionsUtilities::allowedElementsStringToPluginElementsArray($element);
        if (count($parsed_elements) === 0) {
          throw new InvalidPluginDefinitionException($id, sprintf('The "%s" CKEditor 5 plugin definition has a value at "drupal.elements.%d" that is not an HTML tag with optional attributes: "%s". Expected structure: "<tag allowedAttribute="allowedValue1 allowedValue2">".', $id, $index, $element));
        }
        elseif (count($parsed_elements) > 1) {
          throw new InvalidPluginDefinitionException($id, sprintf('The "%s" CKEditor 5 plugin definition has a value at "drupal.elements.%d": multiple tags listed, should be one: "%s".', $id, $index, $element));
        }
      }
    }

    if (isset($definition['drupal']['class']) && !class_exists($definition['drupal']['class'])) {
      throw new InvalidPluginDefinitionException($id, sprintf('The CKEditor 5 "%s" provides a plugin class: "%s", but it does not exist.', $id, $definition['drupal']['class']));
    }
    elseif (isset($definition['drupal']['class']) && !in_array(CKEditor5PluginInterface::class, class_implements($definition['drupal']['class']))) {
      throw new InvalidPluginDefinitionException($id, sprintf('CKEditor 5 plugins must implement \Drupal\ckeditor5\Plugin\CKEditor5PluginInterface. "%s" does not.', $id));
    }
    elseif (in_array(CKEditor5PluginConfigurableInterface::class, class_implements($definition['drupal']['class'], TRUE))) {
      $default_configuration = (new \ReflectionClass($definition['drupal']['class']))
        ->newInstanceWithoutConstructor()
        ->defaultConfiguration();
      $typed_config = \Drupal::service('config.typed');
      if (!empty($default_configuration)) {
        $configuration_name = sprintf("ckeditor5.plugin.%s", $definition['id']);
        if (!$typed_config->hasConfigSchema($configuration_name)) {
          throw new InvalidPluginDefinitionException($id, sprintf('The "%s" CKEditor 5 plugin definition is configurable, has non-empty default configuration but has no config schema. Config schema is required for validation.', $id));
        }
        // TRICKY: SchemaCheckTrait::checkConfigSchema() dynamically adds a
        // 'langcode' key-value pair that is irrelevant here. Also,
        // ::checkValue() may (counter to its docs) trigger an exception.
        $this->configName = 'STRIP';
        $this->schema = $typed_config->createFromNameAndData($configuration_name, $default_configuration);
        $schema_errors = [];
        foreach ($default_configuration as $key => $value) {
          try {
            $schema_error = $this->checkValue($key, $value);
          }
          catch (\InvalidArgumentException $e) {
            $schema_error = [$key => $e->getMessage()];
          }
          $schema_errors = array_merge($schema_errors, $schema_error);
        }
        $formatted_schema_errors = [];
        foreach ($schema_errors as $key => $value) {
          $formatted_schema_errors[] = sprintf("[%s] %s", str_replace('STRIP:', '', $key), trim($value, '.'));
        }
        if (!empty($schema_errors)) {
          throw new InvalidPluginDefinitionException($id, sprintf('The "%s" CKEditor 5 plugin definition is configurable, but its default configuration does not match its config schema. The following errors were found: %s.', $id, implode(', ', $formatted_schema_errors)));
        }
      }
    }

    if ($definition['drupal']['admin_library'] !== FALSE) {
      [$extension, $library] = explode('/', $definition['drupal']['admin_library'], 2);
      if (\Drupal::service('library.discovery')->getLibraryByName($extension, $library) === FALSE) {
        throw new InvalidPluginDefinitionException($id, sprintf('The "%s" CKEditor 5 plugin definition has a "drupal.admin_library" key whose asset library "%s" does not exist.', $id, $definition['drupal']['admin_library']));
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\ckeditor5\Annotation\DrupalAspectsOfCKEditor5Plugin::$class
   */
  public function getClass() {
    return $this->drupal['class'];
  }

  /**
   * {@inheritdoc}
   */
  public function setClass($class) {
    $this->drupal['class'] = $class;
    return $this;
  }

  /**
   * Whether this plugin is configurable by the user.
   *
   * @return bool
   *   TRUE if it is configurable, FALSE otherwise.
   *
   * @see \Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface
   */
  public function isConfigurable(): bool {
    return is_subclass_of($this->getClass(), CKEditor5PluginConfigurableInterface::class);
  }

  /**
   * Gets the human-readable name of the CKEditor plugin.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *
   * @see \Drupal\ckeditor5\Annotation\DrupalAspectsOfCKEditor5Plugin::$label
   */
  public function label(): TranslatableMarkup {
    $label = $this->drupal['label'];
    if (!$label instanceof TranslatableMarkup) {
      $label = new TranslatableMarkup($label);
    }
    return $label;
  }

  /**
   * Gets the list of conditions to enable this plugin.
   *
   * @return array
   *   An array of conditions.
   *
   * @see \Drupal\ckeditor5\Annotation\DrupalAspectsOfCKEditor5Plugin::$conditions
   *
   * @throws \LogicException
   *   When called on a plugin definition that has no conditions.
   */
  public function getConditions(): array {
    if (!$this->hasConditions()) {
      throw new \LogicException('::getConditions() should only be called if ::hasConditions() returns TRUE.');
    }
    return $this->drupal['conditions'];
  }

  /**
   * Whether this plugin has conditions.
   *
   * @return bool
   *
   * @see \Drupal\ckeditor5\Annotation\DrupalAspectsOfCKEditor5Plugin::$conditions
   */
  public function hasConditions(): bool {
    return $this->drupal['conditions'] !== FALSE;
  }

  /**
   * Gets the list of toolbar items this plugin provides.
   *
   * @return array[]
   *   An array of toolbar items.
   *
   * @see \Drupal\ckeditor5\Annotation\DrupalAspectsOfCKEditor5Plugin::$toolbar_items
   */
  public function getToolbarItems(): array {
    return $this->drupal['toolbar_items'];
  }

  /**
   * Whether this plugin has toolbar items.
   *
   * @return bool
   *
   * @see \Drupal\ckeditor5\Annotation\DrupalAspectsOfCKEditor5Plugin::$toolbar_items
   */
  public function hasToolbarItems(): bool {
    return $this->getToolbarItems() !== [];
  }

  /**
   * Gets the asset library this plugin needs to be loaded.
   *
   * @return string
   *   An asset library ID.
   *
   * @see \Drupal\ckeditor5\Annotation\DrupalAspectsOfCKEditor5Plugin::$library
   *
   * @throws \LogicException
   *   When called on a plugin definition that has no library.
   */
  public function getLibrary(): string {
    if (!$this->hasLibrary()) {
      throw new \LogicException('::getLibrary() should only be called if ::hasLibrary() returns TRUE.');
    }
    return $this->drupal['library'];
  }

  /**
   * Whether this plugin has an asset library to load.
   *
   * @return bool
   *
   * @see \Drupal\ckeditor5\Annotation\DrupalAspectsOfCKEditor5Plugin::$library
   */
  public function hasLibrary(): bool {
    return $this->drupal['library'] !== FALSE;
  }

  /**
   * Gets the asset library this plugin needs to be loaded on the admin UI.
   *
   * @return string
   *   An asset library ID.
   *
   * @see \Drupal\ckeditor5\Annotation\DrupalAspectsOfCKEditor5Plugin::$admin_library
   *
   * @throws \LogicException
   *   When called on a plugin definition that has no admin library.
   */
  public function getAdminLibrary(): string {
    if (!$this->hasAdminLibrary()) {
      throw new \LogicException('::getAdminLibrary() should only be called if ::hasAdminLibrary() returns TRUE.');
    }
    return $this->drupal['admin_library'];
  }

  /**
   * Whether this plugin has an asset library to load on the admin UI.
   *
   * @return bool
   *
   * @see \Drupal\ckeditor5\Annotation\DrupalAspectsOfCKEditor5Plugin::$admin_library
   */
  public function hasAdminLibrary(): bool {
    return $this->drupal['admin_library'] !== FALSE;
  }

  /**
   * Gets the list of elements and attributes this plugin allows to create/edit.
   *
   * @return string[]|false
   *   FALSE if this plugin does not create/edit any elements or attributes.
   *   Otherwise a list.
   *
   * @see \Drupal\ckeditor5\Annotation\DrupalAspectsOfCKEditor5Plugin::$elements
   */
  public function getElements() {
    return $this->drupal['elements'];
  }

  /**
   * Whether this plugin allows creating/editing elements and attributes.
   *
   * @return bool
   *
   * @see \Drupal\ckeditor5\Annotation\DrupalAspectsOfCKEditor5Plugin::$elements
   */
  public function hasElements(): bool {
    return $this->getElements() !== FALSE;
  }

  /**
   * Gets the list of CKEditor 5 plugin classes this plugin needs to load.
   *
   * @return string[]
   *   CKEditor 5 plugin classes.
   *
   * @see \Drupal\ckeditor5\Annotation\CKEditor5AspectsOfCKEditor5Plugin::$plugins
   */
  public function getCKEditor5Plugins(): array {
    return $this->ckeditor5['plugins'];
  }

  /**
   * Whether this plugin loads CKEditor 5 plugin classes.
   *
   * @return bool
   *
   * @see \Drupal\ckeditor5\Annotation\CKEditor5AspectsOfCKEditor5Plugin::$plugins
   */
  public function hasCKEditor5Plugins(): bool {
    return $this->getCKEditor5Plugins() !== [];
  }

  /**
   * Gets keyed array of additional values for the CKEditor5 constructor config.
   *
   * @return array
   *   The CKEditor 5 constructor config.
   *
   * @see \Drupal\ckeditor5\Annotation\CKEditor5AspectsOfCKEditor5Plugin::$config
   */
  public function getCKEditor5Config(): array {
    return $this->ckeditor5['config'];
  }

  /**
   * Whether this plugin has additional values for the CKEditor5 constructor.
   *
   * @return bool
   *
   * @see \Drupal\ckeditor5\Annotation\CKEditor5AspectsOfCKEditor5Plugin::$config
   */
  public function hasCKEditor5Config(): bool {
    return $this->getCKEditor5Config() !== [];
  }

}
