<?php

namespace Drupal\Composer\Plugin\RecipeUnpack;

use Composer\Package\PackageInterface;

/**
 * Per-project options from the 'extras' section of the composer.json file.
 *
 * Projects that implement dependency unpacking plugin can further configure it.
 * This data is pulled from the 'drupal-recipe-unpack' portion of the extras
 * section.
 *
 * @code
 *  "extras": {
 *    "drupal-recipe-unpack": {
 *      "ignore": ["drupal/recipe_name"],
 *      "on-require": true
 *    }
 *  }
 * @endcode
 *
 * Supported options:
 * - `ignore` (array):
 *   Specifies packages to exclude from unpacking into the root composer.json.
 * - `on-require` (boolean):
 *   Whether to unpack recipes automatically on require.
 *
 * @internal
 */
final readonly class UnpackOptions {

  /**
   * The ID of the extra section in the top-level composer.json file.
   */
  const string ID = 'drupal-recipe-unpack';

  /**
   * The raw data from the 'extras' section of the top-level composer.json file.
   *
   * @var array{ignore: string[], on-require: boolean}
   */
  public array $options;

  private function __construct(array $options) {
    $this->options = $options + [
      'ignore' => [],
      'on-require' => TRUE,
    ];
  }

  /**
   * Checks if a package should be ignored.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package.
   *
   * @return bool
   *   TRUE if the package should be ignored, FALSE if not.
   */
  public function isIgnored(PackageInterface $package): bool {
    return in_array($package->getName(), $this->options['ignore'], TRUE);
  }

  /**
   * Creates an unpack options object.
   *
   * @param array $extras
   *   The contents of the 'extras' section.
   *
   * @return self
   *   The unpack options object representing the provided unpack options
   */
  public static function create(array $extras): self {
    $options = $extras[self::ID] ?? [];
    return new self($options);
  }

}
