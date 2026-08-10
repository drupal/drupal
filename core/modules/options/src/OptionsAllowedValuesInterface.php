<?php

declare(strict_types=1);

namespace Drupal\options;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Provides an interface to get allowed values for a list field.
 */
interface OptionsAllowedValuesInterface {

  /**
   * Returns the array of allowed values for a list field.
   *
   * The strings are not safe for output. Keys and values of the array should be
   * sanitized through \Drupal\Core\Field\AllowedTagsXssTrait::fieldFilterXss()
   * before being displayed.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $definition
   *   The field storage definition.
   * @param \Drupal\Core\Entity\FieldableEntityInterface|null $entity
   *   (optional) The specific entity when this function is called from the
   *   context of a specific field on a specific entity. This allows custom
   *   'allowed_values_function' callbacks to either restrict the values or
   *   customize the labels for particular bundles and entities. NULL when
   *   there is not a specific entity available, such as for Views filters.
   *
   * @return array
   *   The array of allowed values. Keys of the array are the raw stored values
   *   (number or text), values of the array are the display labels.
   *
   * @see callback_allowed_values_function()
   */
  public function getAllowedValues(FieldStorageDefinitionInterface $definition, ?FieldableEntityInterface $entity = NULL): array;

}
