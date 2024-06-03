<?php

namespace Drupal\Core\Config;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Schema\ConfigSchemaAlterException;
use Drupal\Core\Config\Schema\ConfigSchemaDiscovery;
use Drupal\Core\Config\Schema\TypeResolver;
use Drupal\Core\Config\Schema\SequenceDataDefinition;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Config\Schema\Undefined;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\Core\Validation\Plugin\Validation\Constraint\FullyValidatableConstraint;

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
  public function __construct(StorageInterface $configStorage, StorageInterface $schemaStorage, CacheBackendInterface $cache, ModuleHandlerInterface $module_handler, ?ClassResolverInterface $class_resolver = NULL) {
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
      throw new \InvalidArgumentException("Missing required data for typed configuration: $name");
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
      $type = TypeResolver::resolveDynamicTypeName($type, $replace);
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

    // All values are optional by default (meaning they can be NULL), except for
    // mappings and sequences. A sequence can only be NULL when `nullable: true`
    // is set on the config schema type definition. This is unintuitive and
    // contradicts Drupal core's documentation.
    // @see https://www.drupal.org/node/2264179
    // @see https://www.drupal.org/node/1978714
    // To gradually evolve configuration schemas in the Drupal ecosystem to be
    // validatable, this needs to be clarified in a non-disruptive way. Any
    // config schema type definition — that is, a top-level entry in a
    // *.schema.yml file — can opt into stricter behavior, whereby a property
    // cannot be NULL unless it specifies `nullable: true`, by adding
    // `FullyValidatable` as a top-level validation constraint.
    // @see https://www.drupal.org/node/3364108
    // @see https://www.drupal.org/node/3364109
    // @see \Drupal\Core\TypedData\TypedDataManager::getDefaultConstraints()
    if ($parent) {
      $root_type = $parent->getRoot()->getDataDefinition()->getDataType();
      $root_type_has_opted_in = FALSE;
      foreach ($parent->getRoot()->getConstraints() as $constraint) {
        if ($constraint instanceof FullyValidatableConstraint) {
          $root_type_has_opted_in = TRUE;
          break;
        }
      }
      // If this is a dynamically typed property path, then not only must the
      // (absolute) root type be considered, but also the (relative) static root
      // type: the resolved type.
      // For example, `block.block.*:settings` has a dynamic type defined:
      // `block.settings.[%parent.plugin]`, but `block.block.*:plugin` does not.
      // Consequently, the value at the `plugin` property path depends only on
      // the `block.block.*` config schema type and hence only that config
      // schema type must have the `FullyValidatable` constraint, because it
      // defines which value are required.
      // In contrast, the `block.block.*:settings` property path depends on
      // whichever dynamic type `block.settings.[%parent.plugin]` resolved to,
      // to be able to know which values are required. Therefore that resolved
      // type determines which values are required and whether it is fully
      // validatable.
      // So for example the `block.settings.system_branding_block` config schema
      // type would also need to have the `FullyValidatable` constraint to
      // consider its schema-defined keys to require values:
      // - use_site_logo
      // - use_site_name
      // - use_site_slogan
      $static_type_root = TypedConfigManager::getStaticTypeRoot($parent);
      $static_type_root_type = $static_type_root->getDataDefinition()->getDataType();
      if ($root_type !== $static_type_root_type) {
        $root_type_has_opted_in = FALSE;
        foreach ($static_type_root->getConstraints() as $c) {
          if ($c instanceof FullyValidatableConstraint) {
            $root_type_has_opted_in = TRUE;
            break;
          }
        }
      }
      if ($root_type_has_opted_in) {
        $data_definition->setRequired(!isset($data_definition['nullable']) || $data_definition['nullable'] === FALSE);
      }
    }

    return $data_definition;
  }

  /**
   * Gets the static type root for a config schema object.
   *
   * @param \Drupal\Core\TypedData\TraversableTypedDataInterface $object
   *   A config schema object to get the static type root for.
   *
   * @return \Drupal\Core\TypedData\TraversableTypedDataInterface
   *   The ancestral config schema object at which the static type root lies:
   *   either the first ancestor with a dynamic type (for example:
   *   `block.block.*:settings`, which has the `block.settings.[%parent.plugin]`
   *   type) or the (absolute) root of the config object (in this example:
   *   `block.block.*`).
   */
  public static function getStaticTypeRoot(TraversableTypedDataInterface $object): TraversableTypedDataInterface {
    $root = $object->getRoot();
    $static_type_root = NULL;

    while ($static_type_root === NULL && $object !== $root) {
      // Use the parent data definition to determine the type of this mapping
      // (including the dynamic placeholders). For example:
      // - `editor.settings.[%parent.editor]`
      // - `editor.image_upload_settings.[status]`.
      $parent_data_def = $object->getParent()->getDataDefinition();
      $original_mapping_type = match (TRUE) {
        $parent_data_def instanceof MapDataDefinition => $parent_data_def->toArray()['mapping'][$object->getName()]['type'],
        $parent_data_def instanceof SequenceDataDefinition => $parent_data_def->toArray()['sequence']['type'] ?? $parent_data_def->toArray()['sequence'][0]['type'],
        default => throw new \LogicException('Invalid config schema detected.'),
      };

      // If this mapping's type was dynamically defined, then this is the static
      // type root inside which all types are statically defined.
      if (str_contains($original_mapping_type, ']')) {
        $static_type_root = $object;
        break;
      }

      $object = $object->getParent();
    }

    // Either the discovered static type root is not the actual root, or no
    // static type root was found and it is the root config object.
    assert(($static_type_root !== NULL && $static_type_root !== $root) || ($static_type_root === NULL && $object->getParent() === NULL));

    return $static_type_root ?? $root;
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
        $sub_type = $this->determineType(TypeResolver::resolveDynamicTypeName($definition['type'], $replacements), $definitions);
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
   * @param string $name
   *   Configuration type, potentially with expressions in square brackets.f
   * @param array $data
   *   Configuration data for the element.
   *
   * @return string
   *   Configuration type name with all expressions resolved.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
   *   \Drupal\Core\Config\Schema\TypeResolver::resolveDynamicTypeName::resolveDynamicTypeName()
   *   instead.
   *
   * @see https://www.drupal.org/node/3408266
   */
  protected function replaceName($name, $data) {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\Core\Config\Schema\TypeResolver::resolveDynamicTypeName() instead. See https://www.drupal.org/node/3408266', E_USER_DEPRECATED);
    return TypeResolver::resolveDynamicTypeName($name, $data);
  }

  /**
   * Resolves a dynamic type expression using configuration data.
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
   *   \Drupal\Core\Config\Schema\TypeResolver::resolveDynamicTypeName::resolveExpression()
   *   instead.
   *
   * @see https://www.drupal.org/node/3408266
   */
  protected function replaceVariable($value, $data) {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\Core\Config\Schema\TypeResolver::resolveExpression() instead. See https://www.drupal.org/node/3408266', E_USER_DEPRECATED);
    return TypeResolver::resolveExpression($value, $data);
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

  /**
   * Resolves a dynamic type name.
   *
   * @param string $type
   *   Configuration type, potentially with expressions in square brackets.
   * @param array $data
   *   Configuration data for the element.
   *
   * @return string
   *   Configuration type name with all expressions resolved.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
   *   \Drupal\Core\Config\Schema\TypeResolver::resolveDynamicTypeName()
   *   instead.
   *
   * @see https://www.drupal.org/node/3413264
   */
  protected function resolveDynamicTypeName(string $type, array $data): string {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\Core\Config\Schema\TypeResolver::' . __FUNCTION__ . '() instead. See https://www.drupal.org/node/3413264', E_USER_DEPRECATED);
    return TypeResolver::resolveDynamicTypeName($type, $data);
  }

  /**
   * Resolves a dynamic expression.
   *
   * @param string $expression
   *   Expression to be resolved.
   * @param array|\Drupal\Core\TypedData\TypedDataInterface $data
   *   Configuration data for the element.
   *
   * @return string
   *   The value the expression resolves to, or the given expression if it
   *   cannot be resolved.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
   *   \Drupal\Core\Config\Schema\TypeResolver::resolveExpression() instead.
   *
   * @see https://www.drupal.org/node/3413264
   */
  protected function resolveExpression(string $expression, array $data): string {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\Core\Config\Schema\TypeResolver::' . __FUNCTION__ . '() instead. See https://www.drupal.org/node/3413264', E_USER_DEPRECATED);
    return TypeResolver::resolveExpression($expression, $data);
  }

}
