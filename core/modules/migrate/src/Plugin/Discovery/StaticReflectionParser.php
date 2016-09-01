<?php

namespace Drupal\migrate\Plugin\Discovery;

use Doctrine\Common\Reflection\StaticReflectionParser as BaseStaticReflectionParser;

/**
 * Allows getting the reflection parser for the parent class.
 *
 * @internal
 *   This is a temporary solution to the fact that migration source plugins have
 *   more than one provider. This functionality will be moved to core in
 *   https://www.drupal.org/node/2786355.
 */
class StaticReflectionParser extends BaseStaticReflectionParser {

  /**
   * If the current class extends another, get the parser for the latter.
   *
   * @param \Doctrine\Common\Reflection\StaticReflectionParser $parser
   *   The current static parser.
   * @param $finder
   *   The class finder. Must implement
   *   \Doctrine\Common\Reflection\ClassFinderInterface, but can do so
   *   implicitly (i.e., implements the interface's methods but not the actual
   *   interface).
   *
   * @return static|null
   *   The static parser for the parent if there's a parent class or NULL.
   */
  public static function getParentParser(BaseStaticReflectionParser $parser, $finder) {
    // Ensure the class has been parsed before accessing the parentClassName
    // property.
    $parser->parse();
    if ($parser->parentClassName) {
      return new static($parser->parentClassName, $finder, $parser->classAnnotationOptimize);
    }
  }

}
