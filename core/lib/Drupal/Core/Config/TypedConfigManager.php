<?php

namespace Drupal\Core\Config;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Schema\ConfigSchemaAlterException;
use Drupal\Core\Config\Schema\ConfigSchemaDiscovery;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Config\Schema\Undefined;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\TypedData\TypedDataManager;

/**
 * Manages config schema type plugins.
 */
class TypedConfigManager extends TypedDataManager implements TypedConfigManagerInterface {

  /**
   * A storage instance for reading configuration data.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * A storage instance for reading configuration schema data.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $schemaStorage;

  /**
   * The array of plugin definitions, keyed by plugin id.
   *
   * @var array
   */
  protected $definitions;

  /**
   * Creates a new typed configuration manager.
   *
   * @param \Drupal\Core\Config\StorageInterface $configStorage
   *   The storage object to use for reading schema data
   * @param \Drupal\Core\Config\StorageInterface $schemaStorage
   *   The storage object to use for reading schema data
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to use for caching the definitions.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   (optional) The class resolver.
   */
  public function __construct(StorageInterface $configStorage, StorageInterface $schemaStorage, CacheBackendInterface $cache, ModuleHandlerInterface $module_handler, ClassResolverInterface $class_resolver = NULL) {
    $this->configStorage = $configStorage;
    $this->schemaStorage = $schemaStorage;
    $this->setCacheBackend($cache, 'typed_config_definitions');
    $this->alterInfo('config_schema_info');
    $this->moduleHandler = $module_handler;
    $this->classResolver = $class_resolver ?: \Drupal::service('class_resolver');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new ConfigSchemaDiscovery($this->schemaStorage);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function get($name) {
    $data = $this->configStorage->read($name);
    if ($data === FALSE) {
      // For a typed config the data MUST exist.
      $data = [];
      trigger_error(new FormattableMarkup('Missing required data for typed configuration: @config', [
        '@config' => $name,
      ]), E_USER_ERROR);
    }
    return $this->createFromNameAndData($name, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function buildDataDefinition(array $definition, $value, $name = NULL, $parent = NULL) {
    // Add default values for data type and replace variables.
    $definition += ['type' => 'undefined'];

    $replace = [];
    $type = $definition['type'];
    if (strpos($type, ']')) {
      // Replace variable names in definition.
      $replace = is_array($value) ? $value : [];
      if (isset($parent)) {
        $replace['%parent'] = $parent;
      }
      if (isset($name)) {
        $replace['%key'] = $name;
      }
      $type = $this->resolveDynamicTypeName($type, $replace);
      // Remove the type from the definition so that it is replaced with the
      // concrete type from schema definitions.
      unset($definition['type']);
    }
    // Add default values from type definition.
    $definition += $this->getDefinitionWithReplacements($type, $replace);

    $data_definition = $this->createDataDefinition($definition['type']);

    // Pass remaining values from definition array to data definition.
    foreach ($definition as $key => $value) {
      if (!isset($data_definition[$key])) {
        $data_definition[$key] = $value;
      }
    }
    return $data_definition;
  }

  /**
   * Determines the typed config type for a plugin ID.
   *
   * @param string $base_plugin_id
   *   The plugin ID.
   * @param array $definitions
   *   An array of typed config definitions.
   *
   * @return string
   *   The typed config type for the given plugin ID.
   */
  protected function determineType($base_plugin_id, array $definitions) {
    if (isset($definitions[$base_plugin_id])) {
      $type = $base_plugin_id;
    }
    elseif (strpos($base_plugin_id, '.') && $name = $this->getFallbackName($base_plugin_id)) {
      // Found a generic name, replacing the last element by '*'.
      $type = $name;
    }
    else {
      // If we don't have definition, return the 'undefined' element.
      $type = 'undefined';
    }
    return $type;
  }

  /**
   * Gets a schema definition with replacements for dynamic type names.
   *
   * @param string $base_plugin_id
   *   A plugin ID.
   * @param array $replacements
   *   An array of replacements for dynamic type names.
   * @param bool $exception_on_invalid
   *   (optional) This parameter is passed along to self::getDefinition().
   *   However, self::getDefinition() does not respect this parameter, so it is
   *   effectively useless in this context.
   *
   * @return array
   *   A schema definition array.
   */
  protected function getDefinitionWithReplacements($base_plugin_id, array $replacements, $exception_on_invalid = TRUE) {
    $definitions = $this->getDefinitions();
    $type = $this->determineType($base_plugin_id, $definitions);
    $definition = $definitions[$type];
    // Check whether this type is an extension of another one and compile it.
    if (isset($definition['type'])) {
      $merge = $this->getDefinition($definition['type'], $exception_on_invalid);
      // Preserve integer keys on merge, so sequence item types can override
      // parent settings as opposed to adding unused second, third, etc. items.
      $definition = NestedArray::mergeDeepArray([$merge, $definition], TRUE);

      // Replace dynamic portions of the definition type.
      if (!empty($replacements) && strpos($definition['type'], ']')) {
        $sub_type = $this->determineType($this->resolveDynamicTypeName($definition['type'], $replacements), $definitions);
        $sub_definition = $definitions[$sub_type];
        if (isset($definitions[$sub_type]['type'])) {
          $sub_merge = $this->getDefinition($definitions[$sub_type]['type'], $exception_on_invalid);
          $sub_definition = NestedArray::mergeDeepArray([$sub_merge, $definitions[$sub_type]], TRUE);
        }
        // Merge the newly determined subtype definition with the original
        // definition.
        $definition = NestedArray::mergeDeepArray([$sub_definition, $definition], TRUE);
        $type = "$type||$sub_type";
      }
      // Unset type so we try the merge only once per type.
      unset($definition['type']);
      $this->definitions[$type] = $definition;
    }
    // Add type and default definition class.
    $definition += [
      'definition_class' => '\Drupal\Core\TypedData\DataDefinition',
      'type' => $type,
      'unwrap_for_canonical_representation' => TRUE,
    ];
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition($base_plugin_id, $exception_on_invalid = TRUE) {
    return $this->getDefinitionWithReplacements($base_plugin_id, [], $exception_on_invalid);
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    $this->schemaStorage->reset();
    parent::clearCachedDefinitions();
  }

  /**
   * Finds fallback configuration schema name.
   *
   * @param string $name
   *   Configuration name or key.
   *
   * @return null|string
   *   The resolved schema name for the given configuration name or key. Returns
   *   null if there is no schema name to fallback to. For example,
   *   breakpoint.breakpoint.module.toolbar.narrow will check for definitions in
   *   the following order:
   *     breakpoint.breakpoint.module.toolbar.*
   *     breakpoint.breakpoint.module.*.*
   *     breakpoint.breakpoint.module.*
   *     breakpoint.breakpoint.*.*.*
   *     breakpoint.breakpoint.*
   *     breakpoint.*.*.*.*
   *     breakpoint.*
   *   Colons are also used, for example,
   *   block.settings.system_menu_block:footer will check for definitions in the
   *   following order:
   *     block.settings.system_menu_block:*
   *     block.settings.*:*
   *     block.settings.*
   *     block.*.*:*
   *     block.*
   */
  public function findFallback(string $name): ?string {
    $fallback = $this->getFallbackName($name);
    assert($fallback === NULL || str_ends_with($fallback, '.*'));
    return $fallback;
  }

  /**
   * Gets fallback configuration schema name.
   *
   * @param string $name
   *   Configuration name or key.
   *
   * @return null|string
   *   The resolved schema name for the given configuration name or key.
   */
  protected function getFallbackName($name) {
    // Check for definition of $name with filesystem marker.
    $replaced = preg_replace('/([^\.:]+)([\.:\*]*)$/', '*\2', $name);
    if ($replaced != $name) {
      if (isset($this->definitions[$replaced])) {
        return $replaced;
      }
      else {
        // No definition for this level. Collapse multiple wildcards to a single
        // wildcard to see if there is a greedy match. For example,
        // breakpoint.breakpoint.*.* becomes
        // breakpoint.breakpoint.*
        $one_star = preg_replace('/\.([:\.\*]*)$/', '.*', $replaced);
        if ($one_star != $replaced && isset($this->definitions[$one_star])) {
          return $one_star;
        }
        // Check for next level. For example, if breakpoint.breakpoint.* has
        // been checked and no match found then check breakpoint.*.*
        return $this->getFallbackName($replaced);
      }
    }
  }

  /**
   * Replaces dynamic type expressions in configuration type.
   *
   * The configuration type name may contain one or more expressions to be
   * replaced, enclosed in square brackets like '[name]' or '[%parent.id]' and
   * will follow the replacement rules defined by the resolveExpression()
   * method.
   *
   * @param string $type
   *   Configuration type, potentially with expressions in square brackets.
   * @param array $data
   *   Configuration data for the element.
   *
   * @return string
   *   Configuration type name with all expressions resolved.
   */
  protected function resolveDynamicTypeName(string $type, array $data): string {
    // Parse the expressions in the dynamic type, if any.
    if (preg_match_all("/\[(.*)\]/U", $type, $matches)) {
      // Build our list of '[value]' => replacement.
      $replace = [];
      foreach (array_combine($matches[0], $matches[1]) as $key => $value) {
        $replace[$key] = $this->resolveExpression($value, $data);
      }
      return strtr($type, $replace);
    }
    else {
      // No expressions: nothing to resolve.
      return $type;
    }
  }

  /**
   * Replaces dynamic type expressions in configuration type.
   *
   * The configuration type name may contain one or more expressions to be
   * replaced, enclosed in square brackets like '[name]' or '[%parent.id]' and
   * will follow the replacement rules defined by the resolveExpression()
   * method.
   *
   * @param string $name
   *   Configuration type, potentially with expressions in square brackets.
   * @param array $data
   *   Configuration data for the element.
   *
   * @return string
   *   Configuration type name with all expressions resolved.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
   *   ::resolveDynamicTypeName() instead.
   *
   * @see https://www.drupal.org/node/3408266
   */
  protected function replaceName($name, $data) {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use ::resolveDynamicTypeName() instead. See https://www.drupal.org/node/3408266', E_USER_DEPRECATED);
    return $this->resolveDynamicTypeName($name, $data);
  }

  /**
   * Resolves a dynamic type expression using configuration data.
   *
   * Dynamic type names are nested configuration keys containing expressions to
   * be replaced by the value at the property path that the expression is
   * pointing at. The expression may contain the following special strings:
   * - '%key', will be replaced by the element's key.
   * - '%parent', to reference the parent element.
   * - '%type', to reference the schema definition type. Can only be used in
   *   combination with %parent.
   *
   * There may be nested configuration keys separated by dots or more complex
   * patterns like '%parent.name' which references the 'name' value of the
   * parent element.
   *
   * Example expressions:
   * - 'name.subkey', indicates a nested value of the current element.
   * - '%parent.name', will be replaced by the 'name' value of the parent.
   * - '%parent.%key', will be replaced by the parent element's key.
   * - '%parent.%type', will be replaced by the schema type of the parent.
   * - '%parent.%parent.%type', will be replaced by the schema type of the
   *   parent's parent.
   *
   * @param string $expression
   *   Expression to be resolved.
   * @param array $data
   *   Configuration data for the element.
   *
   * @return string
   *   The value the expression resolves to, or the given expression if it
   *   cannot be resolved.
   *
   * @todo Validate the expression in https://www.drupal.org/project/drupal/issues/3392903
   */
  protected function resolveExpression(string $expression, array $data): string {
    assert(!str_contains($expression, '[') && !str_contains($expression, ']'));
    $parts = explode('.', $expression);
    // Process each value part, one at a time.
    while ($name = array_shift($parts)) {
      if (!is_array($data) || !isset($data[$name])) {
        // Key not found, return original value
        return $expression;
      }
      elseif (!$parts) {
        $expression = $data[$name];
        if (is_bool($expression)) {
          $expression = (int) $expression;
        }
        // If no more parts left, this is the final property.
        return (string) $expression;
      }
      else {
        // Get nested value and continue processing.
        if ($name == '%parent') {
          /** @var \Drupal\Core\Config\Schema\ArrayElement $parent */
          // Switch replacement values with values from the parent.
          $parent = $data['%parent'];
          $data = $parent->getValue();
          $data['%type'] = $parent->getDataDefinition()->getDataType();
          // The special %parent and %key values now need to point one level up.
          if ($new_parent = $parent->getParent()) {
            $data['%parent'] = $new_parent;
            $data['%key'] = $new_parent->getName();
          }
        }
        else {
          $data = $data[$name];
        }
      }
    }

    // Satisfy PHPStan, which cannot interpret the loop.
    return $expression;
  }

  /**
   * Resolves a dynamic type expression using configuration data.
   *
   * Dynamic type names are nested configuration keys containing expressions to
   * be replaced by the value at the property path that the expression is
   * pointing at. The expression may contain the following special strings:
   * - '%key', will be replaced by the element's key.
   * - '%parent', to reference the parent element.
   * - '%type', to reference the schema definition type. Can only be used in
   *   combination with %parent.
   *
   * There may be nested configuration keys separated by dots or more complex
   * patterns like '%parent.name' which references the 'name' value of the
   * parent element.
   *
   * Example expressions:
   * - 'name.subkey', indicates a nested value of the current element.
   * - '%parent.name', will be replaced by the 'name' value of the parent.
   * - '%parent.%key', will be replaced by the parent element's key.
   * - '%parent.%type', will be replaced by the schema type of the parent.
   * - '%parent.%parent.%type', will be replaced by the schema type of the
   *   parent's parent.
   *
   * @param string $value
   *   Expression to be resolved.
   * @param array $data
   *   Configuration data for the element.
   *
   * @return string
   *   The value the expression resolves to, or the given expression if it
   *   cannot be resolved.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
   *   ::resolveExpression() instead.
   *
   * @see https://www.drupal.org/node/3408266
   */
  protected function replaceVariable($value, $data) {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use ::resolveExpression() instead. See https://www.drupal.org/node/3408266', E_USER_DEPRECATED);
    return $this->resolveExpression($value, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function hasConfigSchema($name) {
    // The schema system falls back on the Undefined class for unknown types.
    $definition = $this->getDefinition($name);
    return is_array($definition) && ($definition['class'] != Undefined::class);
  }

  /**
   * {@inheritdoc}
   */
  protected function alterDefinitions(&$definitions) {
    $discovered_schema = array_keys($definitions);
    parent::alterDefinitions($definitions);
    $altered_schema = array_keys($definitions);
    if ($discovered_schema != $altered_schema) {
      $added_keys = implode(',', array_diff($altered_schema, $discovered_schema));
      $removed_keys = implode(',', array_diff($discovered_schema, $altered_schema));
      if (!empty($added_keys) && !empty($removed_keys)) {
        $message = "Invoking hook_config_schema_info_alter() has added ($added_keys) and removed ($removed_keys) schema definitions";
      }
      elseif (!empty($added_keys)) {
        $message = "Invoking hook_config_schema_info_alter() has added ($added_keys) schema definitions";
      }
      else {
        $message = "Invoking hook_config_schema_info_alter() has removed ($removed_keys) schema definitions";
      }
      throw new ConfigSchemaAlterException($message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createFromNameAndData($config_name, array $config_data) {
    $definition = $this->getDefinition($config_name);
    $data_definition = $this->buildDataDefinition($definition, $config_data);
    return $this->create($data_definition, $config_data, $config_name);
  }

}
