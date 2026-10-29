<?php

declare(strict_types=1);

namespace Drupal\TestTools\ErrorHandler;

use Symfony\Component\ErrorHandler\DebugClassLoader;

/**
 * Extends Symfony's DebugClassLoader for Drupal-aware vendor boundaries.
 *
 * Symfony's DebugClassLoader uses the first namespace segment as the vendor
 * boundary for return type deprecation checks. Since all Drupal code shares
 * "Drupal\" as the first segment, cross-extension return type deprecations
 * (e.g., contrib extending core) are never triggered.
 *
 * This subclass treats each Drupal extension (the second namespace segment)
 * as the vendor boundary, enabling return type deprecation notices when an
 * extension overrides a method from a different extension without adding
 * native return types.
 *
 * @internal
 */
class DrupalDebugClassLoader extends DebugClassLoader {

  /**
   * Cached ReflectionProperty for DebugClassLoader::$returnTypes.
   */
  private static \ReflectionProperty $returnTypesProperty;

  /**
   * Cached ReflectionProperty for DebugClassLoader::$patchTypes.
   */
  private static \ReflectionProperty $patchTypesProperty;

  /**
   * {@inheritdoc}
   */
  public function checkAnnotations(\ReflectionClass $refl, string $class): array {
    $deprecations = parent::checkAnnotations($refl, $class);

    // Only process non-trait Drupal classes.
    if (!str_starts_with($class, 'Drupal\\') || trait_exists($class, FALSE)) {
      return $deprecations;
    }

    self::$returnTypesProperty ??= new \ReflectionProperty(DebugClassLoader::class, 'returnTypes');
    $returnTypes = self::$returnTypesProperty->getValue()[$class] ?? [];

    self::$patchTypesProperty ??= new \ReflectionProperty(DebugClassLoader::class, 'patchTypes');
    if (!$returnTypes || empty(self::$patchTypesProperty->getValue($this)['deprecations'])) {
      return $deprecations;
    }

    $classExtension = self::getExtensionName($class);
    $className = str_contains($class, "@anonymous\0")
      ? (get_parent_class($class) ?: key(class_implements($class)) ?: 'class') . '@anonymous'
      : $class;

    foreach ($returnTypes as $method => $returnTypeData) {
      // PHPDoc template return types have no equivalent native PHP type. Symfony
      // stores NULL to stop child classes from inheriting return type data from
      // their interfaces. Skip this entry. It has no return type or declaring
      // class for the cross-extension deprecation check.
      // @see \Symfony\Component\ErrorHandler\DebugClassLoader::setReturnType()
      if ($returnTypeData === NULL) {
        continue;
      }
      [$normalizedType, , $declaringClass] = $returnTypeData;

      // Skip if no cross-extension boundary: empty declaring class (magic
      // methods), same class (own @return), or same/non-Drupal extension.
      $declaringExtension = self::getExtensionName($declaringClass);
      if ($declaringClass === '' || $declaringClass === $class || $declaringExtension === NULL || $classExtension === $declaringExtension) {
        continue;
      }

      // Skip if not actually overridden, or already has a native return type.
      $methodRefl = $refl->getMethod($method);
      if ($methodRefl->class !== $class || $methodRefl->hasReturnType()) {
        continue;
      }

      // Skip if the method's docblock contains @deprecated or @return.
      $docComment = $methodRefl->getDocComment();
      if ($docComment !== FALSE && (str_contains($docComment, '@deprecated') || str_contains($docComment, '@return'))) {
        continue;
      }

      $deprecations[] = \sprintf(
        'Method "%s::%s()" might add "%s" as a native return type declaration in the future. Do the same in %s "%s" now to avoid errors or add an explicit @return annotation to suppress this message.',
        $declaringClass,
        $method,
        $normalizedType,
        interface_exists($declaringClass) ? 'implementation' : 'child class',
        $className,
      );
    }

    return $deprecations;
  }

  /**
   * Extracts the Drupal extension name from a fully qualified class name.
   *
   * @param string $class
   *   The fully qualified class name.
   *
   * @return string|null
   *   The extension name, or NULL for non-Drupal classes.
   */
  private static function getExtensionName(string $class): ?string {
    if (!str_starts_with($class, 'Drupal\\')) {
      return NULL;
    }

    $parts = explode('\\', $class);
    if (isset($parts[2]) && $parts[1] === 'Tests') {
      return $parts[2];
    }
    return $parts[1] ?? NULL;
  }

}
