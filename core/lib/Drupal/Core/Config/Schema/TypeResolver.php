<?php

declare(strict_types = 1);

namespace Drupal\Core\Config\Schema;

use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Provides helper methods for resolving config schema types.
 *
 * @internal
 *   This is an internal part of the config schema system and may be changed or
 *   removed any time. External code should not interact with this class.
 */
class TypeResolver {

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
   */
  public static function resolveDynamicTypeName(string $name, mixed $data): string {
    if (preg_match_all("/\[(.*)\]/U", $name, $matches)) {
      // Build our list of '[value]' => replacement.
      $replace = [];
      foreach (array_combine($matches[0], $matches[1]) as $key => $value) {
        $replace[$key] = self::resolveExpression($value, $data);
      }
      return strtr($name, $replace);
    }
    return $name;
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
   * @param array|\Drupal\Core\TypedData\TypedDataInterface $data
   *   Configuration data for the element.
   *
   * @return string
   *   The value the expression resolves to, or the given expression if it
   *   cannot be resolved.
   *
   * @throws \LogicException
   *    Exception thrown if $expression is not a valid dynamic type expression.
   */
  public static function resolveExpression(string $expression, array|TypedDataInterface $data): string {
    if ($data instanceof TypedDataInterface) {
      $data = [
        '%parent' => $data->getParent(),
        '%key' => $data->getName(),
        '%type' => $data->getDataDefinition()->getDataType(),
      ];
    }

    $parts = explode('.', $expression);
    $previous_name = NULL;
    // Process each value part, one at a time.
    while ($name = array_shift($parts)) {
      if (str_starts_with($name, '%') && !in_array($name, ['%parent', '%key', '%type'], TRUE)) {
        throw new \LogicException('`' . $expression . '` is not a valid dynamic type expression. Dynamic type expressions must contain at least `%parent`, `%key`, or `%type`.`');
      }
      if ($name === '%type' && $previous_name !== '%parent') {
        throw new \LogicException('`%type` can only used when immediately preceded by `%parent` in `' . $expression . '`');
      }
      $previous_name = $name;
      if (!is_array($data) || !isset($data[$name])) {
        // Key not found, return original value
        return $expression;
      }
      if (!$parts) {
        $expression = $data[$name];
        if (is_bool($expression)) {
          $expression = (int) $expression;
        }
        // If no more parts left, this is the final property.
        return (string) $expression;
      }
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
        continue;
      }
      $data = $data[$name];
    }
    // Return the original value
    return $expression;
  }

}
