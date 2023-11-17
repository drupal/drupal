<?php

namespace Drupal\Core\Config\Schema;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\Plugin\DataType\ConfigEntityAdapter;
use Drupal\Core\TypedData\PrimitiveInterface;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\Core\TypedData\Type\BooleanInterface;
use Drupal\Core\TypedData\Type\StringInterface;
use Drupal\Core\TypedData\Type\FloatInterface;
use Drupal\Core\TypedData\Type\IntegerInterface;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Provides a trait for checking configuration schema.
 */
trait SchemaCheckTrait {

  /**
   * The config schema wrapper object for the configuration object under test.
   */
  protected TraversableTypedDataInterface $schema;

  /**
   * The configuration object name under test.
   */
  protected string $configName;

  /**
   * The ignored property paths.
   *
   * Allow ignoring specific config schema types (top-level keys, require an
   * exact match to one of the top-level entries in *.schema.yml files) by
   * allowing one or more partial property path matches.
   *
   * Keys must be an exact match for a Config object's schema type.
   * Values must be wildcard matches for property paths, where any property
   * path segment can use a wildcard (`*`) to indicate any value for that
   * segment should be accepted for this property path to be ignored.
   *
   * @var \string[][]
   */
  protected static array $ignoredPropertyPaths = [
    'search.page.*' => [
      // @todo Fix config or tweak schema of `type: search.page.*` in
      //   https://drupal.org/i/3380475.
      // @see search.schema.yml
      'label' => [
        'This value should not be blank.',
      ],
    ],
  ];

  /**
   * Checks the TypedConfigManager has a valid schema for the configuration.
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The TypedConfigManager.
   * @param string $config_name
   *   The configuration name.
   * @param array $config_data
   *   The configuration data, assumed to be data for a top-level config object.
   * @param bool $validate_constraints
   *   Determines if constraints will be validated. If TRUE, constraint
   *   validation errors will be added to the errors found.
   *
   * @return array|bool
   *   FALSE if no schema found. List of errors if any found. TRUE if fully
   *   valid.
   */
  public function checkConfigSchema(TypedConfigManagerInterface $typed_config, $config_name, $config_data, bool $validate_constraints = FALSE) {
    // We'd like to verify that the top-level type is either config_base,
    // config_entity, or a derivative. The only thing we can really test though
    // is that the schema supports having langcode in it. So add 'langcode' to
    // the data if it doesn't already exist.
    if (!isset($config_data['langcode'])) {
      $config_data['langcode'] = 'en';
    }
    $this->configName = $config_name;
    if (!$typed_config->hasConfigSchema($config_name)) {
      return FALSE;
    }
    $this->schema = $typed_config->createFromNameAndData($config_name, $config_data);
    $errors = [];
    foreach ($config_data as $key => $value) {
      $errors[] = $this->checkValue($key, $value);
    }
    $errors = array_merge(...$errors);
    if ($validate_constraints) {
      // Also perform explicit validation. Note this does NOT require every node
      // in the config schema tree to have validation constraints defined.
      $violations = $this->schema->validate();
      $filtered_violations = array_filter(
        iterator_to_array($violations),
        fn(ConstraintViolation $v) => !static::isViolationForIgnoredPropertyPath($v),
      );
      $validation_errors = array_map(
        fn(ConstraintViolation $v) => sprintf("[%s] %s", $v->getPropertyPath(), (string) $v->getMessage()),
        $filtered_violations
      );
      // @todo Decide in https://www.drupal.org/project/drupal/issues/3395099 when/how to trigger deprecation errors or even failures for contrib modules.
      $errors = array_merge($errors, $validation_errors);
    }

    if (empty($errors)) {
      return TRUE;
    }
    return $errors;
  }

  /**
   * Determines whether this violation is for an ignored Config property path.
   *
   * @param \Symfony\Component\Validator\ConstraintViolation $v
   *   A validation constraint violation for a Config object.
   *
   * @return bool
   */
  protected static function isViolationForIgnoredPropertyPath(ConstraintViolation $v): bool {
    // When the validated object is a config entity wrapped in a
    // ConfigEntityAdapter, some work is necessary to map from e.g.
    // `entity:comment_type` to the corresponding `comment.type.*`.
    if ($v->getRoot() instanceof ConfigEntityAdapter) {
      $config_entity = $v->getRoot()->getEntity();
      assert($config_entity instanceof ConfigEntityInterface);
      $config_entity_type = $config_entity->getEntityType();
      assert($config_entity_type instanceof ConfigEntityType);
      $config_prefix = $config_entity_type->getConfigPrefix();
      // Compute the data type of the config object being validated:
      // - the config entity type's config prefix
      // - with as many `.*`-suffixes appended as there are parts in the ID (for
      //   example, for NodeType there's only 1 part, for EntityViewDisplay
      //   there are 3 parts.)
      // TRICKY: in principle it is possible to compute the exact number of
      // suffixes by inspecting ConfigEntity::getConfigDependencyName(), except
      // when the entity ID itself is invalid. Unfortunately that means
      // gradually discovering it is the only available alternative.
      $suffix_count = 1;
      do {
        $config_object_data_type = $config_prefix . str_repeat('.*', $suffix_count);
        $suffix_count++;
      } while ($suffix_count <= 3 && !array_key_exists($config_object_data_type, static::$ignoredPropertyPaths));
    }
    else {
      $config_object_data_type = $v->getRoot()
        ->getDataDefinition()
        ->getDataType();
    }
    if (!array_key_exists($config_object_data_type, static::$ignoredPropertyPaths)) {
      return FALSE;
    }

    foreach (static::$ignoredPropertyPaths[$config_object_data_type] as $ignored_property_path_expression => $ignored_validation_constraint_messages) {
      // Convert the wildcard-based expression to a regex: treat `*` nor in the
      // regex sense nor as something to be escaped: treat it as the wildcard
      // for a segment in a property path (property path segments are separated
      // by periods).
      // That requires first ensuring that preg_quote() does not escape it, and
      // then replacing it with an appropriate regular expression: `[^\.]+`,
      // which means: ">=1 characters that are anything except a period".
      $ignored_property_path_regex = str_replace(' ', '[^\.]+', preg_quote(str_replace('*', ' ', $ignored_property_path_expression)));

      // To ignore this violation constraint, require a match on both the
      // property path and the message.
      $property_path_match = preg_match('/^' . $ignored_property_path_regex . '$/', $v->getPropertyPath(), $matches) === 1;
      if ($property_path_match) {
        return preg_match(sprintf("/^(%s)$/", implode('|', $ignored_validation_constraint_messages)), (string) $v->getMessage()) === 1;
      }
    }
    return FALSE;
  }

  /**
   * Helper method to check data type.
   *
   * @param string $key
   *   A string of configuration key.
   * @param mixed $value
   *   Value of given key.
   *
   * @return array
   *   List of errors found while checking with the corresponding schema.
   */
  protected function checkValue($key, $value) {
    $error_key = $this->configName . ':' . $key;
    /** @var \Drupal\Core\TypedData\TypedDataInterface $element */
    $element = $this->schema->get($key);

    // Check if this type has been deprecated.
    $data_definition = $element->getDataDefinition();
    if (!empty($data_definition['deprecated'])) {
      @trigger_error($data_definition['deprecated'], E_USER_DEPRECATED);
    }

    if ($element instanceof Undefined) {
      return [$error_key => 'missing schema'];
    }

    // Do not check value if it is defined to be ignored.
    if ($element && $element instanceof Ignore) {
      return [];
    }

    if ($element && is_scalar($value) || $value === NULL) {
      $success = FALSE;
      $type = gettype($value);
      if ($element instanceof PrimitiveInterface) {
        $success =
          ($type == 'integer' && $element instanceof IntegerInterface) ||
          // Allow integer values in a float field.
          (($type == 'double' || $type == 'integer') && $element instanceof FloatInterface) ||
          ($type == 'boolean' && $element instanceof BooleanInterface) ||
          ($type == 'string' && $element instanceof StringInterface) ||
          // Null values are allowed for all primitive types.
          ($value === NULL);
      }
      // Array elements can also opt-in for allowing a NULL value.
      elseif ($element instanceof ArrayElement && $element->isNullable() && $value === NULL) {
        $success = TRUE;
      }
      $class = get_class($element);
      if (!$success) {
        return [$error_key => "variable type is $type but applied schema class is $class"];
      }
    }
    else {
      $errors = [];
      if (!$element instanceof TraversableTypedDataInterface) {
        $errors[$error_key] = 'non-scalar value but not defined as an array (such as mapping or sequence)';
      }

      // Go on processing so we can get errors on all levels. Any non-scalar
      // value must be an array so cast to an array.
      if (!is_array($value)) {
        $value = (array) $value;
      }
      $nested_errors = [];
      // Recurse into any nested keys.
      foreach ($value as $nested_value_key => $nested_value) {
        $nested_errors[] = $this->checkValue($key . '.' . $nested_value_key, $nested_value);
      }
      return array_merge($errors, ...$nested_errors);
    }
    // No errors found.
    return [];
  }

}
