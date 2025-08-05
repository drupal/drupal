<?php

declare(strict_types=1);

namespace Drupal\Core\Utility;

/**
 * Helper functions to translate enum cases into an array of options.
 */
trait OptionsEnumTrait {

  /**
   * Gets the label for this enum case.
   */
  abstract public function label(): string|\Stringable;

  /**
   * Returns an array of options for use in form API.
   *
   * @return array<int|string, string|\Stringable>
   *   A mapping of values to their corresponding labels.
   */
  public static function asOptions(): array {
    $options = [];
    foreach (self::cases() as $case) {
      $options[$case->value] = $case->label();
    }
    return $options;
  }

}
