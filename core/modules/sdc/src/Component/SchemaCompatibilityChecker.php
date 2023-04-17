<?php

namespace Drupal\sdc\Component;

use Drupal\sdc\Exception\IncompatibleComponentSchema;

/**
 * Checks whether two schemas are compatible.
 *
 * This is used during component replacement. We do not allow replacing a
 * component if the schemas are not compatible. Component authors must consider
 * their component schema as part of their module/theme API. Breaking changes
 * to the schema must be done in a new major version.
 *
 * @internal
 */
final class SchemaCompatibilityChecker {

  /**
   * Checks if the replacement schema is compatible with the old one.
   *
   * The goal is to ensure existing usages of the original component will not
   * break when the new component takes place.
   *
   * For the new schema to be compatible with the original it needs to accept
   * all the possible input that the original component allow. Any optional
   * props in the original component, not present in the replacement component
   * should be ignored and not cause validation errors.
   *
   * @param array $original_schema
   *   The schema to check compatibility against.
   * @param array $new_schema
   *   The new schema that should be compatible with.
   *
   * @throws \Drupal\sdc\Exception\IncompatibleComponentSchema
   */
  public function isCompatible(array $original_schema, array $new_schema): void {
    $error_messages = [];
    // First check the required properties.
    $error_messages = [
      ...$error_messages,
      ...$this->checkRequired($original_schema, $new_schema),
    ];
    // Next, compare the property types to ensure compatibility.
    $error_messages = [
      ...$error_messages,
      ...$this->checkSharedProperties($original_schema, $new_schema),
    ];
    // Finally, raise any potential issues that we might have detected.
    if (!empty($error_messages)) {
      $message = implode("\n", $error_messages);
      throw new IncompatibleComponentSchema($message);
    }
  }

  /**
   * Checks that the required properties are compatible.
   *
   * @param array $original_schema
   *   The original schema.
   * @param array $new_schema
   *   The schema of the replacement component.
   *
   * @return array
   *   The detected error messages, if any.
   */
  private function checkRequired(array $original_schema, array $new_schema): array {
    $error_messages = [];
    $original_required = $original_schema['required'] ?? [];
    $new_required = $new_schema['required'] ?? [];
    $missing_required = array_diff($original_required, $new_required);
    if (!empty($missing_required)) {
      $error_messages[] = sprintf(
        'Some of the required properties are missing in the new schema: [%s].',
        implode(', ', $missing_required)
      );
    }
    $additional_required = array_diff($new_required, $original_required);
    if (!empty($additional_required)) {
      $error_messages[] = sprintf(
        'Some of the new required properties are not allowed by the original schema: [%s].',
        implode(', ', $additional_required)
      );
    }
    return $error_messages;
  }

  /**
   * Checks that the shared properties are compatible.
   *
   * @param array $original_schema
   *   The original schema.
   * @param array $new_schema
   *   The schema of the replacement component.
   *
   * @return array
   *   The detected error messages, if any.
   */
  private function checkSharedProperties(array $original_schema, array $new_schema): array {
    $error_messages = [];
    $original_properties = $original_schema['properties'] ?? [];
    $new_properties = $new_schema['properties'] ?? [];
    $shared_properties = array_intersect(
      array_keys($original_properties),
      array_keys($new_properties)
    );
    return array_reduce(
      $shared_properties,
      function (array $errors, string $property_name) use ($original_properties, $new_properties) {
        $original_types = $original_properties[$property_name]['type'] ?? [];
        $new_types = $new_properties[$property_name]['type'] ?? [];
        // The type for the new property should, at least, accept all types for
        // the original property. Type in JSON Schema can be either a string or
        // an array of strings.
        $original_types = is_string($original_types) ? [$original_types] : $original_types;
        $new_types = is_string($new_types) ? [$new_types] : $new_types;
        $unsupported_types = array_diff($original_types, $new_types);
        if (!empty($unsupported_types)) {
          $errors[] = sprintf(
            'Property "%s" does not support the types [%s]. These types are supported in the original schema and should be supported in the new schema for compatibility.',
            $property_name,
            implode(', ', $unsupported_types)
          );
        }
        // If there are enums, those also need to be compatible.
        $original_enums = $original_properties[$property_name]['enum'] ?? [];
        $new_enums = $new_properties[$property_name]['enum'] ?? [];
        $unsupported_enums = array_diff($original_enums, $new_enums);
        if (!empty($unsupported_enums)) {
          $errors[] = sprintf(
            'Property "%s" does not allow some necessary enum values [%s]. These are supported in the original schema and should be supported in the new schema for compatibility.',
            $property_name,
            implode(', ', $unsupported_enums),
          );
        }
        // If the property is an object, then ensure sub-schema compatibility.
        $original_subproperties = $original_properties[$property_name]['properties'] ?? [];
        $new_subproperties = $new_properties[$property_name]['properties'] ?? [];
        if (!empty($original_subproperties) && !empty($new_subproperties)) {
          try {
            $this->isCompatible(
              $original_properties[$property_name],
              $new_properties[$property_name]
            );
          }
          catch (IncompatibleComponentSchema $exception) {
            $errors[] = $exception->getMessage();
          }
        }
        return $errors;
      },
      $error_messages
    );
  }

}
