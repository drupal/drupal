<?php

namespace Drupal\Core\Theme\Component;

use Drupal\Core\Render\Component\Exception\InvalidComponentException;
use Drupal\Core\Render\Element;
use Drupal\Core\Plugin\Component;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;

/**
 * Validates a component based on its definition and the component schema.
 */
class ComponentValidator {

  /**
   * The schema validator.
   *
   * This property will only be set if the validator library is available.
   *
   * @var \JsonSchema\Validator|null
   */
  protected ?Validator $validator = NULL;

  /**
   * Sets the validator service if available.
   */
  public function setValidator(?Validator $validator = NULL): void {
    if ($validator) {
      $this->validator = $validator;
      return;
    }
    if (class_exists(Validator::class)) {
      $this->validator = new Validator();
    }
  }

  /**
   * Validates the component metadata file.
   *
   * A valid component metadata file can be validated against the
   * metadata-author.schema.json, plus the ability of classes and interfaces
   * in the `type` property.
   *
   * @param array $definition
   *   The definition to validate.
   * @param bool $enforce_schemas
   *   TRUE if schema definitions are mandatory.
   *
   * @return bool
   *   TRUE if the component is valid.
   *
   * @throws \Drupal\Core\Render\Component\Exception\InvalidComponentException
   */
  public function validateDefinition(array $definition, bool $enforce_schemas): bool {
    // First ensure there are no name collisions between props and slots.
    $prop_names = array_keys($definition['props']['properties'] ?? []);
    $slot_names = array_keys($definition['slots'] ?? []);
    $collisions = array_intersect($prop_names, $slot_names);
    if ($collisions) {
      $message = sprintf(
        'The component "%s" declared [%s] both as a prop and as a slot. Make sure to use different names.',
        $definition['id'],
        implode(', ', $collisions)
      );
      throw new InvalidComponentException($message);
    }
    // If the validator isn't set, then the validation library is not installed.
    if (!$this->validator) {
      return TRUE;
    }
    // Detect the props with a type class, and validate that the class exists.
    $schema = $definition['props'] ?? NULL;
    if (!$schema) {
      if ($enforce_schemas) {
        throw new InvalidComponentException(sprintf('The component "%s" does not provide schema information. Schema definitions are mandatory for components declared in modules. For components declared in themes, schema definitions are only mandatory if the "enforce_prop_schemas" key is set to "true" in the theme info file.', $definition['id']));
      }
      return TRUE;
    }
    // If there are no props, force casting to object instead of array.
    if (($schema['properties'] ?? NULL) === []) {
      $schema['properties'] = new \stdClass();
    }
    $classes_per_prop = $this->getClassProps($schema);
    $missing_class_errors = [];
    foreach ($classes_per_prop as $prop_name => $class_types) {
      // For each possible type, check if it is a class.
      $missing_classes = array_filter($class_types, static fn(string $class) => !class_exists($class) && !interface_exists($class));
      $missing_class_errors = [
        ...$missing_class_errors,
        ...array_map(
          static fn(string $class) => sprintf('Unable to find class/interface "%s" specified in the prop "%s" for the component "%s".', $class, $prop_name, $definition['id']),
          $missing_classes
        ),
      ];
    }
    // Remove the non JSON Schema types for validation down below.
    $definition['props'] = $this->nullifyClassPropsSchema(
      $schema,
      $classes_per_prop
    );

    $definition_object = Validator::arrayToObjectRecursive($definition);
    $this->validator->validate(
      $definition_object,
      (object) ['$ref' => 'file://' . dirname(__DIR__, 5) . '/assets/schemas/v1/metadata-full.schema.json']
    );
    if (empty($missing_class_errors) && $this->validator->isValid()) {
      return TRUE;
    }
    $message_parts = array_map(
      static fn(array $error): string => sprintf("[%s] %s", $error['property'], $error['message']),
      $this->validator->getErrors()
    );
    $message_parts = [
      ...$message_parts,
      ...$missing_class_errors,
    ];
    $message = implode("/n", $message_parts);
    // Throw the exception with the error message.
    throw new InvalidComponentException($message);
  }

  /**
   * Validates that the props provided to the component.
   *
   * Valid props are compliant with the schema definition in the component
   * metadata file.
   *
   * @param array $context
   *   The Twig context that contains the prop data.
   * @param \Drupal\Core\Plugin\Component $component
   *   The component to validate the props against.
   *
   * @return bool
   *   TRUE if the props adhere to the component definition.
   *
   * @throws \Drupal\Core\Render\Component\Exception\InvalidComponentException
   */
  public function validateProps(array $context, Component $component): bool {
    // If the validator isn't set, then the validation library is not installed.
    if (!$this->validator) {
      return TRUE;
    }
    $component_id = $component->getPluginId();
    $schema = $component->metadata->schema;
    if (!$schema) {
      if ($component->metadata->mandatorySchemas) {
        throw new InvalidComponentException(sprintf('The component "%s" does not provide schema information. Schema definitions are mandatory for components declared in modules. For components declared in themes, schema definitions are only mandatory if the "enforce_prop_schemas" key is set to "true" in the theme info file.', $component_id));
      }
      return TRUE;
    }
    if (empty($schema['properties'])) {
      // If there are no properties in the schema there is nothing to validate.
      return TRUE;
    }
    $prop_names = array_keys($schema['properties']);
    $props_raw = array_intersect_key($context, array_flip($prop_names));
    // Validator::arrayToObjectRecursive stringifies the props using the JSON
    // encoder. Before that happens, we want to validate classes. Once the
    // classes are validated, we remove them as potential problems for the JSON
    // Schema validation.
    [
      $schema,
      $props_raw,
    ] = $this->validateClassProps($schema, $props_raw, $component_id);
    $schema = Validator::arrayToObjectRecursive($schema);
    $props = Validator::arrayToObjectRecursive($props_raw);
    $validator = new Validator();
    $validator->validate($props, $schema, Constraint::CHECK_MODE_TYPE_CAST);
    $validator->getErrors();
    if ($validator->isValid()) {
      return TRUE;
    }
    // Dismiss type errors if the prop received a render array.
    $errors = array_filter(
      $validator->getErrors(),
      function (array $error) use ($context): bool {
        if (($error['constraint'] ?? '') !== 'type') {
          return TRUE;
        }
        return !Element::isRenderArray($context[$error['property']] ?? NULL);
      }
    );
    if (empty($errors)) {
      return TRUE;
    }
    $message_parts = array_map(
      static function (array $error): string {
        // We check the error message instead of values and definitions here
        // because it's hard to access both given the possible complexity of a
        // schema. Since this is a small non critical DX improvement error
        // message checking should be sufficient.
        if (str_contains($error['message'], 'NULL value found, but a ')) {
          $error['message'] .= '. This may be because the property is empty instead of having data present. If possible fix the source data, use the |default() twig filter, or update the schema to allow multiple types.';
        }

        return sprintf("[%s] %s", $error['property'], $error['message']);
      },
      $errors
    );
    $message = implode("/n", $message_parts);
    throw new InvalidComponentException($message);
  }

  /**
   * Validates the props that are not JSON Schema.
   *
   * This validates that the props are instances of the class/interface declared
   * in the metadata file.
   *
   * @param array $props_schema
   *   The schema for all the props in the component.
   * @param array $props_raw
   *   The props provided to the component.
   * @param string $component_id
   *   The component ID. Used for error reporting.
   *
   * @return array
   *   A tuple containing the new $props_schema and the new $props_raw. We
   *   mutate the schema to be `type: null` and the prop value to be `NULL` for
   *   the props that are validated as class objects. This is done so these
   *   props can pass validation later on when validating against the JSON
   *   Schema. We can do this because we have already validated these props
   *   manually.
   *
   * @throws \Drupal\Core\Render\Component\Exception\InvalidComponentException
   */
  private function validateClassProps(array $props_schema, array $props_raw, string $component_id): array {
    $error_messages = [];
    $classes_per_prop = $this->getClassProps($props_schema);
    $properties = $props_schema['properties'] ?? [];
    foreach ($properties as $prop_name => $prop_def) {
      $class_types = $classes_per_prop[$prop_name] ?? [];
      $prop = $props_raw[$prop_name] ?? NULL;
      if (empty($class_types) || is_null($prop)) {
        continue;
      }
      $is_valid = array_reduce(
        $class_types,
        static fn(bool $valid, string $class_name) => $valid || $prop instanceof $class_name,
        FALSE
      );
      if (!$is_valid) {
        $error_messages[] = sprintf(
          'Data provided to prop "%s" for component "%s" is not a valid instance of "%s"',
          $prop_name,
          $component_id,
          implode(', ', $class_types),
        );
      }
      // Remove the non JSON Schema types for later JSON Schema validation.
      $props_raw[$prop_name] = NULL;
    }
    $props_schema = $this->nullifyClassPropsSchema($props_schema, $classes_per_prop);
    if (!empty($error_messages)) {
      $message = implode("/n", $error_messages);
      throw new InvalidComponentException($message);
    }
    return [$props_schema, $props_raw];
  }

  /**
   * Gets the props that are not JSON based.
   *
   * @param array $props_schema
   *   The schema for the props.
   *
   * @return array
   *   The class props.
   */
  private function getClassProps(array $props_schema): array {
    $classes_per_prop = [];
    foreach ($props_schema['properties'] ?? [] as $prop_name => $prop_def) {
      $type = $prop_def['type'] ?? 'null';
      $types = is_string($type) ? [$type] : $type;
      // For each possible type, check if it is a class.
      $class_types = array_filter($types, static fn(string $type) => !in_array(
        $type,
        ['array', 'boolean', 'integer', 'null', 'number', 'object', 'string']
      ));
      $classes_per_prop[$prop_name] = $class_types;
    }
    return array_filter($classes_per_prop);
  }

  /**
   * Utility method to ensure the schema for class props is set to 'null'.
   *
   * @param array $schema_props
   *   The schema for all the props.
   * @param array $classes_per_prop
   *   Associative array that associates prop names with their prop classes.
   *
   * @return array
   *   The new schema.
   */
  private function nullifyClassPropsSchema(array $schema_props, array $classes_per_prop): array {
    foreach ($schema_props['properties'] as $prop_name => $prop_def) {
      $class_types = $classes_per_prop[$prop_name] ?? [];
      // Remove the non JSON Schema types for later JSON Schema validation.
      $types = (array) ($prop_def['type'] ?? ['null']);
      $types = array_diff($types, $class_types);
      $types = empty($types) ? ['null'] : $types;
      $schema_props['properties'][$prop_name]['type'] = $types;
    }
    return $schema_props;
  }

}
