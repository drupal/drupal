<?php

namespace Drupal\Core\Config\Schema;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Defines a mapping configuration element.
 *
 * This object may contain any number and type of nested properties and each
 * property key may have its own definition in the 'mapping' property of the
 * configuration schema.
 *
 * Properties in the configuration value that are not defined in the mapping
 * will get the 'undefined' data type.
 *
 * Read https://www.drupal.org/node/1905070 for more details about configuration
 * schema, types and type resolution.
 */
class Mapping extends ArrayElement {

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, ?TypedDataInterface $parent = NULL) {
    assert($definition instanceof MapDataDefinition);
    // Validate basic structure.
    foreach ($definition['mapping'] as $key => $key_definition) {
      // Guide developers when a config schema definition is wrong.
      if (!is_array($key_definition)) {
        if (!$parent) {
          throw new \LogicException(sprintf("The mapping definition at `%s` is invalid: its `%s` key contains a %s. It must be an array.", $name, $key, gettype($key_definition)));
        }
        else {
          throw new \LogicException(sprintf("The mapping definition at `%s:%s` is invalid: its `%s` key contains a %s. It must be an array.", $parent->getPropertyPath(), $name, $key, gettype($key_definition)));
        }
      }
    }
    $this->processRequiredKeyFlags($definition);
    parent::__construct($definition, $name, $parent);
  }

  /**
   * {@inheritdoc}
   */
  protected function getElementDefinition($key) {
    $value = $this->value[$key] ?? NULL;
    $definition = $this->definition['mapping'][$key] ?? [];
    return $this->buildDataDefinition($definition, $value, $key);
  }

  /**
   * Gets all keys allowed in this mapping.
   *
   * @return string[]
   *   A list of keys allowed in this mapping.
   */
  public function getValidKeys(): array {
    $all_keys = $this->getDefinedKeys();
    return array_keys($all_keys);
  }

  /**
   * Gets all required keys in this mapping.
   *
   * @return string[]
   *   A list of keys required in this mapping.
   */
  public function getRequiredKeys(): array {
    $all_keys = $this->getDefinedKeys();
    $required_keys = array_filter(
      $all_keys,
      fn (array $schema_definition): bool => $schema_definition['requiredKey']
    );
    return array_keys($required_keys);
  }

  /**
   * Gets the keys defined for this mapping (locally defined + inherited).
   *
   * @return array
   *   Raw schema definitions: keys are mapping keys, values are their
   *   definitions.
   */
  protected function getDefinedKeys(): array {
    $definition = $this->getDataDefinition();
    return $definition->toArray()['mapping'];
  }

  /**
   * Gets all dynamically valid keys.
   *
   * When the `type` of the mapping is dynamic itself. For example: the settings
   * associated with a FieldConfig depend on what kind of field it is (i.e.,
   * which field plugin it uses).
   *
   * Other examples:
   * - CKEditor 5 uses 'ckeditor5.plugin.[%key]'; the mapping is stored in a
   *   sequence, and `[%key]` is replaced by the mapping's key in that sequence.
   * - third party settings use '[%parent.%parent.%type].third_party.[%key]';
   *   `[%parent.%parent.%type]` is replaced by the type of the mapping two
   *   levels up. For example, 'node.type.third_party.[%key]'.
   * - field instances' default values have a type of
   *   'field.value.[%parent.%parent.field_type]'. This uses the value of the
   *   `field_type` key from the mapping two levels up.
   * - Views filters have a type of 'views.filter.[plugin_id]'; `[plugin_id]` is
   *   replaced by the value of the mapping's `plugin_id` key.
   *
   * In each of these examples, the mapping may have keys that are dynamically
   * valid, meaning that which keys are considered valid may depend on other
   * values in the tree.
   *
   * @return string[][]
   *   A list of dynamically valid keys. An array with:
   *   - a key for every possible resolved type
   *   - the corresponding value an array of the additional mapping keys that
   *     are supported for this resolved type
   *
   * @see \Drupal\Core\Config\TypedConfigManager::resolveDynamicTypeName()
   * @see \Drupal\Core\Config\TypedConfigManager::resolveExpression()
   * @see https://www.drupal.org/files/ConfigSchemaCheatSheet2.0.pdf
   */
  public function getDynamicallyValidKeys(): array {
    $parent_data_def = $this->getParent()?->getDataDefinition();
    if ($parent_data_def === NULL) {
      return [];
    }

    // Use the parent data definition to determine the type of this mapping
    // (including the dynamic placeholders). For example:
    // - `editor.settings.[%parent.editor]`
    // - `editor.image_upload_settings.[status]`.
    $original_mapping_type = match (TRUE) {
      $parent_data_def instanceof MapDataDefinition => $parent_data_def->toArray()['mapping'][$this->getName()]['type'],
      $parent_data_def instanceof SequenceDataDefinition => $parent_data_def->toArray()['sequence']['type'],
      default => throw new \LogicException('Invalid config schema detected.'),
    };

    // If this mapping's type isn't dynamic, there's nothing to do.
    if (!str_contains($original_mapping_type, ']')) {
      return [];
    }
    // Only third-party settings, which are optional by definition, start with
    // a dynamic placeholder.
    elseif (str_starts_with($original_mapping_type, '[')) {
      return [];
    }

    // Expand the dynamic placeholders to find all mapping types derived from
    // the original mapping type. To continue the previous example:
    // - `editor.settings.unicorn`
    // - `editor.image_upload_settings.*`
    // - `editor.image_upload_settings.1`
    $possible_types = $this->getPossibleTypes($original_mapping_type);

    // TRICKY: it is tempting to not consider this a dynamic type if only one
    // concrete type exists. But that would lead to different validation errors
    // when modules are installed or uninstalled.
    assert(!empty($possible_types));

    // Determine all valid keys, across all possible types.
    $typed_data_manager = $this->getTypedDataManager();
    $all_type_definitions = $typed_data_manager->getDefinitions();
    $possible_type_definitions = array_intersect_key($all_type_definitions, array_fill_keys($possible_types, TRUE));
    // TRICKY: \Drupal\Core\Config\TypedConfigManager::getDefinition() does the
    // necessary resolving, but TypedConfigManager::getDefinitions() does not!
    // 🤷‍♂️
    // @see \Drupal\Core\Config\TypedConfigManager::getDefinitionWithReplacements()
    // @see ::getValidKeys()
    $valid_keys_per_type = [];
    foreach (array_keys($possible_type_definitions) as $possible_type_name) {
      $valid_keys_per_type[$possible_type_name] = array_keys($typed_data_manager->getDefinition($possible_type_name)['mapping'] ?? []);
    }

    // From all valid keys across all types, get the ones for the fallback type:
    // its keys are inherited by all type definitions and are therefore always
    // ("statically") valid. Not all types have a fallback type.
    // @see \Drupal\Core\Config\TypedConfigManager::getDefinitionWithReplacements()
    $fallback_type = $typed_data_manager->findFallback($original_mapping_type);
    $valid_keys_everywhere = array_intersect_key(
      $valid_keys_per_type,
      [$fallback_type => NULL],
    );
    assert(count($valid_keys_everywhere) <= 1);
    $statically_required_keys = NestedArray::mergeDeepArray($valid_keys_everywhere);

    // Now that statically valid keys are known, determine which valid keys are
    // only valid in *some* cases: remove the statically valid keys from every
    // per-type array of valid keys.
    $valid_keys_some = array_diff_key($valid_keys_per_type, $valid_keys_everywhere);
    $valid_keys_some_processed = array_map(
      fn (array $keys) => array_values(array_filter($keys, fn (string $key) => !in_array($key, $statically_required_keys, TRUE))),
      $valid_keys_some
    );
    return $valid_keys_some_processed;
  }

  /**
   * Gets all optional keys in this mapping.
   *
   * @return string[]
   *   A list of optional keys given the values in this mapping.
   */
  public function getOptionalKeys(): array {
    return array_values(array_diff($this->getValidKeys(), $this->getRequiredKeys()));
  }

  /**
   * Validates optional `requiredKey` flags, guarantees one will be set.
   *
   * For each key-value pair:
   * - If the `requiredKey` flag is set, it must be `false`, to avoid pointless
   *   information in the schema.
   * - If the `requiredKey` flag is not set and the `deprecated` flag is set,
   *   this will set `requiredKey: false`: deprecated keys are always optional.
   * - If the `requiredKey` flag is not set, nor the `deprecated` flag,
   *   will set `requiredKey: true`.
   *
   * @param \Drupal\Core\TypedData\MapDataDefinition $definition
   *   The config schema definition for a `type: mapping`.
   *
   * @throws \LogicException
   *   Thrown when `requiredKey: true` is specified.
   */
  protected function processRequiredKeyFlags(MapDataDefinition $definition): void {
    foreach ($definition['mapping'] as $key => $key_definition) {
      // Validates `requiredKey` flag in mapping definitions.
      if (array_key_exists('requiredKey', $key_definition) && $key_definition['requiredKey'] !== FALSE) {
        throw new \LogicException('The `requiredKey` flag must either be omitted or have `false` as the value.');
      }
      // Generates the `requiredKey` flag if it is not set.
      if (!array_key_exists('requiredKey', $key_definition)) {
        // Required by default, unless this key is marked as deprecated.
        // @see https://www.drupal.org/node/3129881
        $definition['mapping'][$key]['requiredKey'] = !array_key_exists('deprecated', $key_definition);
      }
    }
  }

  /**
   * Returns all possible types for the type with the given name.
   *
   * @param string $name
   *   Configuration name or key.
   *
   * @return string[]
   *   All possible types for a given type. For example,
   *   `core_date_format_pattern.[%parent.locked]` will return:
   *   - `core_date_format_pattern.0`
   *   - `core_date_format_pattern.1`
   *   If a fallback name is available, that will be returned too. In this
   *   example, that would be `core_date_format_pattern.*`.
   */
  protected function getPossibleTypes(string $name): array {
    // First, parse from e.g.
    // `module.something.foo_[%parent.locked]`
    // this:
    // `[%parent.locked]`
    // or from
    // `[%parent.%parent.%type].third_party.[%key]`
    // this:
    // `[%parent.%parent.%type]` and `[%key]`.
    // And collapse all these to just `[]`.
    // @see \Drupal\Core\Config\TypedConfigManager::replaceVariable()
    $matches = [];
    if (preg_match_all('/(\[[^\]]+\])/', $name, $matches) >= 1) {
      $name = str_replace($matches[0], '[]', $name);
    }
    // Then, replace all `[]` occurrences with `.*` and escape all periods for
    // use in a regex. So:
    // `module\.something\.foo_.*`
    // or
    // `.*\.third_party\..*`
    $regex = str_replace(['.', '[]'], ['\.', '.*'], $name);
    // Now find all possible types:
    // 1. `module.something.foo_foo`, `module.something.foo_bar`, etc.
    $possible_types = array_filter(
      array_keys($this->getTypedDataManager()->getDefinitions()),
      fn (string $type) => preg_match("/^$regex$/", $type) === 1
    );
    // 2. The fallback: `module.something.*` — if no concrete definition for it
    // exists.
    $fallback_type = $this->getTypedDataManager()->findFallback($name);
    if ($fallback_type && !in_array($fallback_type, $possible_types, TRUE)) {
      $possible_types[] = $fallback_type;
    }
    return $possible_types;
  }

}
