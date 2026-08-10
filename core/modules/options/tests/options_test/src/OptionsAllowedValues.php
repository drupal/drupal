<?php

declare(strict_types=1);

namespace Drupal\options_test;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Provide allowed values callback.
 */
class OptionsAllowedValues {

  /**
   * Implements callback_allowed_values_function().
   *
   * @see \Drupal\options\OptionsAllowedValuesInterface::allowedValues()
   */
  public static function simpleValues(FieldStorageDefinitionInterface $definition, ?FieldableEntityInterface $entity = NULL): array {
    return [
      'Group 1' => [
        0 => 'Zero',
      ],
      1 => 'One',
      'Group 2' => [
        2 => 'Some <script>dangerous</script> & unescaped <strong>markup</strong>',
      ],
      'More <script>dangerous</script> markup' => [
        3 => 'Three',
      ],
    ];
  }

  /**
   * Implements callback_allowed_values_function().
   *
   * @see \Drupal\options\OptionsAllowedValuesInterface::allowedValues()
   */
  public static function dynamicValues(FieldStorageDefinitionInterface $definition, ?FieldableEntityInterface $entity = NULL, &$cacheable = NULL): array {
    $values = [];
    if (isset($entity)) {
      $cacheable = FALSE;
      $values = [
        $entity->label(),
        $entity->toUrl()->toString(),
        $entity->uuid(),
        $entity->bundle(),
      ];
    }
    // We need the values of the entity as keys.
    return array_combine($values, $values);
  }

}
