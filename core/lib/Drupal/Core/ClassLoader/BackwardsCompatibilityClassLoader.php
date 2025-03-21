<?php

declare(strict_types=1);

namespace Drupal\Core\ClassLoader;

/**
 * Adds backwards compatibility support for deprecated classes.
 */
final class BackwardsCompatibilityClassLoader {

  public function __construct(protected array $movedClasses) {}

  /**
   * Aliases a moved class to another class, instead of actually autoloading it.
   *
   * @param string $class
   *   The classname to load.
   */
  public function loadClass(string $class): void {
    if (isset($this->movedClasses[$class])) {
      $moved = $this->movedClasses[$class];
      if (isset($moved['deprecation_version']) && isset($moved['removed_version']) && isset($moved['change_record'])) {
        // @phpcs:ignore
        @trigger_error(sprintf('Class %s is deprecated in %s and is removed from %s, use %s instead. See %s', $class, $moved['deprecation_version'], $moved['removed_version'], $moved['class'], $moved['change_record']), E_USER_DEPRECATED);
      }
      class_alias($moved['class'], $class, TRUE);
    }
  }

}
